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
use Illuminate\Validation\Rule;
use App\Models\ClientLedger;

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
            ->withSum('returns', 'total_amount')
            ->orderBy('due_date', 'asc')
            ->get();

        $invoicesWithBalance = $invoices->map(function ($invoice) {
            $totalRetur = $invoice->returns_sum_total_amount ?? 0;
            $sisaTagihan = $invoice->total_amount - $invoice->amount_paid - $totalRetur;
            return [
                'invoice_id' => $invoice->invoice_id,
                'invoice_number' => $invoice->invoice_number,
                'due_date_formatted' => $invoice->due_date->format('d M Y'),
                'total_amount' => $invoice->total_amount,
                'amount_paid' => $invoice->amount_paid,
                'total_retur' => $totalRetur,
                'sisa_tagihan' => max(0, $sisaTagihan),
            ];
        })->filter(fn($invoice) => $invoice['sisa_tagihan'] > 0.01);

        return response()->json($invoicesWithBalance);
    }

    /**
     * Simpan batch payment dan alokasikan ke invoice-invoice.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,client_id',
            'payment_date' => 'required|date',
            'total_amount' => 'required|numeric|min:0',
            'payment_method' => [
                'required_unless:total_amount,0',
                'nullable',
                'string',
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

            // ✅ Gunakan accessor balance dari Client
            $kreditAwalKlien = $client->balance;

            // Ambil invoice yang dipilih
            $invoicesDipilih = SalesInvoice::whereIn('invoice_id', $validated['invoice_ids'])
                                // ✅ Gunakan relasi 'deductingReturns' yang sudah kita buat
                                ->withSum('deductingReturns', 'total_amount')
                                ->orderBy('due_date', 'asc')
                                ->get();
                                
            $totalTagihanTerpilih = $invoicesDipilih->reduce(function ($carry, $invoice) {
                // ✅ Gunakan hasil sum yang sudah benar
                $retur = $invoice->deducting_returns_sum_total_amount ?? 0;
                $sisa = max(0, $invoice->total_amount - $invoice->amount_paid - $retur);
                return $carry + $sisa;
            }, 0.0);

            if ($totalTagihanTerpilih <= 0.01) {
                throw new \Exception("Tidak ada tagihan yang dipilih atau semua sudah lunas.");
            }

            // Hitung berapa kredit yang akan digunakan
            $kreditAkanDigunakan = 0;
            if ($pakaiKredit && $kreditAwalKlien > 0) {
                $kreditAkanDigunakan = min($kreditAwalKlien, $totalTagihanTerpilih);
            }
            $sisaTagihanSetelahKredit = max(0, $totalTagihanTerpilih - $kreditAkanDigunakan);
            $danaInputAkanDigunakan = min($danaDariInput, $sisaTagihanSetelahKredit);
            $totalDanaAlokasi = $kreditAkanDigunakan + $danaInputAkanDigunakan;
            $sisaDanaInput = max(0, $danaDariInput - $danaInputAkanDigunakan); // Overpayment

             if ($totalDanaAlokasi <= 0.01 && $sisaDanaInput <= 0.01) {
                 if ($totalTagihanTerpilih > 0.01) {
                    throw new \Exception("Tidak ada dana (input/kredit) yang cukup untuk dialokasikan.");
                 }
             }

            $sisaDanaInput = max(0, $danaDariInput - $danaInputAkanDigunakan);

            // --- Mulai proses database ---

            // 1. Buat BatchPayment
            $metodeBatch = '';
            if ($kreditAkanDigunakan > 0) $metodeBatch .= 'Kredit Klien';
            if ($danaInputAkanDigunakan > 0) {
                 if (!empty($metodeBatch)) $metodeBatch .= ' + ';
                 $metodeBatch .= $validated['payment_method'];
            }
            if (empty($metodeBatch)) $metodeBatch = 'N/A';

            $batchPayment = BatchPayment::create([
                'client_id' => $validated['client_id'],
                'processed_by_user_id' => Auth::id(),
                'payment_date' => $validated['payment_date'],
                'total_amount' => $totalDanaAlokasi, // Total yang dialokasikan
                'notes' => $validated['notes'],
                'payment_method' => $metodeBatch,
            ]);

            $alokasiLog = [];
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
                $alokasiLog[] = "Menggunakan kredit Rp " . number_format($kreditAkanDigunakan);
            }
            if ($danaInputAkanDigunakan > 0) $alokasiLog[] = "Menggunakan dana input Rp " . number_format($danaInputAkanDigunakan);

            // 3. Alokasikan ke invoice
            $sisaKreditUntukAlokasi = $kreditAkanDigunakan;
            $sisaInputUntukAlokasi = $danaInputAkanDigunakan;

            foreach ($invoicesDipilih as $invoice) {
                if ($sisaKreditUntukAlokasi <= 0.01 && $sisaInputUntukAlokasi <= 0.01) break;

                // Hitung ulang sisa tagihan pakai data yang sudah di-load
                $totalRetur = $invoice->deducting_returns_sum_total_amount ?? $invoice->deductingReturns->sum('total_amount');
                $sisaTagihanInvoice = max(0, $invoice->total_amount - $invoice->amount_paid - $totalRetur);

                if ($sisaTagihanInvoice <= 0.01) continue;

                $bayarDariKredit = min($sisaTagihanInvoice, $sisaKreditUntukAlokasi);
                $sisaTagihanSetelahKreditInvoice = max(0, $sisaTagihanInvoice - $bayarDariKredit);
                $bayarDariInput = min($sisaTagihanSetelahKreditInvoice, $sisaInputUntukAlokasi);
                $jumlahUntukInvoiceIni = $bayarDariKredit + $bayarDariInput;

                if ($jumlahUntukInvoiceIni <= 0.01) continue;

                $metodePayment = '';
                 if ($bayarDariKredit > 0) $metodePayment .= 'Kredit Klien';
                 if ($bayarDariInput > 0) {
                     if (!empty($metodePayment)) $metodePayment .= ' + ';
                     $metodePayment .= $validated['payment_method'] ?? 'N/A';
                 }
                if (empty($metodePayment)) $metodePayment = 'N/A';

                $invoice->payments()->create([
                    'batch_payment_id' => $batchPayment->batch_payment_id,
                    'payment_date' => $validated['payment_date'],
                    'amount' => $jumlahUntukInvoiceIni,
                    'payment_method' => $metodePayment,
                    'received_by_user_id' => Auth::id(),
                    'status' => 'completed',
                    'notes' => 'Auto-allocated from Batch Payment #' . $batchPayment->batch_payment_id,
                ]);

                $invoiceCurrent = SalesInvoice::find($invoice->invoice_id); // Ambil data terbaru
                $totalPaidBaru = ($invoiceCurrent->amount_paid ?? 0) + $jumlahUntukInvoiceIni;
                $sisaTagihanBaru = $invoiceCurrent->total_amount - $totalPaidBaru - $totalRetur;

                $newStatus = ($sisaTagihanBaru <= 0.01) ? 'paid' : 'partially_paid';

                $invoiceCurrent->update([
                    'amount_paid' => $totalPaidBaru,
                    'status' => $newStatus,
                ]);

                if ($newStatus == 'paid') {
                    ClientLedger::where('sales_invoice_id', $invoiceCurrent->invoice_id)
                                ->where('status', 'pending')
                                ->update([
                                    'status' => 'available',
                                    'description' => DB::raw("REPLACE(description, ' (Ditahan)', '')")
                                ]);
                }

                $sisaKreditUntukAlokasi -= $bayarDariKredit;
                $sisaInputUntukAlokasi -= $bayarDariInput;
                $alokasiLog[] = "Rp " . number_format($jumlahUntukInvoiceIni) . " dialokasikan ke " . $invoice->invoice_number;
            }

            // 4. Jika ada kelebihan dana input, masukkan ke kredit klien via ledger
            if ($sisaDanaInput > 0.01) {
                 ClientLedger::create([
                    'client_id' => $client->client_id,
                    'reference_type' => BatchPayment::class,
                    'reference_id' => $batchPayment->batch_payment_id,
                    'transaction_date' => $validated['payment_date'],
                    'type' => 'credit',
                    'amount' => $sisaDanaInput,
                    'status' => 'available', // Kelebihan bayar selalu 'available'
                    'description' => 'Kelebihan dana dari Pembayaran Batch #' . $batchPayment->batch_payment_id,
                    'user_id' => Auth::id(),
                 ]);
                $alokasiLog[] = "Sisa dana input Rp " . number_format($sisaDanaInput) . " dikembalikan ke kredit klien.";
            }

            DB::commit();
            $message = 'Pembayaran batch berhasil. Detail: ' . implode('. ', $alokasiLog);
            return redirect()->route('clients.show', $client->client_id)->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan pembayaran batch: ' . $e->getMessage())->withInput();
        }
    }
}
