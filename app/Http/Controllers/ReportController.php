<?php

namespace App\Http\Controllers;

// Model-model yang sudah ada
use App\Models\SalesInvoice;
use App\Models\PurchaseOrder;
use App\Models\SalesReturn;
use App\Models\PurchaseReturn;
use App\Models\Payment;
use App\Models\PurchaseOrderPayment;
use App\Models\Expense;
use App\Models\InvoiceItem;
use App\Models\LoanPayment;

// <-- TAMBAHKAN MODEL-MODEL BARU UNTUK NERACA -->
use App\Models\Product;
use App\Models\FixedAsset;
use App\Models\Loan;
use App\Models\EquityTransaction;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view-reports');
    }
    
    public function index(Request $request): View
    {
        // --- 1. PENGATURAN TANGGAL ---
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());
        $endDateCarbon = Carbon::parse($endDate)->endOfDay(); // Untuk filter neraca

        // =================================================================
        // LAPORAN LABA RUGI (PROFIT & LOSS) - PERIODE ($startDate s/d $endDate)
        // =================================================================
        
        $invoicesInPeriod = SalesInvoice::whereBetween('order_date', [$startDate, $endDate])
                            ->where('status', '!=', 'cancelled');
        
        $pendapatanKotor = $invoicesInPeriod->sum('subtotal');
        $totalDiskonPenjualan = $invoicesInPeriod->sum('discount_amount');
        $totalReturPenjualan = SalesReturn::whereBetween('return_date', [$startDate, $endDate])->sum('total_amount');
        $pendapatanNetto = ($pendapatanKotor - $totalDiskonPenjualan) - $totalReturPenjualan;

        $totalHPP = InvoiceItem::whereHas('salesInvoice', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('order_date', [$startDate, $endDate])
                  ->where('status', '!=', 'cancelled');
        })->sum(DB::raw('quantity * hpp'));

        $labaKotor = $pendapatanNetto - $totalHPP;
        $bebanDariExpenses = Expense::whereBetween('expense_date', [$startDate, $endDate])->sum('amount');
        $bebanBungaPinjaman = LoanPayment::whereBetween('payment_date', [$startDate, $endDate])->sum('interest_paid'); // <-- Ambil bunga dari cicilan
        $totalBebanOperasional = $bebanDariExpenses + $bebanBungaPinjaman;
        $labaBersih = $labaKotor - $totalBebanOperasional;


        // =================================================================
        // LAPORAN ARUS KAS (CASH FLOW) - PERIODE ($startDate s/d $endDate)
        // =================================================================

        // 1. Pemasukan (dari pembayaran invoice) - Tetap sama
        $pemasukan = Payment::whereBetween('payment_date', [$startDate, $endDate])->get();
        $totalPemasukan = $pemasukan->sum('amount');

        // 2. Pengeluaran (dari pembayaran PO) - Tetap sama
        $pengeluaranPO = PurchaseOrderPayment::whereBetween('payment_date', [$startDate, $endDate])->get();
        $totalPengeluaranPO = $pengeluaranPO->sum('amount');

        // 3. Pengeluaran (dari Beban Operasional) - Tetap sama
        $pengeluaranBeban = Expense::whereBetween('expense_date', [$startDate, $endDate])->get();
        $totalPengeluaranBeban = $pengeluaranBeban->sum('amount');

        // 4. Pengeluaran (dari Pembayaran Pinjaman) - [NEW] <-- TAMBAHKAN INI
        $pengeluaranPinjaman = LoanPayment::whereBetween('payment_date', [$startDate, $endDate])->get();
        $totalPengeluaranPinjaman = $pengeluaranPinjaman->sum('total_paid'); // <-- Ambil TOTAL bayar (pokok+bunga)

        // 5. Total Pengeluaran Kas - [UPDATED]
        $totalPengeluaran = $totalPengeluaranPO + $totalPengeluaranBeban + $totalPengeluaranPinjaman; // <-- Tambahkan pengeluaran pinjaman
        

        // =================================================================
        // LAPORAN NERACA (BALANCE SHEET) - SNAPSHOT PADA $endDate
        // =================================================================

        // --- A. ASET (ASSETS) ---
        
        // 1. Aset Lancar
        // Estimasi Kas (Total kas masuk - total kas keluar s/d $endDate)
        $kasMasukTotal = Payment::where('payment_date', '<=', $endDateCarbon)->sum('amount');
        $kasKeluarPoTotal = PurchaseOrderPayment::where('payment_date', '<=', $endDateCarbon)->sum('amount');
        $kasKeluarBebanTotal = Expense::where('expense_date', '<=', $endDateCarbon)->sum('amount');
        // TODO: Nanti tambahkan pengeluaran beli aset, bayar pinjaman, dan terima pinjaman
        $aset_kasDanBank = $kasMasukTotal - $kasKeluarPoTotal - $kasKeluarBebanTotal;

        // Piutang Usaha (Tagihan belum lunas saat ini)
        $laporanPiutang = SalesInvoice::with('client')
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->orderBy('due_date', 'asc')
            ->get();
        $aset_piutangUsaha = $laporanPiutang->sum(function($invoice) {
            return $invoice->total_amount - $invoice->amount_paid;
        });

        // Persediaan (Stok saat ini * HPP rata-rata)
        $aset_persediaan = Product::sum(DB::raw('stock_quantity * average_cost'));
        
        $totalAsetLancar = $aset_kasDanBank + $aset_piutangUsaha + $aset_persediaan;

        // 2. Aset Tetap (Nilai perolehan s/d $endDate, belum termasuk depresiasi)
        $aset_tetap = FixedAsset::where('purchase_date', '<=', $endDateCarbon)->sum('purchase_cost');
        $totalAsetTetap = $aset_tetap;

        // 3. TOTAL ASET
        $totalAset = $totalAsetLancar + $totalAsetTetap;

        // --- B. LIABILITAS (LIABILITIES) & EKUITAS (EQUITY) ---
        
        // 1. Liabilitas Lancar (Utang Usaha)
        $laporanUtang = PurchaseOrder::with('supplier')
            ->whereIn('payment_status', ['unpaid', 'partially_paid'])
            ->orderBy('due_date', 'asc')
            ->get();
        $liabilitas_utangUsaha = $laporanUtang->sum(function($po) {
            return $po->total_amount - $po->total_returned - $po->amount_paid;
        });
        $totalLiabilitasLancar = $liabilitas_utangUsaha;

        // 2. Liabilitas Jangka Panjang (Sisa pokok pinjaman)
        $liabilitas_utangJangkaPanjang = Loan::where('status', 'active')->sum('remaining_balance');
        $totalLiabilitasJangkaPanjang = $liabilitas_utangJangkaPanjang;

        // 3. Total Liabilitas
        $totalLiabilitas = $totalLiabilitasLancar + $totalLiabilitasJangkaPanjang;
        
        // 4. Ekuitas (Modal)
        // Modal Disetor (total investasi s/d $endDate)
        $ekuitas_modalDisetor = EquityTransaction::where('type', 'investment')
                                ->where('transaction_date', '<=', $endDateCarbon)
                                ->sum('amount');
        // Penarikan Modal (total prive/drawing s/d $endDate)
        $ekuitas_penarikanModal = EquityTransaction::where('type', 'drawing')
                                ->where('transaction_date', '<=', $endDateCarbon)
                                ->sum('amount');

        // Laba/Rugi Akumulasi (Total Laba/Rugi dari awal s/d $endDate)
        $totalPendapatanAkumulasi = SalesInvoice::where('order_date', '<=', $endDateCarbon)
                                    ->where('status', '!=', 'cancelled')
                                    ->sum(DB::raw('subtotal - discount_amount'));
        $totalReturAkumulasi = SalesReturn::where('return_date', '<=', $endDateCarbon)->sum('total_amount');
        $totalHppAkumulasi = InvoiceItem::whereHas('salesInvoice', function ($query) use ($endDateCarbon) {
                                    $query->where('order_date', '<=', $endDateCarbon)
                                          ->where('status', '!=', 'cancelled');
                                })->sum(DB::raw('quantity * hpp'));
        $totalBebanAkumulasi = Expense::where('expense_date', '<=', $endDateCarbon)->sum('amount');
        
        $ekuitas_labaRugiAkumulasi = ($totalPendapatanAkumulasi - $totalReturAkumulasi) - $totalHppAkumulasi - $totalBebanAkumulasi;

        // 5. Total Ekuitas
        $totalEkuitas = ($ekuitas_modalDisetor - $ekuitas_penarikanModal) + $ekuitas_labaRugiAkumulasi;

        // 6. TOTAL LIABILITAS & EKUITAS
        $totalLiabilitasDanEkuitas = $totalLiabilitas + $totalEkuitas;
        
        // =================================================================
        // MENGIRIM DATA KE VIEW
        // =================================================================
        
        return view('reports.index', compact(
            'startDate', 'endDate', 'endDateCarbon',
            
            // Laba Rugi - [UPDATED] Tambahkan detail beban bunga
            'pendapatanKotor', 'totalDiskonPenjualan', 'totalReturPenjualan', 'pendapatanNetto',
            'totalHPP', 'labaKotor', 
            'bebanDariExpenses', 'bebanBungaPinjaman', // Detail beban
            'totalBebanOperasional', 'labaBersih',

            // Arus Kas - [UPDATED] Tambahkan detail pengeluaran pinjaman
            'pemasukan', 'totalPemasukan',
            'pengeluaranPO', 'totalPengeluaranPO',
            'pengeluaranBeban', 'totalPengeluaranBeban',
            'pengeluaranPinjaman', 'totalPengeluaranPinjaman', // Detail pinjaman
            'totalPengeluaran',

            // Laporan Utang & Piutang (untuk Neraca/ringkasan)
            'laporanPiutang', 'laporanUtang',

            // Neraca: Aset
            'aset_kasDanBank', 'aset_piutangUsaha', 'aset_persediaan', 'totalAsetLancar',
            'aset_tetap', 'totalAsetTetap', 'totalAset',

            // Neraca: Liabilitas & Ekuitas
            'liabilitas_utangUsaha', 'totalLiabilitasLancar',
            'liabilitas_utangJangkaPanjang', 'totalLiabilitasJangkaPanjang', 'totalLiabilitas',
            'ekuitas_modalDisetor', 'ekuitas_penarikanModal', 'ekuitas_labaRugiAkumulasi', 'totalEkuitas',
            'totalLiabilitasDanEkuitas'
        ));
    }
}