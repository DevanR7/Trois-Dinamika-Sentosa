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
use Illuminate\Validation\Rule; // <-- Pastikan ini ada

class BatchPaymentController extends Controller
{
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
                // Pastikan sisa tagihan tidak negatif karena pembulatan atau retur > sisa
                'sisa_tagihan' => max(0, $sisaTagihan),
            ];
        })->filter(fn($invoice) => $invoice['sisa_tagihan'] > 0.01); // Filter jika sisa sangat kecil/nol

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
            // ✅ VALIDASI DIPERBARUI
            'payment_method' => [
                'required_unless:total_amount,0', // Wajib kecuali total_amount adalah 0
                'nullable', // Boleh null jika tidak wajib
                'string',   // Harus string jika diisi
            ],
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

            // Jika tidak ada tagihan terpilih atau total 0, lempar error
            if ($totalTagihanTerpilih <= 0.01) {
                 throw new \Exception("Tidak ada tagihan yang dipilih atau semua invoice terpilih sudah lunas.");
            }

            // Tentukan berapa kredit yang AKAN digunakan
            $kreditAkanDigunakan = 0;
            if ($pakaiKredit && $kreditAwalKlien > 0) {
                // Gunakan kredit maksimal sejumlah tagihan terpilih atau sisa kredit
                $kreditAkanDigunakan = min($kreditAwalKlien, $totalTagihanTerpilih);
            }

            // Tentukan berapa dana input yang AKAN digunakan
            // Sisa tagihan setelah dikurangi kredit yg akan dipakai
            $sisaTagihanSetelahKredit = max(0, $totalTagihanTerpilih - $kreditAkanDigunakan);
            // Gunakan dana input maksimal sejumlah sisa tagihan tsb atau dana input yg ada
            $danaInputAkanDigunakan = min($danaDariInput, $sisaTagihanSetelahKredit);

            // Hitung total dana yang akan dialokasikan (dari kredit + input yg digunakan)
            $totalDanaAlokasi = $kreditAkanDigunakan + $danaInputAkanDigunakan;

            // Jika total dana alokasi 0 (misal input 0, kredit 0/tidak dipakai/tidak cukup), lempar error
             if ($totalDanaAlokasi <= 0.01) {
                 throw new \Exception("Tidak ada dana (input/kredit) yang cukup untuk dialokasikan ke tagihan terpilih.");
             }

            // Hitung sisa dana input YANG TIDAK TERPAKAI (untuk dikembalikan ke kredit)
            $sisaDanaInput = max(0, $danaDariInput - $danaInputAkanDigunakan);

            // --- Mulai Proses Database ---

            // 1. Kurangi kredit klien (jika dipakai)
            if ($kreditAkanDigunakan > 0) {
                // Pastikan tidak mengurangi lebih dari yang ada (pencegahan race condition)
                $client->where('client_id', $validated['client_id'])
                       ->where('credit_balance', '>=', $kreditAkanDigunakan)
                       ->decrement('credit_balance', $kreditAkanDigunakan);
                // Reload client untuk mendapatkan balance terbaru
                $client->refresh();
            }

            // 2. Buat record t
            $metodeBatch = '';
            if ($kreditAkanDigunakan > 0) $metodeBatch .= 'Kredit Klien';
            if ($danaInputAkanDigunakan > 0) {
                 if (!empty($metodeBatch)) $metodeBatch .= ' + ';
                 $metodeBatch .= $validated['payment_method']; // Ambil dari input
            }
             if (empty($metodeBatch)) $metodeBatch = 'N/A'; // Fallback

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

                // Reload invoice data just in case? (Optional, might impact performance)
                // $invoice->refresh();

                $totalRetur = $invoice->returns_sum_total_amount ?? $invoice->returns->sum('total_amount'); // Ambil dari hasil load atau hitung jika belum
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
                     // Pastikan ada nilai default jika payment_method null (misal saat dana input 0)
                     $metodePayment .= $validated['payment_method'] ?? 'N/A';
                 }
                 if (empty($metodePayment)) $metodePayment = 'N/A'; // Fallback

                // Buat record Payment
                $invoice->payments()->create([
                    'batch_payment_id' => $batchPayment->batch_payment_id,
                    'payment_date' => $validated['payment_date'],
                    'amount' => $jumlahUntukInvoiceIni,
                    'payment_method' => $metodePayment,
                    'received_by_user_id' => Auth::id(),
                    'status' => 'completed', // Pembayaran dari admin selalu completed
                    'notes' => 'Auto-allocated from Batch Payment #' . $batchPayment->batch_payment_id,
                ]);

                // Update status SalesInvoice
                // Penting: Ambil ulang amount_paid dari DB sebelum update
                // untuk mencegah race condition jika ada pembayaran lain bersamaan
                $invoiceCurrent = SalesInvoice::find($invoice->invoice_id); // Ambil data terbaru
                $totalPaidBaru = ($invoiceCurrent->amount_paid ?? 0) + $jumlahUntukInvoiceIni; // Gunakan data terbaru
                $sisaTagihanBaru = $invoiceCurrent->total_amount - $totalPaidBaru - $totalRetur;

                $invoiceCurrent->update([
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
                 // Gunakan increment untuk atomicity
                 $client->where('client_id', $validated['client_id'])
                        ->increment('credit_balance', $sisaDanaInput);
                $alokasiLog[] = "Sisa dana input Rp " . number_format($sisaDanaInput) . " dikembalikan ke kredit klien.";
            }

            DB::commit();

            $message = 'Pembayaran batch berhasil. Detail: ' . implode('. ', $alokasiLog);

            // Redirect ke halaman detail klien untuk melihat update kredit & status invoice
            return redirect()->route('clients.show', $client->client_id)->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            // Log error jika perlu: Log::error($e->getMessage());
            return back()->with('error', 'Gagal menyimpan pembayaran batch: ' . $e->getMessage())->withInput();
        }
    }
}