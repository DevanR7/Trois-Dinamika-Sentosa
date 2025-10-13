<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    /**
     * Menampilkan daftar invoice milik klien yang sedang login.
     */
    public function index(): View
    {
        $client = Auth::guard('client')->user();
        $invoices = $client->salesInvoices()
                           ->latest('order_date') // Mengurutkan berdasarkan order_date
                           ->paginate(15);
                           
        return view('client.invoices.index', compact('invoices'));
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

        return view('client.invoices.show', compact('invoice'));
    }
}