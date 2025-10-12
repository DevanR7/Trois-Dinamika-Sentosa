<?php

namespace App\Http\Controllers;

use App\Models\SalesInvoice;
use App\Models\PurchaseOrder;
use App\Models\SalesReturn;
use App\Models\PurchaseReturn;
use App\Models\Payment;
use App\Models\PurchaseOrderPayment;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view-reports');
    }
    
    public function index(Request $request): View
    {
        // Tentukan rentang tanggal, defaultnya bulan ini
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        // 1. LAPORAN PEMASUKAN (dari pembayaran invoice)
        $pemasukan = Payment::whereBetween('payment_date', [$startDate, $endDate])->get();
        $totalPemasukan = $pemasukan->sum('amount');

        // 2. LAPORAN PENGELUARAN (dari pembayaran PO)
        $pengeluaran = PurchaseOrderPayment::whereBetween('payment_date', [$startDate, $endDate])->get();
        $totalPengeluaran = $pengeluaran->sum('amount');

        // 3. LAPORAN PIUTANG (Invoice belum lunas)
        $laporanPiutang = SalesInvoice::with('client')
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->orderBy('due_date', 'asc')
            ->get();

        // 4. LAPORAN UTANG (PO belum lunas)
        $laporanUtang = PurchaseOrder::with('supplier')
            ->whereIn('payment_status', ['unpaid', 'partially_paid'])
            ->orderBy('due_date', 'asc')
            ->get();

        return view('reports.index', compact(
            'startDate',
            'endDate',
            'pemasukan',
            'totalPemasukan',
            'pengeluaran',
            'totalPengeluaran',
            'laporanPiutang',
            'laporanUtang'
        ));
    }
}