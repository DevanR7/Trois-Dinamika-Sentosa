<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoice;
use App\Models\Payment;
use App\Models\ClientLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Models\User;
use Carbon\Carbon;
use App\Models\BatchPayment;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    /**
     * Menampilkan daftar invoice milik klien yang sedang login.
     */
    public function index(Request $request): View
    {
        $client = Auth::guard('client')->user();
        
        $query = $client->salesInvoices()->with([
            'deductingReturns', 
            'adjustments'       
        ]);

        // --- Ambil Data untuk Dropdown Filter Tanggal ---
        $baseDateQuery = $client->salesInvoices(); 
        $uniqueOrderDates = (clone $baseDateQuery)
            ->select(DB::raw("DATE_FORMAT(order_date, '%Y-%m') as ym"))
            ->distinct()->orderBy('ym', 'desc')->get()->mapWithKeys(function ($item) {
                return [$item->ym => Carbon::createFromFormat('Y-m', $item->ym)->isoFormat('MMMM YYYY')];
            });
        
        $uniqueDueDates = (clone $baseDateQuery)
            ->select(DB::raw("DATE_FORMAT(due_date, '%Y-%m') as ym"))
            ->distinct()->orderBy('ym', 'desc')->get()->mapWithKeys(function ($item) {
                 return [$item->ym => Carbon::createFromFormat('Y-m', $item->ym)->isoFormat('MMMM YYYY')];
            });

        // --- Terapkan Filter ---
        if ($request->filled('search')) {
            $query->where('invoice_number', 'like', "%{$request->search}%");
        }
        if ($request->filled('order_date_filter')) {
            $yearMonth = $request->order_date_filter;
            try {
                $date = Carbon::createFromFormat('Y-m', $yearMonth);
                $query->whereYear('order_date', $date->year)
                      ->whereMonth('order_date', $date->month);
            } catch (\Exception $e) { /* Abaikan */ }
        }
         if ($request->filled('due_date_filter')) {
            $yearMonth = $request->due_date_filter;
            try {
                $date = Carbon::createFromFormat('Y-m', $yearMonth);
                $query->whereYear('due_date', $date->year)
                      ->whereMonth('due_date', $date->month);
            } catch (\Exception $e) { /* Abaikan */ }
        }
        if ($request->filled('status_filter')) {
             $query->where('status', $request->status_filter);
        }

        // --- Pengurutan ---
        $sort = $request->get('sort', 'terbaru'); 
        if ($sort === 'terlama') {
            $query->orderBy('order_date', 'asc')->orderBy('invoice_id', 'asc');
        } else {
            $query->orderBy('order_date', 'desc')->orderBy('invoice_id', 'desc');
        }

        $invoices = $query->paginate(15)->appends($request->query());
                                        
        return view('client.invoices.index', compact(
            'invoices', 
            'uniqueOrderDates', 
            'uniqueDueDates'
        ));
    }

    /**
     * Menampilkan detail satu invoice milik klien.
     */
    public function show(SalesInvoice $invoice): View
    {
        if ($invoice->client_id !== Auth::guard('client')->id()) {
            abort(403, 'Akses Ditolak');
        }

        $invoice->load([
            'items.product', 
            'taxes', 
            'payments.receivedBy',
            'deductingReturns',
            'adjustments.user',
            'returns.items.product' 
        ]);
        
        $salesUsers = User::role('sales')->get();

        return view('client.invoices.show', compact('invoice','salesUsers'));
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

        return view('client.invoices.batch_pay', compact(
            'invoices', 
            'availableBalance', 
            'pendingBalance'
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

        $validated = $request->validate([
            'payment_method'    => 'required|in:cash,manual_transfer',
            'payment_amount'    => "required|numeric|min:0",
            'use_credit'        => 'nullable|boolean',
            'user_id_sales'     => 'required_if:payment_method,cash|exists:users,user_id',
            'proof_of_payment'  => 'required_if:payment_method,manual_transfer|image|mimes:jpeg,png,jpg|max:2048',
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

            if ($creditToUse > 0) {
                $uniqueTransactionId = 'CREDIT-' . time() . '-' . $invoice->invoice_id;
                Payment::create([
                    'invoice_id'            => $invoice->invoice_id,
                    'payment_date'          => now(),
                    'amount'                => $creditToUse,
                    'payment_method'        => 'Kredit Klien',
                    'transaction_id'        => $uniqueTransactionId,
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
                $invoice->increment('amount_paid', $creditToUse);
            }

            if ($amountFromInput > 0) {
                $invoice->payments()->create([
                    'payment_date'          => now(),
                    'amount'                => $validated['payment_amount'],
                    'payment_method'        => $validated['payment_method'],
                    'proof_of_payment_path' => $path,
                    'status'                => 'pending_verification',
                    'received_by_user_id'   => $validated['user_id_sales'] ?? null,
                    'notes'                 => $validated['notes'],
                ]);
            }

            $invoice->refresh();
            if ($invoice->remaining_balance <= 0.01) {
                $invoice->update(['status' => 'paid']);
                ClientLedger::where('sales_invoice_id', $invoice->invoice_id)
                            ->where('status', 'pending')
                            ->update([
                                'status' => 'available',
                                'description' => DB::raw("REPLACE(description, ' (Ditahan)', '')")
                            ]);
            } elseif ($invoice->amount_paid > 0) {
                 $invoice->update(['status' => 'partially_paid']);
            }

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
        
        $validated = $request->validate([
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'exists:sales_invoices,invoice_id',
            'payment_method'    => 'required|in:cash,manual_transfer',
            'payment_amount'    => "required|numeric|min:0",
            'use_credit'        => 'nullable|boolean',
            'user_id_sales'     => 'required_if:payment_method,cash|exists:users,user_id',
            'proof_of_payment'  => 'required_if:payment_method,manual_transfer|image|mimes:jpeg,png,jpg|max:2048',
            'notes'             => 'nullable|string',
        ]);

        DB::beginTransaction(); 
        try {
            $path = null;
            if ($request->hasFile('proof_of_payment')) {
                $path = $request->file('proof_of_payment')->store('payment_proofs', 'public');
            }

            // ======================================================
            // ✅ PERBAIKAN 1: 'validated' -> 'invoice_id'
            // ======================================================
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

            // ======================================================
            // ✅ PERBAIKAN 2: Hapus json_encode()
            // ======================================================
            BatchPayment::create([
                'client_id' => $client->client_id,
                'processed_by_user_id' => null,
                'payment_date' => now(),
                'total_amount' => $validated['payment_amount'],
                'payment_method' => $validated['payment_method'],
                'status' => 'pending_verification',
                'notes' => $validated['notes'],
                'details' => [ // <-- Berikan sebagai array PHP
                    'invoice_ids' => $validated['invoice_ids'],
                    'total_tagihan_dipilih' => $totalSisaTagihan,
                    'use_credit' => $useCredit,
                    'credit_amount_to_use' => $kreditAkanDigunakan,
                    'proof_path' => $path,
                    'sales_receiver_id' => $validated['user_id_sales'] ?? null
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