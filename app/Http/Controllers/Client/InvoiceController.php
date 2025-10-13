<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Models\User;

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
        $salesUsers = User::role('sales')->get();

        return view('client.invoices.show', compact('invoice','salesUsers'));
    }

    public function uploadProof(Request $request, SalesInvoice $invoice): RedirectResponse
    {
        if ($invoice->client_id !== Auth::guard('client')->id()) {
            abort(403);
        }

        $sisaTagihan = $invoice->total_amount - $invoice->amount_paid;

        // Validasi yang disempurnakan
        $validated = $request->validate([
            'payment_method'    => 'required|in:cash,manual_transfer',
            'payment_amount'    => "required|numeric|min:1|max:{$sisaTagihan}",
            'user_id_sales'     => 'required_if:payment_method,cash|exists:users,user_id',
            'proof_of_payment'  => 'required_if:payment_method,manual_transfer|image|mimes:jpeg,png,jpg|max:2048',
            'notes'             => 'nullable|string',
        ]);

        $path = null;
        if ($request->hasFile('proof_of_payment')) {
            $path = $request->file('proof_of_payment')->store('payment_proofs', 'public');
        }

        $invoice->payments()->create([
            'payment_date'          => now(),
            'amount'                => $validated['payment_amount'],
            'payment_method'        => $validated['payment_method'],
            'proof_of_payment_path' => $path,
            'status'                => 'pending_verification',
            'received_by_user_id'   => $validated['user_id_sales'] ?? null, // Simpan ID sales jika metodenya cash
            'notes'                 => $validated['notes'],
        ]);

        return back()->with('success', 'Informasi pembayaran berhasil dikirim dan sedang menunggu verifikasi.');
    }
}