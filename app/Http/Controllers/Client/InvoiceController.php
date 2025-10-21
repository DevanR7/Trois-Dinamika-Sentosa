<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Models\User;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    /**
     * Menampilkan daftar invoice milik klien yang sedang login.
     */
    public function index(Request $request): View // <-- Tambahkan Request
    {
        $client = Auth::guard('client')->user();
        
        $query = $client->salesInvoices(); // Basis query

        // --- Ambil Data untuk Dropdown Filter Tanggal ---
        $baseDateQuery = $client->salesInvoices(); // Query terpisah untuk data tanggal
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
        // 1. Filter Search (Nomor Invoice)
        if ($request->filled('search')) {
            $query->where('invoice_number', 'like', "%{$request->search}%");
        }

        // 2. Filter Tanggal Terbit (Bulan & Tahun)
        if ($request->filled('order_date_filter')) {
            $yearMonth = $request->order_date_filter; // Format YYYY-MM
            try {
                $date = Carbon::createFromFormat('Y-m', $yearMonth);
                $query->whereYear('order_date', $date->year)
                      ->whereMonth('order_date', $date->month);
            } catch (\Exception $e) { /* Abaikan */ }
        }
        
        // 3. Filter Tanggal Jatuh Tempo (Bulan & Tahun)
         if ($request->filled('due_date_filter')) {
            $yearMonth = $request->due_date_filter; // Format YYYY-MM
            try {
                $date = Carbon::createFromFormat('Y-m', $yearMonth);
                $query->whereYear('due_date', $date->year)
                      ->whereMonth('due_date', $date->month);
            } catch (\Exception $e) { /* Abaikan */ }
        }
        
        // 4. Filter Status
        if ($request->filled('status_filter')) {
             $query->where('status', $request->status_filter);
        }

        // 5. Pengurutan
        $sort = $request->get('sort', 'terbaru'); // Default terbaru
        if ($sort === 'terlama') {
            $query->orderBy('order_date', 'asc');
        } else {
            $query->orderBy('order_date', 'desc');
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
        // Keamanan: Pastikan invoice ini benar-benar milik klien yang sedang login.
        if ($invoice->client_id !== Auth::guard('client')->id()) {
            abort(403, 'Akses Ditolak');
        }

        // Ambil semua data terkait yang dibutuhkan untuk tampilan
        $invoice->load(['items.product', 'taxes', 'payments']);
        $salesUsers = User::role('sales')->get();

        return view('client.invoices.show', compact('invoice','salesUsers'));
    }

    public function uploadProof(Request $request, SalesInvoice $invoice): RedirectResponse
    {
        if ($invoice->client_id !== Auth::guard('client')->id()) {
            abort(403);
        }

        $sisaTagihan = $invoice->total_amount - $invoice->amount_paid;

        // Validasi yang disempurnakan
        $validated = $request->validate([
            'payment_method'    => 'required|in:cash,manual_transfer',
            'payment_amount'    => "required|numeric|min:1|max:{$sisaTagihan}",
            'user_id_sales'     => 'required_if:payment_method,cash|exists:users,user_id',
            'proof_of_payment'  => 'required_if:payment_method,manual_transfer|image|mimes:jpeg,png,jpg|max:2048',
            'notes'             => 'nullable|string',
        ]);

        $path = null;
        if ($request->hasFile('proof_of_payment')) {
            $path = $request->file('proof_of_payment')->store('payment_proofs', 'public');
        }

        $invoice->payments()->create([
            'payment_date'          => now(),
            'amount'                => $validated['payment_amount'],
            'payment_method'        => $validated['payment_method'],
            'proof_of_payment_path' => $path,
            'status'                => 'pending_verification',
            'received_by_user_id'   => $validated['user_id_sales'] ?? null, // Simpan ID sales jika metodenya cash
            'notes'                 => $validated['notes'],
        ]);

        return back()->with('success', 'Informasi pembayaran berhasil dikirim dan sedang menunggu verifikasi.');
    }
}