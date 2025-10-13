<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $client = Auth::guard('client')->user();
        $invoices = $client->salesInvoices()->latest('order_date')->paginate(10);
        return view('client.dashboard', compact('client', 'invoices'));
    }
}