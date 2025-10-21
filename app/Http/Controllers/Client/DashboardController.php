<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\OrderChangeRequest;

class DashboardController extends Controller
{
    public function index(): View
    {
        // Get the currently logged-in client
        $client = Auth::guard('client')->user();
        $clientId = $client->client_id;

        // --- 1. DATA UNTUK KPI CARDS ---

        // Ambil semua invoice yang belum lunas (untuk KPI dan Daftar)
        $unpaidInvoices = $client->salesInvoices()
                                ->with('returns') // Ambil data retur
                                ->whereIn('status', ['unpaid', 'partially_paid'])
                                ->get();
        
        // Hitung total piutang dari koleksi di atas
        $totalPiutang = $unpaidInvoices->reduce(function ($carry, $invoice) {
            $totalRetur = $invoice->returns->sum('total_amount');
            // Pastikan sisa tagihan tidak negatif
            $remaining = $invoice->total_amount - $invoice->amount_paid - $totalRetur;
            return $carry + ($remaining > 0 ? $remaining : 0);
        }, 0);

        // Hitung Pesanan Online Klien yg Menunggu Review
        $pendingClientOrdersCount = $client->orders()
            ->where('order_source', 'client')
            ->where('status', 'pending_review')
            ->count();

        // Hitung Pesanan Sales yg Aktif (Belum di-invoice)
        $activeSalesOrdersCount = $client->orders()
            ->where('order_source', 'sales')
            ->whereIn('status', ['pending', 'approved'])
            ->count();
        
        // Hitung Permintaan Perubahan yg Pending
        $pendingChangeRequestsCount = OrderChangeRequest::where('client_id', $clientId)
            ->where('status', 'pending')
            ->count();


        // --- 2. DATA UNTUK DAFTAR "PERLU TINDAKAN" ---

        // Ambil 3 pesanan online terbaru yg pending review
        $latestPendingClientOrders = $client->orders()
            ->where('order_source', 'client')
            ->where('status', 'pending_review')
            ->latest('created_at')
            ->take(3)
            ->get();

        // Ambil 3 permintaan perubahan terbaru yg pending
        $latestPendingChangeRequests = OrderChangeRequest::with('order')
            ->where('client_id', $clientId)
            ->where('status', 'pending')
            ->latest('created_at')
            ->take(3)
            ->get();
        
        // Gabungkan keduanya dan urutkan
        $pendingActivities = $latestPendingClientOrders->concat($latestPendingChangeRequests)
                                 ->sortByDesc('created_at');


        // --- 3. DATA UNTUK KARTU "TAGIHAN BELUM LUNAS" ---
        
        // Kita sudah punya $unpaidInvoices, tinggal diurutkan
        $invoicesForCard = $unpaidInvoices->sortByDesc('order_date');
        

        // Kirim semua data ke view
        return view('client.dashboard', compact(
            'client',
            'totalPiutang',
            'pendingClientOrdersCount',
            'activeSalesOrdersCount',
            'pendingChangeRequestsCount',
            'pendingActivities',
            'invoicesForCard' // ✅ Variabel baru
        ));
    }
}