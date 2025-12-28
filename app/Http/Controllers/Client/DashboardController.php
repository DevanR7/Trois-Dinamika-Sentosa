<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\OrderChangeRequest;

class DashboardController extends Controller
{
    public function index(): View
    {
        $client = Auth::guard('client')->user();
        $clientId = $client->client_id;

        $unpaidInvoices = $client->salesInvoices()
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->with(['deductingReturns', 'adjustments'])
            ->get();

        $totalPiutang = $unpaidInvoices->sum('remaining_balance');
        $availableBalance = $client->balance;
        $pendingBalance = $client->pending_balance;
        $pendingClientOrdersCount = $client->orders()->where('order_source', 'client')->where('status', 'pending_review')->count();
        $activeSalesOrdersCount = $client->orders()->where('order_source', 'sales')->whereIn('status', ['pending', 'approved'])->count();
        $pendingChangeRequestsCount = OrderChangeRequest::where('client_id', $clientId)->where('status', 'pending')->count();
        $latestPendingClientOrders = $client->orders()->where('order_source', 'client')->where('status', 'pending_review')->latest('created_at')->take(3)->get();
        $latestPendingChangeRequests = OrderChangeRequest::with('order')->where('client_id', $clientId)->where('status', 'pending')->latest('created_at')->take(3)->get();
        $pendingActivities = $latestPendingClientOrders->concat($latestPendingChangeRequests)->sortByDesc('created_at');
        $invoicesForCard = $unpaidInvoices->sortByDesc('order_date');

        return view('client.dashboard', compact(
            'client', 'totalPiutang', 'availableBalance', 'pendingBalance',
            'pendingClientOrdersCount', 'activeSalesOrdersCount', 'pendingChangeRequestsCount',
            'pendingActivities', 'invoicesForCard'
        ));
    }
}