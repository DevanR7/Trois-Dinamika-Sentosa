<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoice;
use App\Models\Payment;
use App\Models\ClientLedger;
use App\Models\PaymentMethod;
use App\Models\BatchPayment;
use App\Models\BulkSalesPayment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class InvoiceController extends Controller
{
    // ============================================================
    // 1. Halaman Daftar Invoice Klien
    // ============================================================
    public function index(Request $request): View
    {
        $client = Auth::guard('client')->user();

        // Ambil semua invoice milik klien, kecuali yang masih draft
        $query = $client->salesInvoices()
            ->where('status', '!=', 'draft')
            ->with(['deductingReturns', 'adjustments']);

        // Pagination dan query tambahan
        $invoices = $query->paginate(15)->appends($request->query());

        // Ambil daftar tanggal unik untuk filter
        $uniqueOrderDates = $client->salesInvoices()
            ->select(DB::raw('DISTINCT DATE(order_date) as order_date'))
            ->pluck('order_date');

        $uniqueDueDates = $client->salesInvoices()
            ->select(DB::raw('DISTINCT DATE(due_date) as due_date'))
            ->pluck('due_date');

        return view('client.invoices.index', compact('invoices', 'uniqueOrderDates', 'uniqueDueDates'));
    }

    // ============================================================
    // 2. Halaman Detail Invoice Klien
    // ============================================================
    public function show(SalesInvoice $invoice): View
    {
        // Validasi agar hanya pemilik invoice yang bisa melihat
        if ($invoice->client_id !== Auth::guard('client')->id() || $invoice->status === 'draft') {
            abort(403, 'Akses Ditolak');
        }

        // Muat relasi yang diperlukan untuk tampilan detail
        $invoice->load([
            'items.product',
            'taxes',
            'payments.receivedBy',
            'payments.paymentMethod',
            'deductingReturns',
            'adjustments.user',
            'returns.items.product',
        ]);

        $salesUsers = User::role('sales')->get();

        $paymentMethods = PaymentMethod::where('is_active', true)
            ->whereIn('type', ['direct', 'pending'])
            ->orderBy('name')
            ->get();

        return view('client.invoices.show', compact('invoice', 'salesUsers', 'paymentMethods'));
    }

    // ============================================================
    // 3. Halaman Pembayaran Batch (Multi-Invoice)
    // ============================================================
    public function showBulkPay(): View
    {
        $client = Auth::guard('client')->user();

        // Ambil semua invoice yang belum lunas
        $invoices = $client->salesInvoices()
            ->whereIn('status', ['unpaid', 'partially_paid', 'paid']) // PERUBAHAN: Tambahkan 'paid'
            ->with(['deductingReturns', 'adjustments'])
            ->orderBy('due_date', 'asc')
            ->get();
            // PERUBAHAN: Hapus filter untuk menampilkan invoice dengan saldo negatif
            // ->filter(fn($invoice) => $invoice->remaining_balance > 0.01);

        $availableBalance = $client->balance;
        $pendingBalance = $client->pending_balance;

        $paymentMethods = PaymentMethod::where('is_active', true)
            ->whereIn('type', ['direct', 'pending'])
            ->orderBy('name')
            ->get();

        return view('client.invoices.bulk_pay', compact(
            'invoices',
            'availableBalance',
            'pendingBalance',
            'paymentMethods'
        ));
    }

    // ============================================================
    // 4. Simpan Bukti Pembayaran TUNGGAL
    // ============================================================
    public function uploadProof(Request $request, SalesInvoice $invoice): RedirectResponse
    {
        // Pastikan invoice milik klien yang login
        if ($invoice->client_id !== Auth::guard('client')->id()) {
            abort(403);
        }

        $client = Auth::guard('client')->user();
        $sisaTagihan = $invoice->remaining_balance;
        $saldoKlien = $client->balance;

        // Validasi input
        $validated = $request->validate([
            'payment_method_id' => [
                Rule::requiredIf(fn() => $request->input('payment_amount', 0) > 0 || !$request->has('use_credit')),
                'nullable',
                'exists:payment_methods,payment_method_id',
            ],
            'payment_amount'   => 'required|numeric|min:0',
            'use_credit'       => 'nullable|boolean',
            'user_id_sales'    => 'nullable|exists:users,user_id',
            'proof_of_payment' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'notes'            => 'nullable|string',
        ]);

        $amountFromInput = (float) $validated['payment_amount'];
        $useCredit = $validated['use_credit'] ?? false;
        $creditToUse = 0;
        $totalPaymentValue = $amountFromInput;

        // Hitung total jika menggunakan saldo kredit
        if ($useCredit && $saldoKlien > 0) {
            $totalPaymentValue = $amountFromInput + $saldoKlien;
            $creditToUse = min($saldoKlien, $sisaTagihan, $totalPaymentValue);
        }

        // Validasi jumlah pembayaran
        if ($totalPaymentValue <= 0.01 && $sisaTagihan > 0.01) {
            return back()->with('error', 'Jumlah pembayaran harus lebih dari 0.');
        }

        // PERUBAHAN: Hapus validasi kelebihan bayar
        // if ($totalPaymentValue > ($sisaTagihan + 0.01)) {
        //     return back()->with('error', 'Jumlah pembayaran melebihi sisa tagihan.');
        // }
        // Biarkan overpayment terjadi. Admin akan menanganinya
        // (Controller admin akan mengubahnya jadi deposit).

        DB::beginTransaction();
        try {
            $path = $request->hasFile('proof_of_payment')
                ? $request->file('proof_of_payment')->store('payment_proofs', 'public')
                : null;

            // Jika memakai kredit
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

            // Jika ada pembayaran baru (non-kredit)
            if ($amountFromInput > 0) {
                $invoice->payments()->create([
                    'payment_date'          => now(),
                    'amount'                => $validated['payment_amount'],
                    'payment_method_id'     => $validated['payment_method_id'] ?? null,
                    'proof_of_payment_path' => $path,
                    'status'                => 'pending_verification',
                    'received_by_user_id'   => $validated['user_id_sales'] ?? null,
                    'notes'                 => $validated['notes'],
                ]);
            }

            $invoice->updatePaymentStatus();
            DB::commit();

            return back()->with('success', 'Informasi pembayaran berhasil dikirim dan menunggu verifikasi.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menyimpan bukti pembayaran: ' . $e->getMessage());
            return back()->with('error', 'Gagal menyimpan informasi pembayaran: ' . $e->getMessage());
        }
    }

    // ============================================================
    // 5. Simpan Bukti Pembayaran BATCH (Multi-Invoice)
    // ============================================================
    public function storeBatchProof(Request $request): RedirectResponse
    {
        $client = Auth::guard('client')->user();

        // Validasi awal
        $rules = [
            'invoice_ids'      => 'required|array|min:1',
            'invoice_ids.*'    => 'exists:sales_invoices,invoice_id',
            'payment_amount'   => 'required|numeric|min:0',
            'use_credit'       => 'nullable|boolean',
            'user_id_sales'    => 'nullable|exists:users,user_id',
            'notes'            => 'nullable|string',
            'payment_method_id' => [
                Rule::requiredIf(fn() => $request->input('payment_amount', 0) > 0 || !$request->has('use_credit')),
                'nullable',
                'exists:payment_methods,payment_method_id',
            ],
        ];

        // Validasi tambahan berdasarkan metode pembayaran
        $paymentMethod = $request->filled('payment_method_id')
            ? PaymentMethod::find($request->input('payment_method_id'))
            : null;

        if ($paymentMethod) {
            $config = $paymentMethod->required_fields_config;

            $rules['proof_of_payment'] = in_array($config, ['proof_only', 'proof_and_reference'])
                ? 'required|image|mimes:jpeg,png,jpg|max:2048'
                : 'nullable|image|mimes:jpeg,png,jpg|max:2048';

            $rules['reference_number'] = in_array($config, ['reference_only', 'proof_and_reference'])
                ? 'required|string|max:255'
                : 'nullable|string|max:255';

            if (str_contains(strtolower($paymentMethod->name), 'cash')) {
                $rules['user_id_sales'] = 'required|exists:users,user_id';
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

            // Ambil semua invoice yang relevan
            $invoices = SalesInvoice::whereIn('invoice_id', $validated['invoice_ids'])
                ->where('client_id', $client->client_id)
                ->get();

            $totalSisaTagihan = $invoices->sum('remaining_balance');
            $useCredit = $validated['use_credit'] ?? false;
            $kreditAkanDigunakan = 0;
            $amountFromInput = (float) $validated['payment_amount'];

            if ($useCredit && $client->balance > 0) {
                $totalPaymentValue = $amountFromInput + $client->balance;
                // PERUBAHAN: Kredit yang dipakai adalah minimal dari saldo, ATAU total tagihan (jika tagihan negatif)
                $kreditAkanDigunakan = min($client->balance, max(0, $totalSisaTagihan), $totalPaymentValue);
            }

            $totalPaymentValue = $amountFromInput + $kreditAkanDigunakan;

            if ($totalPaymentValue <= 0.01 && $totalSisaTagihan > 0.01) {
                throw new \Exception("Jumlah pembayaran harus lebih dari 0.");
            }

            // PERUBAHAN: Hapus validasi kelebihan bayar
            // if ($totalPaymentValue > ($totalSisaTagihan + 0.01)) {
            //     throw new \Exception(
            //         "Jumlah pembayaran (Rp " . number_format($totalPaymentValue) .
            //         ") melebihi total tagihan (Rp " . number_format($totalSisaTagihan) . ")."
            //     );
            // }
            // Biarkan overpayment terjadi. JS di view sudah memberi info.

            BulkSalesPayment::create([
                'client_id'             => $client->client_id,
                'processed_by_user_id'  => null,
                'payment_date'          => now(),
                'total_amount'          => $validated['payment_amount'],
                'payment_method_id'     => $validated['payment_method_id'] ?? null,
                'status'                => 'pending_verification',
                'notes'                 => $validated['notes'],
                'proof_of_payment_path' => $path,
                'reference_number'      => $validated['reference_number'] ?? null,
                'details'               => [
                    'invoice_ids'           => $validated['invoice_ids'],
                    'total_tagihan_dipilih' => $totalSisaTagihan,
                    'use_credit'            => $useCredit,
                    'credit_amount_to_use'  => $kreditAkanDigunakan,
                    'proof_path'            => $path,
                    'sales_receiver_id'     => $validated['user_id_sales'] ?? null,
                    'payment_method_id'     => $validated['payment_method_id'] ?? null,
                    'reference_number'      => $validated['reference_number'] ?? null,
                ],
            ]);

            DB::commit();

            return redirect()->route('client.invoices.index')
                ->with('success', 'Informasi pembayaran batch berhasil dikirim dan menunggu verifikasi.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menyimpan bukti batch: ' . $e->getMessage() . ' di baris ' . $e->getLine());
            return back()->with('error', 'Gagal menyimpan informasi pembayaran: ' . $e->getMessage());
        }
    }
}