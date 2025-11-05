<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\SalesInvoice;
use App\Models\BatchPayment;
use App\Models\Payment;
use App\Models\ClientLedger;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class BatchPaymentController extends Controller
{
    // ======================================================
    // ✅ PERMISSION MIDDLEWARE
    // ======================================================
    public function __construct()
    {
        // Untuk admin yang membuat batch payment
        $this->middleware('permission:create-batch-payments')->only(['create', 'store', 'getUnpaidInvoicesApi']);
        // Untuk admin reviewer batch klien
        $this->middleware('permission:review-batch-payments')->only(['pending', 'showPending', 'approve', 'reject']);
    }

    // ======================================================
    // ✅ FORM PEMBUATAN BATCH PAYMENT
    // ======================================================
    public function create(): View
    {
        $clients = Client::orderBy('client_name')->get();
        return view('batch_payments.create', compact('clients'));
    }

    // ======================================================
    // ✅ API AMBIL INVOICE YANG BELUM LUNAS
    // ======================================================
    public function getUnpaidInvoicesApi(Client $client): JsonResponse
    {
        $invoices = $client->salesInvoices()
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->with(['deductingReturns', 'adjustments'])
            ->orderBy('due_date', 'asc')
            ->get();

        $invoicesWithBalance = $invoices->map(function ($invoice) {
            return [
                'invoice_id' => $invoice->invoice_id,
                'invoice_number' => $invoice->invoice_number,
                'due_date_formatted' => $invoice->due_date->format('d M Y'),
                'total_amount' => $invoice->total_amount,
                'amount_paid' => $invoice->amount_paid,
                'total_retur' => $invoice->total_deducting_returns,
                'sisa_tagihan' => $invoice->remaining_balance,
            ];
        })->filter(fn($inv) => $inv['sisa_tagihan'] > 0.01);

        return response()->json($invoicesWithBalance);
    }

    // ======================================================
    // ✅ SIMPAN PEMBAYARAN BATCH (ADMIN BUAT LANGSUNG)
    // ======================================================
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,client_id',
            'payment_date' => 'required|date',
            'total_amount' => 'required|numeric|min:0',
            'payment_method' => ['required_unless:total_amount,0', 'nullable', 'string'],
            'notes' => 'nullable|string',
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'required|exists:sales_invoices,invoice_id',
            'use_credit' => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            $client = Client::findOrFail($validated['client_id']);
            $pakaiKredit = $validated['use_credit'] ?? false;
            $danaDariInput = (float) ($validated['total_amount'] ?? 0);
            $kreditAwalKlien = $client->balance;

            $invoicesDipilih = SalesInvoice::whereIn('invoice_id', $validated['invoice_ids'])
                ->with(['deductingReturns', 'adjustments'])
                ->orderBy('due_date', 'asc')
                ->get();

            $totalTagihanTerpilih = $invoicesDipilih->reduce(fn($carry, $inv) => $carry + $inv->remaining_balance, 0.0);
            if ($totalTagihanTerpilih <= 0.01) throw new \Exception("Semua invoice yang dipilih sudah lunas.");

            // Hitung alokasi dana
            $kreditAkanDigunakan = ($pakaiKredit && $kreditAwalKlien > 0)
                ? min($kreditAwalKlien, $totalTagihanTerpilih)
                : 0;
            $sisaTagihanSetelahKredit = max(0, $totalTagihanTerpilih - $kreditAkanDigunakan);
            $danaInputAkanDigunakan = min($danaDariInput, $sisaTagihanSetelahKredit);
            $totalDanaAlokasi = $kreditAkanDigunakan + $danaInputAkanDigunakan;
            $sisaDanaInput = max(0, $danaDariInput - $danaInputAkanDigunakan);

            if ($totalDanaAlokasi <= 0.01 && $sisaDanaInput <= 0.01) {
                throw new \Exception("Tidak ada dana (input/kredit) yang bisa dialokasikan.");
            }

            // Buat BatchPayment
            $metodeBatch = [];
            if ($kreditAkanDigunakan > 0) $metodeBatch[] = 'Kredit Klien';
            if ($danaInputAkanDigunakan > 0) $metodeBatch[] = $validated['payment_method'];
            $metodeGabung = implode(' + ', $metodeBatch) ?: 'N/A';

            $batchPayment = BatchPayment::create([
                'client_id' => $validated['client_id'],
                'processed_by_user_id' => Auth::id(),
                'payment_date' => $validated['payment_date'],
                'total_amount' => $totalDanaAlokasi,
                'notes' => $validated['notes'],
                'payment_method' => $metodeGabung,
                'status' => 'completed',
                'details' => json_encode(['invoice_ids' => $validated['invoice_ids']]),
            ]);

            // Ledger: kredit digunakan
            if ($kreditAkanDigunakan > 0) {
                ClientLedger::create([
                    'client_id' => $client->client_id,
                    'reference_type' => BatchPayment::class,
                    'reference_id' => $batchPayment->batch_payment_id,
                    'transaction_date' => $validated['payment_date'],
                    'type' => 'debit',
                    'amount' => -$kreditAkanDigunakan,
                    'status' => 'available',
                    'description' => 'Digunakan untuk Pembayaran Batch #' . $batchPayment->batch_payment_id,
                    'user_id' => Auth::id(),
                ]);
            }

            // Alokasikan ke invoice
            $sisaKredit = $kreditAkanDigunakan;
            $sisaInput = $danaInputAkanDigunakan;
            $alokasiLog = [];

            foreach ($invoicesDipilih as $invoice) {
                if ($sisaKredit <= 0.01 && $sisaInput <= 0.01) break;
                $sisaTagihan = $invoice->remaining_balance;
                if ($sisaTagihan <= 0.01) continue;

                $bayarDariKredit = min($sisaTagihan, $sisaKredit);
                $bayarDariInput = min($sisaTagihan - $bayarDariKredit, $sisaInput);
                $jumlahBayar = $bayarDariKredit + $bayarDariInput;

                $metodePayment = [];
                if ($bayarDariKredit > 0) $metodePayment[] = 'Kredit Klien';
                if ($bayarDariInput > 0) $metodePayment[] = $validated['payment_method'];
                $metodeGabungInvoice = implode(' + ', $metodePayment) ?: 'N/A';

                $invoice->payments()->create([
                    'batch_payment_id' => $batchPayment->batch_payment_id,
                    'payment_date' => $validated['payment_date'],
                    'amount' => $jumlahBayar,
                    'payment_method' => $metodeGabungInvoice,
                    'received_by_user_id' => Auth::id(),
                    'status' => 'completed',
                    'notes' => 'Auto-allocated dari Batch Payment #' . $batchPayment->batch_payment_id,
                ]);

                $invoiceCurrent = SalesInvoice::find($invoice->invoice_id);
                $invoiceCurrent->update([
                    'amount_paid' => ($invoiceCurrent->amount_paid ?? 0) + $jumlahBayar,
                    'status' => ($invoiceCurrent->remaining_balance - $jumlahBayar <= 0.01) ? 'paid' : 'partially_paid',
                ]);

                $sisaKredit -= $bayarDariKredit;
                $sisaInput -= $bayarDariInput;
                $alokasiLog[] = "Rp " . number_format($jumlahBayar) . " dialokasikan ke " . $invoice->invoice_number;
            }

            // Overpayment → simpan ke kredit klien
            if ($sisaDanaInput > 0.01) {
                ClientLedger::create([
                    'client_id' => $client->client_id,
                    'reference_type' => BatchPayment::class,
                    'reference_id' => $batchPayment->batch_payment_id,
                    'transaction_date' => $validated['payment_date'],
                    'type' => 'credit',
                    'amount' => $sisaDanaInput,
                    'status' => 'available',
                    'description' => 'Kelebihan dana dari Pembayaran Batch #' . $batchPayment->batch_payment_id,
                    'user_id' => Auth::id(),
                ]);
            }

            DB::commit();
            return redirect()->route('clients.show', $client->client_id)
                ->with('success', 'Pembayaran batch berhasil. ' . implode('. ', $alokasiLog));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menyimpan batch: ' . $e->getMessage());
            return back()->with('error', 'Gagal menyimpan pembayaran batch: ' . $e->getMessage())->withInput();
        }
    }

    // ======================================================
    // ✅ BAGIAN VERIFIKASI ADMIN
    // ======================================================
    public function pending(): View
    {
        $pendingBatches = BatchPayment::where('status', 'pending_verification')
            ->with('client')
            ->orderBy('created_at', 'asc')
            ->paginate(15);

        return view('batch_payments.pending', compact('pendingBatches'));
    }

    public function showPending(BatchPayment $batchPayment): View|RedirectResponse
    {
        if ($batchPayment->status !== 'pending_verification') {
            return redirect()->route('batch-payments.pending')->with('error', 'Pembayaran ini sudah diproses.');
        }

        $batchPayment->load('client');
        $details = $batchPayment->details ?? [];
        $invoiceIds = $details['invoice_ids'] ?? [];

        $invoices = SalesInvoice::whereIn('invoice_id', $invoiceIds)
            ->with(['deductingReturns', 'adjustments'])
            ->get();

        $salesUser = !empty($details['sales_receiver_id'])
            ? User::find($details['sales_receiver_id'])
            : null;

        return view('batch_payments.show_pending', compact('batchPayment', 'invoices', 'details', 'salesUser'));
    }

    public function approve(BatchPayment $batchPayment): RedirectResponse
    {
        if ($batchPayment->status !== 'pending_verification') {
            return redirect()->route('batch-payments.pending')->with('error', 'Pembayaran ini sudah diproses.');
        }

        DB::beginTransaction();
        try {
            $client = $batchPayment->client;
            $details = $batchPayment->details ?? [];
            $invoiceIds = $details['invoice_ids'] ?? [];

            $danaDariInput = (float) $batchPayment->total_amount;
            $kreditAkanDigunakan = (float) ($details['credit_amount_to_use'] ?? 0);
            
            $invoices = SalesInvoice::whereIn('invoice_id', $invoiceIds)
                            ->with(['deductingReturns', 'adjustments'])
                            ->orderBy('due_date', 'asc')
                            ->get();
            
            // Hitung total tagihan asli (hanya untuk cek overpayment nanti)
            $totalTagihanTerpilih = $invoices->reduce(fn($carry, $inv) => $carry + $inv->remaining_balance, 0.0);

            $sisaKredit = $kreditAkanDigunakan;
            $sisaInput = $danaDariInput;
            $alokasiLog = [];

            // 1. Buat entri Ledger untuk KREDIT YANG DIPAKAI
            if ($kreditAkanDigunakan > 0) {
                ClientLedger::create([
                    'client_id' => $client->client_id,
                    'reference_type' => BatchPayment::class,
                    'reference_id' => $batchPayment->batch_payment_id,
                    'transaction_date' => $batchPayment->payment_date,
                    'type' => 'debit',
                    'amount' => -$kreditAkanDigunakan,
                    'status' => 'available',
                    'description' => 'Disetujui dari Pembayaran Batch #' . $batchPayment->batch_payment_id,
                    'user_id' => Auth::id(),
                ]);
                $alokasiLog[] = "Menggunakan kredit Rp " . number_format($kreditAkanDigunakan);
            }
            if ($danaDariInput > 0) $alokasiLog[] = "Menggunakan dana input Rp " . number_format($danaDariInput);

            // 2. Alokasikan dana ke invoice
            foreach ($invoices as $invoice) {
                if ($sisaKredit <= 0.01 && $sisaInput <= 0.01) break;
                
                // Ambil sisa tagihan SEBELUM alokasi
                $sisaTagihanInvoice = $invoice->remaining_balance;
                if ($sisaTagihanInvoice <= 0.01) continue;

                $bayarDariKredit = min($sisaTagihanInvoice, $sisaKredit);
                $bayarDariInput = min($sisaTagihanInvoice - $bayarDariKredit, $sisaInput);
                $jumlahBayar = $bayarDariKredit + $bayarDariInput;

                if ($jumlahBayar <= 0.01) continue;
                
                $metodePayment = [];
                if ($bayarDariKredit > 0) $metodePayment[] = 'Kredit Klien';
                if ($bayarDariInput > 0) $metodePayment[] = $batchPayment->payment_method;
                $metodeGabungInvoice = implode(' + ', $metodePayment) ?: 'N/A';

                $invoice->payments()->create([
                    'batch_payment_id' => $batchPayment->batch_payment_id,
                    'payment_date' => $batchPayment->payment_date,
                    'amount' => $jumlahBayar,
                    'payment_method' => $metodeGabungInvoice,
                    'received_by_user_id' => $details['sales_receiver_id'] ?? Auth::id(),
                    'status' => 'completed',
                    'notes' => 'Disetujui dari Batch Payment #' . $batchPayment->batch_payment_id,
                    'proof_of_payment_path' => $details['proof_path'] ?? null
                ]);

                // === PERBAIKAN LOGIKA STATUS ===
                // Update amount_paid dulu
                $invoice->increment('amount_paid', $jumlahBayar);
                
                // Refresh model untuk mendapatkan remaining_balance yang baru
                $invoice->refresh(); 
                
                $newStatus = 'partially_paid';
                if ($invoice->remaining_balance <= 0.01) {
                    $newStatus = 'paid';
                }
                
                // Update status
                $invoice->update(['status' => $newStatus]);
                
                // === TAMBAHAN: LEPASKAN KREDIT PENDING ===
                if ($newStatus == 'paid') {
                    ClientLedger::where('sales_invoice_id', $invoice->invoice_id)
                                ->where('status', 'pending')
                                ->update([
                                    'status' => 'available',
                                    'description' => DB::raw("REPLACE(description, ' (Ditahan)', '')")
                                ]);
                }
                // === AKHIR PERBAIKAN ===

                $sisaKredit -= $bayarDariKredit;
                $sisaInput -= $bayarDariInput;
            }

            // Overpayment kembali ke kredit
            $sisaDana = $sisaKredit + $sisaInput;
            if ($sisaDana > 0.01) {
                ClientLedger::create([
                    'client_id' => $client->client_id,
                    'reference_type' => BatchPayment::class,
                    'reference_id' => $batchPayment->batch_payment_id,
                    'transaction_date' => $batchPayment->payment_date,
                    'type' => 'credit',
                    'amount' => $sisaDana,
                    'status' => 'available',
                    'description' => 'Kelebihan dana dari Batch Payment #' . $batchPayment->batch_payment_id,
                    'user_id' => Auth::id(),
                ]);
                 $alokasiLog[] = "Kelebihan dana Rp " . number_format($sisaDana) . " dikembalikan ke kredit klien.";
            }

            $batchPayment->update([
                'status' => 'completed',
                'processed_by_user_id' => Auth::id(),
            ]);

            DB::commit();
            return redirect()->route('batch-payments.pending')->with('success', 'Batch payment berhasil disetujui.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal approve batch: ' . $e->getMessage() . ' on line ' . $e->getLine());
            return back()->with('error', 'Gagal menyetujui batch: ' . $e->getMessage());
        }
    }

    public function reject(BatchPayment $batchPayment): RedirectResponse
    {
        if ($batchPayment->status !== 'pending_verification') {
            return redirect()->route('batch-payments.pending')->with('error', 'Pembayaran ini sudah diproses.');
        }

        $batchPayment->update([
            'status' => 'rejected',
            'processed_by_user_id' => Auth::id(),
        ]);

        return redirect()->route('batch-payments.pending')->with('success', 'Pembayaran batch ditolak.');
    }
}
