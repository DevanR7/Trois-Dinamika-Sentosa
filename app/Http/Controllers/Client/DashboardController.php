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
    /**
     * Menampilkan dashboard klien dengan metrik dan data penting
     */
    public function index(): View
    {
        // Data dasar klien
        $client = Auth::guard('client')->user();
        $clientId = $client->client_id;

        // --- SECTION 1: DATA UNTUK KPI CARDS ---

        // Perhitungan total piutang dari invoice belum lunas
        $unpaidInvoices = $client->salesInvoices()
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->with(['deductingReturns', 'adjustments'])
            ->get();
        
        $totalPiutang = $unpaidInvoices->reduce(function ($carry, $invoice) {
            return $carry + $invoice->remaining_balance;
        }, 0);

        // Data saldo klien
        $availableBalance = $client->balance;
        $pendingBalance = $client->pending_balance;

        // Count data untuk KPI cards
        $pendingClientOrdersCount = $client->orders()
            ->where('order_source', 'client')
            ->where('status', 'pending_review')
            ->count();
            
        $activeSalesOrdersCount = $client->orders()
            ->where('order_source', 'sales')
            ->whereIn('status', ['pending', 'approved'])
            ->count();
            
        $pendingChangeRequestsCount = OrderChangeRequest::where('client_id', $clientId)
            ->where('status', 'pending')
            ->count();

        // --- SECTION 2: DATA UNTUK DAFTAR "PERLU TINDAKAN" ---

        // Data order perlu tindakan
        $latestPendingClientOrders = $client->orders()
            ->where('order_source', 'client')
            ->where('status', 'pending_review')
            ->latest('created_at')
            ->take(3)
            ->get();

        // Data permintaan perubahan pending
        $latestPendingChangeRequests = OrderChangeRequest::with('order')
            ->where('client_id', $clientId)
            ->where('status', 'pending')
            ->latest('created_at')
            ->take(3)
            ->get();

        // Gabungan aktivitas pending
        $pendingActivities = $latestPendingClientOrders->concat($latestPendingChangeRequests)
                                        ->sortByDesc('created_at');

        // --- SECTION 3: DATA UNTUK KARTU "TAGIHAN BELUM LUNAS" ---
        $invoicesForCard = $unpaidInvoices->sortByDesc('order_date');

        // Kirim data ke view
        return view('client.dashboard', compact(
            'client',
            'totalPiutang',
            'availableBalance',
            'pendingBalance',
            'pendingClientOrdersCount',
            'activeSalesOrdersCount',
            'pendingChangeRequestsCount',
            'pendingActivities',
            'invoicesForCard'
        ));
    }
}