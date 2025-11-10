<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoice;
use App\Models\Payment;
use App\Models\ClientLedger;
use App\Models\PaymentMethod;
use App\Models\BatchPayment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    // ============================================================
    // 📋 1. Menampilkan daftar invoice milik klien yang login
    // ============================================================
    public function index(Request $request): View
    {
        $client = Auth::guard('client')->user();

        // === Query dasar: ambil semua invoice non-draft milik klien ===
        $query = $client->salesInvoices()
            ->where('status', '!=', 'draft')
            ->with(['deductingReturns', 'adjustments']);

        // TODO: Tambahkan filter dan sorting jika dibutuhkan di masa depan
        $invoices = $query->paginate(15)->appends($request->query());

        // === Data unik untuk dropdown filter ===
        $uniqueOrderDates = $client->salesInvoices()
            ->select(DB::raw('DISTINCT DATE(order_date) as order_date'))
            ->pluck('order_date');

        $uniqueDueDates = $client->salesInvoices()
            ->select(DB::raw('DISTINCT DATE(due_date) as due_date'))
            ->pluck('due_date');

        return view('client.invoices.index', compact('invoices', 'uniqueOrderDates', 'uniqueDueDates'));
    }

    // ============================================================
    // 📄 2. Menampilkan detail satu invoice klien
    // ============================================================
    public function show(SalesInvoice $invoice): View
    {
        // === Validasi akses: hanya pemilik invoice & bukan draft ===
        if ($invoice->client_id !== Auth::guard('client')->id() || $invoice->status == 'draft') {
            abort(403, 'Akses Ditolak');
        }

        // === Muat semua relasi yang dibutuhkan untuk tampilan detail ===
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

        // === Ambil metode pembayaran aktif untuk modal input ===
        $paymentMethods = PaymentMethod::where('is_active', true)
            ->whereIn('type', ['direct', 'pending'])
            ->orderBy('name')
            ->get();

        return view('client.invoices.show', compact('invoice', 'salesUsers', 'paymentMethods'));
    }

    // ============================================================
    // 💳 3. Menampilkan halaman pembayaran batch (multi-invoice)
    // ============================================================
    public function showBatchPay(): View
    {
        $client = Auth::guard('client')->user();

        // === Ambil semua invoice yang belum lunas ===
        $invoices = $client->salesInvoices()
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->with(['deductingReturns', 'adjustments'])
            ->orderBy('due_date', 'asc')
            ->get()
            ->filter(fn($invoice) => $invoice->remaining_balance > 0.01);

        $availableBalance = $client->balance;
        $pendingBalance = $client->pending_balance;

        // === Ambil metode pembayaran untuk form modal ===
        $paymentMethods = PaymentMethod::where('is_active', true)
            ->whereIn('type', ['direct', 'pending'])
            ->orderBy('name')
            ->get();

        return view('client.invoices.batch_pay', compact(
            'invoices',
            'availableBalance',
            'pendingBalance',
            'paymentMethods'
        ));
    }

    // ============================================================
    // 🧾 4. Simpan bukti pembayaran TUNGGAL (per invoice)
    // ============================================================
    public function uploadProof(Request $request, SalesInvoice $invoice): RedirectResponse
    {
        // === Pastikan invoice milik klien yang login ===
        if ($invoice->client_id !== Auth::guard('client')->id()) {
            abort(403);
        }

        $client = Auth::guard('client')->user();
        $sisaTagihan = $invoice->remaining_balance;
        $saldoKlien = $client->balance;

        // === Validasi input pembayaran ===
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

        // === Hitung total jika menggunakan saldo kredit ===
        if ($useCredit && $saldoKlien > 0) {
            $totalPaymentValue = $amountFromInput + $saldoKlien;
            $creditToUse = min($saldoKlien, $sisaTagihan, $totalPaymentValue);
        }

        // === Validasi jumlah pembayaran ===
        if ($totalPaymentValue <= 0.01 && $sisaTagihan > 0.01) {
            return back()->with('error', 'Jumlah pembayaran harus lebih dari 0.');
        }
        if ($totalPaymentValue > ($sisaTagihan + 0.01)) {
            return back()->with('error', 'Jumlah pembayaran melebihi sisa tagihan.');
        }

        // === Proses transaksi database ===
        DB::beginTransaction();
        try {
            $path = null;
            if ($request->hasFile('proof_of_payment')) {
                $path = $request->file('proof_of_payment')->store('payment_proofs', 'public');
            }

            $paymentMethodId = $validated['payment_method_id'] ?? null;

            // === Jika memakai kredit ===
            if ($creditToUse > 0) {
                $uniqueTransactionId = 'CREDIT-' . time() . '-' . $invoice->invoice_id;

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

            // === Jika ada pembayaran tunai / transfer ===
            if ($amountFromInput > 0) {
                $invoice->payments()->create([
                    'payment_date'          => now(),
                    'amount'                => $validated['payment_amount'],
                    'payment_method_id'     => $paymentMethodId,
                    'proof_of_payment_path' => $path,
                    'status'                => 'pending_verification',
                    'received_by_user_id'   => $validated['user_id_sales'] ?? null,
                    'notes'                 => $validated['notes'],
                ]);
            }

            // === Perbarui status invoice ===
            $invoice->updatePaymentStatus();

            DB::commit();

            return back()->with('success', 'Informasi pembayaran berhasil dikirim dan sedang menunggu verifikasi.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal simpan bukti: ' . $e->getMessage());
            return back()->with('error', 'Gagal menyimpan informasi pembayaran: ' . $e->getMessage());
        }
    }

    // ============================================================
    // 💰 5. Simpan bukti pembayaran BATCH (multi-invoice)
    // ============================================================
    public function storeBatchProof(Request $request): RedirectResponse
    {
        $client = Auth::guard('client')->user();

        // === Validasi dasar ===
        $rules = [
            'invoice_ids'        => 'required|array|min:1',
            'invoice_ids.*'      => 'exists:sales_invoices,invoice_id',
            'payment_amount'     => 'required|numeric|min:0',
            'use_credit'         => 'nullable|boolean',
            'user_id_sales'      => 'nullable|exists:users,user_id',
            'notes'              => 'nullable|string',
            'payment_method_id'  => [
                Rule::requiredIf(fn() => $request->input('payment_amount', 0) > 0 || !$request->has('use_credit')),
                'nullable',
                'exists:payment_methods,payment_method_id',
            ],
        ];

        // === Validasi tambahan berdasarkan konfigurasi metode pembayaran ===
        $paymentMethod = $request->filled('payment_method_id')
            ? PaymentMethod::find($request->input('payment_method_id'))
            : null;

        if ($paymentMethod) {
            $config = $paymentMethod->required_fields_config;

            // Wajib bukti transfer
            $rules['proof_of_payment'] = ($config === 'proof_only' || $config === 'proof_and_reference')
                ? 'required|image|mimes:jpeg,png,jpg|max:2048'
                : 'nullable|image|mimes:jpeg,png,jpg|max:2048';

            // Wajib nomor referensi
            $rules['reference_number'] = ($config === 'reference_only' || $config === 'proof_and_reference')
                ? 'required|string|max:255'
                : 'nullable|string|max:255';

            // Jika metode cash, wajib pilih sales penerima
            if (str_contains(strtolower($paymentMethod->name), 'cash')) {
                $rules['user_id_sales'] = 'required|exists:users,user_id';
            }
        } else {
            $rules['proof_of_payment'] = 'nullable|image|mimes:jpeg,png,jpg|max:2048';
            $rules['reference_number'] = 'nullable|string|max:255';
        }

        $validated = $request->validate($rules);

        // === Proses transaksi database ===
        DB::beginTransaction();
        try {
            $path = null;
            if ($request->hasFile('proof_of_payment')) {
                $path = $request->file('proof_of_payment')->store('payment_proofs', 'public');
            }

            // === Ambil semua invoice yang dipilih ===
            $invoices = SalesInvoice::whereIn('invoice_id', $validated['invoice_ids'])
                ->where('client_id', $client->client_id)
                ->with(['deductingReturns', 'adjustments'])
                ->get();

            // Hitung total tagihan dari semua invoice
            $totalSisaTagihan = $invoices->reduce(
                fn($carry, $invoice) => $carry + $invoice->remaining_balance,
                0.0
            );

            $useCredit = $validated['use_credit'] ?? false;
            $amountFromInput = (float) $validated['payment_amount'];
            $kreditAkanDigunakan = 0;

            // === Hitung total pembayaran jika pakai kredit ===
            if ($useCredit && $client->balance > 0) {
                $totalPaymentValue = $amountFromInput + $client->balance;
                $kreditAkanDigunakan = min($client->balance, $totalSisaTagihan, $totalPaymentValue);
            }

            $totalPaymentValue = $amountFromInput + $kreditAkanDigunakan;

            // === Validasi jumlah total ===
            if ($totalPaymentValue <= 0.01 && $totalSisaTagihan > 0.01) {
                throw new \Exception("Jumlah pembayaran harus lebih dari 0.");
            }
            if ($totalPaymentValue > ($totalSisaTagihan + 0.01)) {
                throw new \Exception("Jumlah pembayaran (Rp " . number_format($totalPaymentValue) . ") melebihi total tagihan (Rp " . number_format($totalSisaTagihan) . ").");
            }

            // === Simpan BatchPayment ===
            BatchPayment::create([
                'client_id'              => $client->client_id,
                'processed_by_user_id'   => null,
                'payment_date'           => now(),
                'total_amount'           => $validated['payment_amount'],
                'payment_method_id'      => $validated['payment_method_id'] ?? null,
                'status'                 => 'pending_verification',
                'notes'                  => $validated['notes'],
                'proof_of_payment_path'  => $path,
                'reference_number'       => $validated['reference_number'] ?? null,
                'details' => [
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
                ->with('success', 'Informasi pembayaran batch berhasil dikirim dan sedang menunggu verifikasi.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal simpan bukti batch: ' . $e->getMessage() . ' on line ' . $e->getLine());
            return back()->with('error', 'Gagal menyimpan informasi pembayaran: ' . $e->getMessage());
        }
    }
}
