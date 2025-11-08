<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoice;
use App\Models\Payment;
use App\Models\ClientLedger;
use App\Models\PaymentMethod; // ✅ TAMBAHKAN INI
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Models\User;
use Carbon\Carbon;
use App\Models\BatchPayment;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule; // ✅ TAMBAHKAN INI

class InvoiceController extends Controller
{
    /**
     * Menampilkan daftar invoice milik klien yang sedang login.
     */
    public function index(Request $request): View
    {
        $client = Auth::guard('client')->user();
        $query = $client->salesInvoices()
                        ->where('status', '!=', 'draft') 
                        ->with(['deductingReturns', 'adjustments']);
        // ... (Logika filter & sort Anda) ...
        $invoices = $query->paginate(15)->appends($request->query());
        $uniqueOrderDates = $client->salesInvoices()->select(DB::raw('DISTINCT DATE(order_date) as order_date'))->pluck('order_date');
        $uniqueDueDates = $client->salesInvoices()->select(DB::raw('DISTINCT DATE(due_date) as due_date'))->pluck('due_date');
        
        return view('client.invoices.index', compact('invoices', 'uniqueOrderDates', 'uniqueDueDates'));
    }

    /**
     * Menampilkan detail satu invoice milik klien.
     */
    public function show(SalesInvoice $invoice): View
    {
        if ($invoice->client_id !== Auth::guard('client')->id() || $invoice->status == 'draft') {
            abort(403, 'Akses Ditolak');
        }

        $invoice->load([
            'items.product', 
            'taxes', 
            'payments.receivedBy',
            'payments.paymentMethod', // ✅ Muat relasi
            'deductingReturns',
            'adjustments.user',
            'returns.items.product' 
        ]);
        
        $salesUsers = User::role('sales')->get();
        
        // ✅ Ambil metode pembayaran untuk modal
        $paymentMethods = PaymentMethod::where('is_active', true)
                            ->whereIn('type', ['direct', 'pending']) // Hanya manual & giro
                            ->orderBy('name')
                            ->get();

        return view('client.invoices.show', compact('invoice','salesUsers', 'paymentMethods'));
    }

    /**
     * Menampilkan halaman untuk pembayaran batch.
     */
    public function showBatchPay(): View
    {
        $client = Auth::guard('client')->user();
        
        $invoices = $client->salesInvoices()
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->with(['deductingReturns', 'adjustments'])
            ->orderBy('due_date', 'asc')
            ->get()
            ->filter(function ($invoice) {
                return $invoice->remaining_balance > 0.01;
            });

        $availableBalance = $client->balance;
        $pendingBalance = $client->pending_balance;
        
        // ✅ Ambil metode pembayaran untuk modal
        $paymentMethods = PaymentMethod::where('is_active', true)
                            ->whereIn('type', ['direct', 'pending'])
                            ->orderBy('name')
                            ->get();

        return view('client.invoices.batch_pay', compact(
            'invoices', 
            'availableBalance', 
            'pendingBalance',
            'paymentMethods' // ✅ Kirim ke view
        ));
    }

    /**
     * Menyimpan bukti pembayaran manual TUNGGAL
     */
    public function uploadProof(Request $request, SalesInvoice $invoice): RedirectResponse
    {
        if ($invoice->client_id !== Auth::guard('client')->id()) {
            abort(403);
        }

        $client = Auth::guard('client')->user();
        $sisaTagihan = $invoice->remaining_balance;
        $saldoKlien = $client->balance;

        // ✅ PERBARUI VALIDASI
        $validated = $request->validate([
            'payment_method_id' => [ // Ganti dari 'payment_method'
                Rule::requiredIf(function () use ($request) {
                    return $request->input('payment_amount', 0) > 0 || !$request->has('use_credit');
                }),
                'nullable',
                'exists:payment_methods,payment_method_id',
            ],
            'payment_amount'    => "required|numeric|min:0",
            'use_credit'        => 'nullable|boolean',
            'user_id_sales'     => 'nullable|exists:users,user_id',
            'proof_of_payment'  => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'notes'             => 'nullable|string',
        ]);

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
        if ($totalPaymentValue > ($sisaTagihan + 0.01)) {
            return back()->with('error', 'Jumlah pembayaran melebihi sisa tagihan.');
        }
        
        DB::beginTransaction();
        try {
            $path = null;
            if ($request->hasFile('proof_of_payment')) {
                $path = $request->file('proof_of_payment')->store('payment_proofs', 'public');
            }
            
            $paymentMethodId = $validated['payment_method_id'] ?? null;

            if ($creditToUse > 0) {
                $uniqueTransactionId = 'CREDIT-' . time() . '-' . $invoice->invoice_id;
                Payment::create([
                    'invoice_id'            => $invoice->invoice_id,
                    'payment_method_id'     => null, // Pembayaran kredit tidak punya ID metode manual
                    'payment_date'          => now(),
                    'amount'                => $creditToUse,
                    'status'                => 'completed',
                    'notes'                 => 'Saldo kredit digunakan oleh klien.',
                ]);
                ClientLedger::create([
                    'client_id' => $client->client_id,
                    'sales_invoice_id' => $invoice->invoice_id,
                    'reference_type' => SalesInvoice::class,
                    'reference_id' => $invoice->invoice_id,
                    'transaction_date' => now(),
                    'type' => 'debit',
                    'amount' => -$creditToUse,
                    'status' => 'available',
                    'description' => 'Digunakan untuk membayar Invoice #' . $invoice->invoice_number,
                    'user_id' => null,
                ]);
                
                // Jangan panggil increment, biarkan updatePaymentStatus yang menangani
                // $invoice->increment('amount_paid', $creditToUse);
            }

            if ($amountFromInput > 0) {
                $invoice->payments()->create([
                    'payment_date'          => now(),
                    'amount'                => $validated['payment_amount'],
                    'payment_method_id'     => $paymentMethodId, // ✅ Simpan ID
                    'proof_of_payment_path' => $path,
                    'status'                => 'pending_verification',
                    'received_by_user_id'   => $validated['user_id_sales'] ?? null,
                    'notes'                 => $validated['notes'],
                ]);
            }

            // Panggil fungsi update status
            $invoice->updatePaymentStatus();

            DB::commit();

            return back()->with('success', 'Informasi pembayaran berhasil dikirim dan sedang menunggu verifikasi.');
        
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal simpan bukti: ' . $e->getMessage());
            return back()->with('error', 'Gagal menyimpan informasi pembayaran: ' . $e->getMessage());
        }
    }
    
