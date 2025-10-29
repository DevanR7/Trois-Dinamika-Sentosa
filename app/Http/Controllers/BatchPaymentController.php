<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\SalesInvoice;
use App\Models\BatchPayment;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class BatchPaymentController extends Controller
{   
    public function __construct()
    {
        $this->middleware('can:create-batch-payments');
    }

    /**
     * Tampilkan halaman form untuk membuat batch payment.
     */
    public function create(): View
    {
        $clients = Client::orderBy('client_name')->get();
        return view('batch_payments.create', compact('clients'));
    }

    /**
     * [API] Ambil data invoice yang belum lunas milik klien.
     */
    public function getUnpaidInvoicesApi(Client $client): JsonResponse
    {
        $invoices = $client->salesInvoices()
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->withSum('returns', 'total_amount') // Hitung total retur
            ->orderBy('due_date', 'asc') // Urutkan dari paling lama
            ->get();

        // Hitung sisa tagihan untuk setiap invoice
        $invoicesWithBalance = $invoices->map(function ($invoice) {
            $totalRetur = $invoice->returns_sum_total_amount ?? 0;
            $sisaTagihan = $invoice->total_amount - $invoice->amount_paid - $totalRetur;
            
            // Hanya kembalikan data yang perlu
            return [
                'invoice_id' => $invoice->invoice_id,
                'invoice_number' => $invoice->invoice_number,
                'due_date_formatted' => $invoice->due_date->format('d M Y'),
                'total_amount' => $invoice->total_amount,
                'amount_paid' => $invoice->amount_paid,
                'total_retur' => $totalRetur,
                'sisa_tagihan' => $sisaTagihan,
            ];
        })->filter(fn($invoice) => $invoice['sisa_tagihan'] > 0); // Filter jika ada yg sisa 0 tapi status belum update

        return response()->json($invoicesWithBalance);
    }

    /**
     * Simpan batch payment dan alokasikan ke invoice-invoice.
     */
    public function store(Request $request): RedirectResponse
    {
        // Validasi: Dana input boleh 0, metode tidak wajib jika dana input 0
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,client_id',
            'payment_date' => 'required|date',
            'total_amount' => 'required|numeric|min:0', // Boleh 0
            'payment_method' => 'required_if:total_amount,>,0|string|nullable', // Wajib jika dana > 0
            'notes' => 'nullable|string',
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'required|exists:sales_invoices,invoice_id',
            'use_credit' => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            $client = Client::findOrFail($validated['client_id']);
            $danaDariInput = (float)($validated['total_amount'] ?? 0);
            $pakaiKredit = $validated['use_credit'] ?? false;
            $kreditAwalKlien = (float)($client->credit_balance ?? 0);

            // Ambil invoice terpilih untuk hitung total tagihan
            $invoicesDipilih = SalesInvoice::whereIn('invoice_id', $validated['invoice_ids'])
                                ->withSum('returns', 'total_amount')
                                ->orderBy('due_date', 'asc') // Penting untuk alokasi
                                ->get();

            $totalTagihanTerpilih = $invoicesDipilih->reduce(function ($carry, $invoice) {
                $retur = $invoice->returns_sum_total_amount ?? 0;
                $sisa = max(0, $invoice->total_amount - $invoice->amount_paid - $retur);
                return $carry + $sisa;
            }, 0.0);

            // Jika tidak ada tagihan terpilih, lempar error
            if ($totalTagihanTerpilih <= 0.01) {
                 throw new \Exception("Tidak ada tagihan yang dipilih atau semua sudah lunas.");
            }

            // Tentukan berapa kredit yang AKAN digunakan
            $kreditAkanDigunakan = 0;
            if ($pakaiKredit && $kreditAwalKlien > 0) {
                $kreditAkanDigunakan = min($kreditAwalKlien, $totalTagihanTerpilih);
            }

            // Tentukan berapa dana input yang AKAN digunakan
            $sisaTagihanSetelahKredit = max(0, $totalTagihanTerpilih - $kreditAkanDigunakan);
            $danaInputAkanDigunakan = min($danaDariInput, $sisaTagihanSetelahKredit);

            // Hitung total dana yang akan dialokasikan
            $totalDanaAlokasi = $kreditAkanDigunakan + $danaInputAkanDigunakan;

            // Jika total dana alokasi 0 (misal input 0, kredit 0/tidak dipakai), lempar error
             if ($totalDanaAlokasi <= 0.01) {
                 throw new \Exception("Tidak ada dana (input/kredit) yang cukup untuk dialokasikan ke tagihan terpilih.");
             }

            // Hitung sisa dana input YANG TIDAK TERPAKAI
            $sisaDanaInput = max(0, $danaDariInput - $danaInputAkanDigunakan);

            // --- Mulai Proses Database ---

            // 1. Kurangi kredit klien (jika dipakai)
            if ($kreditAkanDigunakan > 0) {
                $client->decrement('credit_balance', $kreditAkanDigunakan);
            }

            // 2. Buat record BatchPayment
            $metodeBatch = '';
            if ($kreditAkanDigunakan > 0) $metodeBatch .= 'Kredit Klien';
            if ($danaInputAkanDigunakan > 0) {
                 if (!empty($metodeBatch)) $metodeBatch .= ' + ';
                 $metodeBatch .= $validated['payment_method'];
            }
             if (empty($metodeBatch)) $metodeBatch = 'N/A'; // Kasus aneh jika terjadi

            $batchPayment = BatchPayment::create([
                'client_id' => $validated['client_id'],
                'processed_by_user_id' => Auth::id(),
                'payment_date' => $validated['payment_date'],
                'total_amount' => $totalDanaAlokasi, // Total yang benar-benar dialokasikan
                'payment_method' => $metodeBatch,
                'notes' => $validated['notes'],
            ]);

            // 3. Alokasikan dana ke invoice
            $sisaKreditUntukAlokasi = $kreditAkanDigunakan;
            $sisaInputUntukAlokasi = $danaInputAkanDigunakan;
            $alokasiLog = [];
            if ($kreditAkanDigunakan > 0) $alokasiLog[] = "Menggunakan kredit Rp " . number_format($kreditAkanDigunakan);
             if ($danaInputAkanDigunakan > 0) $alokasiLog[] = "Menggunakan dana input Rp " . number_format($danaInputAkanDigunakan);


            foreach ($invoicesDipilih as $invoice) {
                // Jika kedua sisa dana sudah habis, hentikan loop
                if ($sisaKreditUntukAlokasi <= 0.01 && $sisaInputUntukAlokasi <= 0.01) break;

                $totalRetur = $invoice->returns_sum_total_amount ?? 0; // Ambil dari hasil load
                $sisaTagihanInvoice = max(0, $invoice->total_amount - $invoice->amount_paid - $totalRetur);

                if ($sisaTagihanInvoice <= 0.01) continue; // Skip jika sudah lunas

                // Prioritaskan alokasi dari kredit
                $bayarDariKredit = min($sisaTagihanInvoice, $sisaKreditUntukAlokasi);
                $sisaTagihanSetelahKreditInvoice = max(0, $sisaTagihanInvoice - $bayarDariKredit);

                // Alokasi dari dana input
                $bayarDariInput = min($sisaTagihanSetelahKreditInvoice, $sisaInputUntukAlokasi);

                // Total pembayaran untuk invoice ini
                $jumlahUntukInvoiceIni = $bayarDariKredit + $bayarDariInput;

                // Jangan proses jika jumlah bayar 0
                if ($jumlahUntukInvoiceIni <= 0.01) continue;

                // Tentukan metode pembayaran untuk log Payment individual
                 $metodePayment = '';
                 if ($bayarDariKredit > 0) $metodePayment .= 'Kredit Klien';
                 if ($bayarDariInput > 0) {
                     if (!empty($metodePayment)) $metodePayment .= ' + ';
                     $metodePayment .= $validated['payment_method'];
                 }

                // Buat record Payment
                $invoice->payments()->create([
                    'batch_payment_id' => $batchPayment->batch_payment_id,
                    'payment_date' => $validated['payment_date'],
                    'amount' => $jumlahUntukInvoiceIni,
                    'payment_method' => $metodePayment,
                    'received_by_user_id' => Auth::id(),
                    'status' => 'completed',
                    'notes' => 'Auto-allocated from Batch Payment #' . $batchPayment->batch_payment_id,
                ]);

                // Update status SalesInvoice
                $totalPaidBaru = $invoice->amount_paid + $jumlahUntukInvoiceIni;
                $sisaTagihanBaru = $invoice->total_amount - $totalPaidBaru - $totalRetur;

                $invoice->update([
                    'amount_paid' => $totalPaidBaru,
                    'status' => ($sisaTagihanBaru <= 0.01) ? 'paid' : 'partially_paid',
                ]);

                // Kurangi sisa dana yang tersedia untuk alokasi
                $sisaKreditUntukAlokasi -= $bayarDariKredit;
                $sisaInputUntukAlokasi -= $bayarDariInput;
                $alokasiLog[] = "Rp " . number_format($jumlahUntukInvoiceIni) . " dialokasikan ke " . $invoice->invoice_number;
            }

            // 4. Tambahkan sisa dana INPUT (jika ada) kembali ke kredit klien
            if ($sisaDanaInput > 0.01) {
                $client->increment('credit_balance', $sisaDanaInput);
                $alokasiLog[] = "Sisa dana input Rp " . number_format($sisaDanaInput) . " dikembalikan ke kredit klien.";
            }

            DB::commit();

            $message = 'Pembayaran batch berhasil. Detail: ' . implode('. ', $alokasiLog);

            // Redirect ke halaman index invoice atau halaman detail klien
            return redirect()->route('clients.show', $client->client_id)->with('success', $message); // Redirect ke detail klien

        } catch (\Exception $e) {
            DB::rollBack();
            // Log error jika perlu: Log::error(...)
            return back()->with('error', 'Gagal menyimpan pembayaran batch: ' . $e->getMessage())->withInput();
        }
    }
}