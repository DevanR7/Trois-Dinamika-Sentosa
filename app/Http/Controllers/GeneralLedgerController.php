<?php

namespace App\Http\Controllers;

use App\Models\GeneralLedger;
use App\Models\ChartOfAccount;
use App\Models\SalesInvoice;
use App\Models\PurchaseOrder;
use App\Models\Expense; // Opsional: jika ada relasi di Expense
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class GeneralLedgerController extends Controller
{
    public function __construct()
    {
        // Pastikan hanya user dengan izin 'view-reports' yang bisa akses
        $this->middleware('can:view-reports');
    }

    /**
     * Menampilkan laporan Jurnal Umum (Buku Besar).
     */
    public function index(Request $request): View
    {
        // 1. Optimasi Query dengan Eager Loading
        // Kita gunakan 'morphWith' agar Laravel otomatis meload relasi anak 
        // berdasarkan tipe referensinya (Invoice -> Client, PO -> Supplier).
        $query = GeneralLedger::with([
            'account', 
            'user',
            'reference' => function (MorphTo $morphTo) {
                $morphTo->morphWith([
                    SalesInvoice::class => ['client'],   // Load 'client' jika referensi adalah SalesInvoice
                    PurchaseOrder::class => ['supplier'], // Load 'supplier' jika referensi adalah PurchaseOrder
                    // Tambahkan model lain di sini jika perlu, contoh:
                    // Expense::class => ['category'], 
                ]);
            }
        ])
        ->orderBy('entry_date', 'desc')
        ->orderBy('journal_group_id', 'desc');

        // 2. Filter Tanggal
        if ($request->filled('start_date')) {
            $query->where('entry_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('entry_date', '<=', $request->end_date);
        }

        // 3. Filter Akun Spesifik
        if ($request->filled('account_id')) {
            $query->where('chart_of_account_id', $request->account_id);
        }
        
        // 4. Filter Grup Jurnal (Pencarian per ID Transaksi, misal: INV-2023-001)
        if ($request->filled('journal_group_id')) {
            $query->where('journal_group_id', 'like', '%' . $request->journal_group_id . '%');
        }

        // 5. Eksekusi Pagination
        // 'appends' menjaga agar parameter filter tetap ada saat klik halaman berikutnya
        $journalEntries = $query->paginate(50)->appends($request->query());

        // 6. Data Pendukung untuk Filter di View
        $accounts = ChartOfAccount::where('is_active', true)
                        ->orderBy('account_number')
                        ->get();

        return view('reports.general-ledger', compact(
            'journalEntries', 
            'accounts'
        ));
    }
}