    /**
     * Menyimpan bukti pembayaran batch manual (Transfer/Cash) untuk verifikasi admin.
     */
    public function storeBatchProof(Request $request): RedirectResponse
    {
        $client = Auth::guard('client')->user();
        
        // ✅ PERBARUI VALIDASI
        $validated = $request->validate([
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'exists:sales_invoices,invoice_id',
            'payment_method_id' => [ // Ganti dari 'payment_method'
                Rule::requiredIf(function () use ($request) {
                    return $request->input('payment_amount', 0) > 0 || !$request->has('use_credit');
                }),
                'nullable',
                'exists:payment_methods,payment_method_id',
            ],
            'payment_amount'    => "required|numeric|min:0",
            'use_credit'        => 'nullable|boolean',
            'user_id_sales'     => 'nullable|exists:users,user_id',
            'proof_of_payment'  => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'notes'             => 'nullable|string',
        ]);

        DB::beginTransaction(); 
        try {
            $path = null;
            if ($request->hasFile('proof_of_payment')) {
                $path = $request->file('proof_of_payment')->store('payment_proofs', 'public');
            }

            $invoices = SalesInvoice::whereIn('invoice_id', $validated['invoice_ids'])
                ->where('client_id', $client->client_id)
                ->with(['deductingReturns', 'adjustments'])
                ->get();
            
            $totalSisaTagihan = $invoices->reduce(function ($carry, $invoice) {
                return $carry + $invoice->remaining_balance;
            }, 0.0);

            $useCredit = $validated['use_credit'] ?? false;
            $kreditAkanDigunakan = 0;
            $amountFromInput = (float) $validated['payment_amount'];

            if ($useCredit && $client->balance > 0) {
                $totalPaymentValue = $amountFromInput + $client->balance;
                $kreditAkanDigunakan = min($client->balance, $totalSisaTagihan, $totalPaymentValue);
            }
            
            $totalPaymentValue = $amountFromInput + $kreditAkanDigunakan;
            
            if ($totalPaymentValue <= 0.01 && $totalSisaTagihan > 0.01) {
                 throw new \Exception("Jumlah pembayaran harus lebih dari 0.");
            }
            if ($totalPaymentValue > ($totalSisaTagihan + 0.01)) {
                throw new \Exception("Jumlah pembayaran (Rp ".number_format($totalPaymentValue).") melebihi total tagihan yang dipilih (Rp ".number_format($totalSisaTagihan).").");
            }
            
            // ✅ PERBAIKAN: Simpan ID ke kolom utama
            BatchPayment::create([
                'client_id' => $client->client_id,
                'processed_by_user_id' => null,
                'payment_date' => now(),
                'total_amount' => $validated['payment_amount'],
                'payment_method_id' => $validated['payment_method_id'], // <-- Simpan ID di sini
                'status' => 'pending_verification',
                'notes' => $validated['notes'],
                'details' => [ // Berikan sebagai array PHP
                    'invoice_ids' => $validated['invoice_ids'],
                    'total_tagihan_dipilih' => $totalSisaTagihan,
                    'use_credit' => $useCredit,
                    'credit_amount_to_use' => $kreditAkanDigunakan,
                    'proof_path' => $path,
                    'sales_receiver_id' => $validated['user_id_sales'] ?? null,
                    'payment_method_id' => $validated['payment_method_id'] // <-- Simpan juga di sini untuk `approve()`
                ],
            ]);
            
            DB::commit();

            return redirect()->route('client.invoices.index')
                         ->with('success', 'Informasi pembayaran batch berhasil dikirim dan sedang menunggu verifikasi.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal simpan bukti batch: ' . $e->getMessage() . ' on line ' . $e->getLine());
            return back()->with('error', 'Gagal menyimpan informasi pembayaran: ' . $e->getMessage());
        }
    }
}