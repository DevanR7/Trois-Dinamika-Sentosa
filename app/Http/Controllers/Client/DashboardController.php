<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): View
    {
        // Get the currently logged-in client
        $client = Auth::guard('client')->user();

        // Fetch the 5 most recent invoices for the table
        $latestInvoices = $client->salesInvoices()
            ->latest('order_date')
            ->take(5)
            ->get();

        // Calculate total outstanding balance (piutang)
        $totalPiutang = $client->salesInvoices()
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->sum(DB::raw('total_amount - amount_paid'));

        // Count active sales orders
        $activeOrders = $client->orders() // ✅ BERUBAH: Gunakan method orders()
            ->whereIn('status', ['pending', 'approved']) // Status 'draft' tidak ada di model Order
            // ->where('order_source', 'client') // Optional: Tambahkan filter jika perlu
            ->count();

        return view('client.dashboard', compact(
            'client',
            'latestInvoices',
            'totalPiutang',
            'activeOrders'
        ));
    }
}