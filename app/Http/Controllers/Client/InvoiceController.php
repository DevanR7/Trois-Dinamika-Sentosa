<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoice;
use App\Models\Payment;
use App\Models\ClientLedger;
use App\Models\PaymentMethod;
use App\Models\BulkSalesPayment;
use App\Models\User;
use App\Models\CompanyBankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $client = Auth::guard('client')->user();
        $query = $client->salesInvoices()->where('status', '!=', 'draft')->with(['deductingReturns', 'adjustments']);
        $invoices = $query->paginate(15)->appends($request->query());
        
        $uniqueOrderDates = $client->salesInvoices()->select(DB::raw('DISTINCT DATE(order_date) as order_date'))->pluck('order_date');
        $uniqueDueDates = $client->salesInvoices()->select(DB::raw('DISTINCT DATE(due_date) as due_date'))->pluck('due_date');

        return view('client.invoices.index', compact('invoices', 'uniqueOrderDates', 'uniqueDueDates'));
    }

    public function show(SalesInvoice $invoice): View
    {
        if ($invoice->client_id !== Auth::guard('client')->id() || $invoice->status === 'draft') {
            abort(403, 'Akses Ditolak');
        }

        $invoice->load(['items.product', 'taxes', 'payments.receivedBy', 'payments.paymentMethod', 'deductingReturns', 'adjustments.user', 'returns.items.product']);
        $salesUsers = User::role('sales')->get();
        
        $paymentMethods = PaymentMethod::where('is_active', true)
            ->whereIn('type', ['direct', 'pending'])
            ->orderBy('name')
            ->get();

        $gatewayMethod = PaymentMethod::where('is_active', true)
            ->where('type', 'gateway')
            ->first();

        $companyBankAccounts = CompanyBankAccount::where('is_active', true)
            ->orderBy('bank_name')
            ->get();

        return view('client.invoices.show', compact('invoice', 'salesUsers', 'paymentMethods', 'companyBankAccounts', 'gatewayMethod'));
    }

    public function showBulkPay(): View
    {
        $client = Auth::guard('client')->user();
        $invoices = $client->salesInvoices()
            ->whereIn('status', ['unpaid', 'partially_paid']) 
            ->with(['deductingReturns', 'adjustments'])
            ->orderBy('due_date', 'asc')
            ->get();

        $availableBalance = $client->balance;
        $pendingBalance = $client->pending_balance;

        $paymentMethods = PaymentMethod::where('is_active', true)
            ->whereIn('type', ['direct', 'pending'])
            ->orderBy('name')
            ->get();
        
        $gatewayMethod = PaymentMethod::where('is_active', true)
            ->where('type', 'gateway')
            ->first();

        $companyBankAccounts = CompanyBankAccount::where('is_active', true)
            ->orderBy('bank_name')
            ->get();

        return view('client.invoices.bulk_pay', compact('invoices', 'availableBalance', 'pendingBalance', 'paymentMethods', 'companyBankAccounts', 'gatewayMethod'));
    }

    public function uploadProof(Request $request, SalesInvoice $invoice): RedirectResponse
    {
        if ($invoice->client_id !== Auth::guard('client')->id()) abort(403);

        $client = Auth::guard('client')->user();
        $sisaTagihan = $invoice->remaining_balance;
        $saldoKlien = $client->balance;

        $validated = $request->validate([
            'payment_method_id' => [
                Rule::requiredIf(fn() => $request->input('payment_amount', 0) > 0 || !$request->has('use_credit')),
                'nullable',
                'exists:payment_methods,payment_method_id',
            ],
            'company_bank_account_id' => [
                 Rule::requiredIf(fn() => $request->input('payment_amount', 0) > 0),
                 'nullable',
                 'exists:company_bank_accounts,company_bank_account_id'
            ],
            'payment_amount'    => 'required|numeric|min:0',
            'use_credit'        => 'nullable|boolean',
            'user_id_sales'     => 'nullable|exists:users,user_id',
            'notes'             => 'nullable|string',
        ]);

        $paymentMethod = null;
        if (!empty($validated['payment_method_id'])) {
            $paymentMethod = PaymentMethod::find($validated['payment_method_id']);
            
            $config = $paymentMethod->client_input_config;

            if (in_array($config, ['proof_only', 'proof_and_reference'])) {
                $request->validate(['proof_of_payment' => 'required|image|mimes:jpeg,png,jpg|max:2048']);
            } else {
                $request->validate(['proof_of_payment' => 'nullable|image|mimes:jpeg,png,jpg|max:2048']);
            }

            if (in_array($config, ['reference_only', 'proof_and_reference'])) {
                $request->validate(['reference_number' => 'required|string|max:255']);
            } else {
                $request->validate(['reference_number' => 'nullable|string|max:255']);
            }
        }

        $amountFromInput = (float) $validated['payment_amount'];
        $useCredit = $validated['use_credit'] ?? false;
        $creditToUse = 0;
        $totalPaymentValue = $amountFromInput;

        if ($useCredit && $saldoKlien > 0) {
            $totalPaymentValue = $amountFromInput + $saldoKlien;
            $creditToUse = min($saldoKlien, $sisaTagihan, $totalPaymentValue);
        }

        if ($totalPaymentValue <= 0.01 && $sisaTagihan > 0.01) {
            return back()->with('error', 'Jumlah pembayaran harus lebih dari 0.');
        }

        DB::beginTransaction();
        try {
            $path = $request->hasFile('proof_of_payment')
                ? $request->file('proof_of_payment')->store('payment_proofs', 'public')
                : null;
            
            $status = $paymentMethod ? $paymentMethod->client_status_default : 'pending_verification';

            if ($creditToUse > 0) {
                Payment::create([
                    'invoice_id'        => $invoice->invoice_id,
                    'payment_method_id' => null, 
                    'payment_date'      => now(),
                    'amount'            => $creditToUse,
                    'status'            => 'completed', 
                    'notes'             => 'Saldo kredit digunakan oleh klien.',
                ]);

                ClientLedger::create([
                    'client_id'        => $client->client_id,
                    'sales_invoice_id' => $invoice->invoice_id,
                    'reference_type'   => SalesInvoice::class,
                    'reference_id'     => $invoice->invoice_id,
                    'transaction_date' => now(),
                    'type'             => 'debit',
                    'amount'           => -$creditToUse,
                    'status'           => 'available',
                    'description'      => 'Digunakan untuk membayar Invoice #' . $invoice->invoice_number,
                    'user_id'          => null,
                ]);
            }

            if ($amountFromInput > 0) {
                $invoice->payments()->create([
                    'payment_date'        => now(),
                    'amount'              => $amountFromInput,
                    'payment_method_id'   => $validated['payment_method_id'] ?? null,
                    'company_bank_account_id' => $validated['company_bank_account_id'] ?? null,
                    'proof_of_payment_path' => $path,
                    'status'              => $status, 
                    'received_by_user_id' => null,
                    'notes'               => $validated['notes'],
                    'reference_number'    => $request->input('reference_number'),
                ]);
            }

            $invoice->updatePaymentStatus();
            DB::commit();

            return back()->with('success', 'Informasi pembayaran berhasil dikirim.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menyimpan bukti pembayaran klien: ' . $e->getMessage());
            return back()->with('error', 'Gagal menyimpan informasi: ' . $e->getMessage());
        }
    }

    public function storeBatchProof(Request $request): RedirectResponse
    {
        $client = Auth::guard('client')->user();

        $rules = [
            'invoice_ids'   => 'required|array|min:1',
            'invoice_ids.*' => 'exists:sales_invoices,invoice_id',
            'payment_amount'=> 'required|numeric|min:0',
            'use_credit'    => 'nullable|boolean',
            'notes'         => 'nullable|string',
            'payment_method_id' => [
                Rule::requiredIf(fn() => $request->input('payment_amount', 0) > 0 || !$request->has('use_credit')),
                'nullable',
                'exists:payment_methods,payment_method_id',
            ],
            'company_bank_account_id' => [
                 Rule::requiredIf(fn() => $request->input('payment_amount', 0) > 0),
                 'nullable',
                 'exists:company_bank_accounts,company_bank_account_id'
            ],
        ];

        $paymentMethod = null;
        if ($request->filled('payment_method_id')) {
            $paymentMethod = PaymentMethod::find($request->input('payment_method_id'));
            
            $config = $paymentMethod->client_input_config;

            if (in_array($config, ['proof_only', 'proof_and_reference'])) {
                $rules['proof_of_payment'] = 'required|image|mimes:jpeg,png,jpg|max:2048';
            } else {
                $rules['proof_of_payment'] = 'nullable|image|mimes:jpeg,png,jpg|max:2048';
            }

            if (in_array($config, ['reference_only', 'proof_and_reference'])) {
                $rules['reference_number'] = 'required|string|max:255';
            } else {
                $rules['reference_number'] = 'nullable|string|max:255';
            }
        } else {
            $rules['proof_of_payment'] = 'nullable|image|mimes:jpeg,png,jpg|max:2048';
            $rules['reference_number'] = 'nullable|string|max:255';
        }

        $validated = $request->validate($rules);

        DB::beginTransaction();
        try {
            $path = $request->hasFile('proof_of_payment')
                ? $request->file('proof_of_payment')->store('payment_proofs', 'public')
                : null;

            $invoices = SalesInvoice::whereIn('invoice_id', $validated['invoice_ids'])
                ->where('client_id', $client->client_id)
                ->get();

            $totalSisaTagihan = $invoices->sum('remaining_balance');
            $useCredit = $validated['use_credit'] ?? false;
            $kreditAkanDigunakan = 0;
            $amountFromInput = (float) $validated['payment_amount'];

            if ($useCredit && $client->balance > 0) {
                $totalPaymentValue = $amountFromInput + $client->balance;
                $kreditAkanDigunakan = min($client->balance, max(0, $totalSisaTagihan), $totalPaymentValue);
            }

            $totalPaymentValue = $amountFromInput + $kreditAkanDigunakan;

            if ($totalPaymentValue <= 0.01 && $totalSisaTagihan > 0.01) {
                throw new \Exception("Jumlah pembayaran harus lebih dari 0.");
            }
            
            $status = $paymentMethod ? $paymentMethod->client_status_default : 'pending_verification';

            BulkSalesPayment::create([
                'client_id'             => $client->client_id,
                'processed_by_user_id'  => null,
                'payment_date'          => now(),
                'total_amount'          => $validated['payment_amount'], 
                'payment_method_id'     => $validated['payment_method_id'] ?? null,
                'company_bank_account_id' => $validated['company_bank_account_id'] ?? null,
                'status'                => $status, 
                'notes'                 => $validated['notes'],
                'proof_of_payment_path' => $path,
                'reference_number'      => $validated['reference_number'] ?? null,
                'details'               => [
                    'invoice_ids'           => $validated['invoice_ids'],
                    'total_tagihan_dipilih' => $totalSisaTagihan,
                    'use_credit'            => $useCredit,
                    'credit_amount_to_use'  => $kreditAkanDigunakan,
                    'proof_path'            => $path,
                    'payment_method_id'     => $validated['payment_method_id'] ?? null,
                    'reference_number'      => $validated['reference_number'] ?? null,
                ],
            ]);

            DB::commit();

            return redirect()->route('client.invoices.index')
                ->with('success', 'Informasi pembayaran batch berhasil dikirim.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menyimpan bukti batch klien: ' . $e->getMessage());
            return back()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }
}