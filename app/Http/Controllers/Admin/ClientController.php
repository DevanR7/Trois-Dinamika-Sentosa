<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\InvoiceAdjustment;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ClientController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view-clients')->only(['index', 'show']);
        $this->middleware('can:create-clients')->only(['create', 'store']);
        $this->middleware('can:edit-clients')->only(['edit', 'update', 'approve', 'lock', 'unlock']);
        $this->middleware('can:delete-clients')->only(['destroy', 'restore']);
    }

    public function index(Request $request): View
    {
        $query = Client::query();

        if ($request->get('status') === 'deleted') {
            $query->onlyTrashed();
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('client_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $clients = $query->latest('client_id')->paginate(10);
        return view('admin.clients.index', compact('clients'));
    }

    public function create(): View
    {
        return view('admin.clients.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_name' => 'required|string|max:150',
            'email' => 'nullable|string|email|max:100|unique:clients,email',
            'password' => ['required', 'confirmed', Password::defaults()],
            'person_in_charge' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'phone_number' => 'nullable|string|max:20',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_approved'] = false;

        Client::create($validated);

        return redirect()->route('admin.clients.index')->with('success', 'Klien baru berhasil ditambahkan.');
    }

    public function show(Client $client): View
    {
        return view('admin.clients.show', compact('client'));
    }

    public function getTabContent(Request $request, Client $client)
{
    $tab = $request->get('tab');

    switch ($tab) {
        case 'ledger':
            $data = $client->ledgers()
                ->latest('transaction_date')
                ->latest('ledger_id')
                ->paginate(10);
            return view('admin.clients.tabs.ledger', ['ledgers' => $data])->render();

        case 'invoices':
            $data = $client->salesInvoices()
                ->latest('order_date')
                ->paginate(10);
            return view('admin.clients.tabs.invoices', ['invoices' => $data])->render();

        case 'returns':
            // Ambil retur melalui relasi invoice atau langsung jika ada relation direct
            // Di model Client sudah saya buatkan relasi through invoices sebelumnya? 
            // Jika belum, kita query manual agar efisien:
            $data = SalesReturn::where('client_id', $client->client_id)
                ->with('salesInvoice')
                ->latest('return_date')
                ->paginate(10);
            return view('admin.clients.tabs.returns', ['returns' => $data])->render();

        case 'adjustments':
            // Adjustment biasanya nempel ke Invoice. Kita cari via Invoice ID milik klien
            $invoiceIds = $client->salesInvoices()->pluck('invoice_id');
            $data = InvoiceAdjustment::whereIn('sales_invoice_id', $invoiceIds)
                ->with('salesInvoice')
                ->latest('adjustment_date')
                ->paginate(10);
            return view('admin.clients.tabs.adjustments', ['adjustments' => $data])->render();

        default:
            return '<div class="p-4 text-center text-red-500">Tab tidak ditemukan</div>';
    }
}

    public function edit(Client $client): View
    {
        return view('admin.clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $validated = $request->validate([
            'client_name' => 'required|string|max:150',
            'email' => ['nullable', 'string', 'email', 'max:100', Rule::unique('clients')->ignore($client->client_id, 'client_id')],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'person_in_charge' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'phone_number' => 'nullable|string|max:20',
        ]);

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $client->update($validated);

        return redirect()->route('admin.clients.index')->with('success', 'Data klien berhasil diperbarui.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $client->delete();
        return redirect()->route('admin.clients.index')->with('success', 'Klien berhasil dihapus.');
    }

    public function approve(Client $client): RedirectResponse
    {
        $client->update(['is_approved' => true]);
        return back()->with('success', 'Akun klien ' . $client->client_name . ' telah disetujui.');
    }

    public function restore(Client $client): RedirectResponse
    {
        if ($client->trashed()) {
            $client->restore();
            return back()->with('success', 'Akun klien ' . $client->client_name . ' telah dipulihkan.');
        }

        return back()->with('error', 'Klien tidak terhapus.');
    }

    public function lock(Client $client): RedirectResponse
    {
        if (!$client->trashed()) {
            $client->update(['is_locked' => true]);
            return back()->with('success', 'Akun klien ' . $client->client_name . ' telah dikunci.');
        }

        return back()->with('error', 'Tidak dapat mengunci akun yang sudah diarsipkan.');
    }

    public function unlock(Client $client): RedirectResponse
    {
        if (!$client->trashed()) {
            $client->update(['is_locked' => false]);
            return back()->with('success', 'Kunci akun klien ' . $client->client_name . ' telah dibuka.');
        }

        return back()->with('error', 'Tidak dapat membuka kunci akun yang sudah diarsipkan.');
    }
}