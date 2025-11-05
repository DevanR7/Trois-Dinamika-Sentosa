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
        $client = Auth::guard('client')->user();
        $clientId = $client->client_id;

        // --- 1. DATA UNTUK KPI CARDS ---

        // Ambil semua invoice yang belum lunas
        $unpaidInvoices = $client->salesInvoices()
            ->whereIn('status', ['unpaid', 'partially_paid'])
            // ✅ Eager load relasi yang diperlukan untuk accessor
            ->with(['deductingReturns', 'adjustments']) 
            ->get();
        
        // Hitung total piutang menggunakan accessor
        $totalPiutang = $unpaidInvoices->reduce(function ($carry, $invoice) {
            // ✅ GUNAKAN ACCESSOR BARU
            $remaining = $invoice->remaining_balance; 
            return $carry + $remaining;
        }, 0);

        // ✅ AMBIL SALDO KLIEN (AVAILABLE & PENDING)
        $availableBalance = $client->balance;
        $pendingBalance = $client->pending_balance;

        // ... (Hitungan pendingClientOrdersCount, activeSalesOrdersCount, dll. Anda sudah benar) ...
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


        // --- 2. DATA UNTUK DAFTAR "PERLU TINDAKAN" ---
        // ... (Logika $latestPendingClientOrders, $latestPendingChangeRequests, $pendingActivities Anda sudah benar) ...
        $latestPendingClientOrders = $client->orders()
            ->where('order_source', 'client')
            ->where('status', 'pending_review')
            ->latest('created_at')
            ->take(3)
            ->get();
        $latestPendingChangeRequests = OrderChangeRequest::with('order')
            ->where('client_id', $clientId)
            ->where('status', 'pending')
            ->latest('created_at')
            ->take(3)
            ->get();
        $pendingActivities = $latestPendingClientOrders->concat($latestPendingChangeRequests)
                                        ->sortByDesc('created_at');


        // --- 3. DATA UNTUK KARTU "TAGIHAN BELUM LUNAS" ---
        // Kita sudah punya $unpaidInvoices, tinggal diurutkan
        $invoicesForCard = $unpaidInvoices->sortByDesc('order_date');
        

        // Kirim semua data ke view
        return view('client.dashboard', compact(
            'client',
            'totalPiutang',
            'availableBalance', // ✅ KIRIM SALDO INI
            'pendingBalance',   // ✅ KIRIM SALDO INI
            'pendingClientOrdersCount',
            'activeSalesOrdersCount',
            'pendingChangeRequestsCount',
            'pendingActivities',
            'invoicesForCard'
        ));
    }
}