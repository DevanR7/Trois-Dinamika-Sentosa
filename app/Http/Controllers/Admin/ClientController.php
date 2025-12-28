<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
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
        $ledgers = $client->ledgers()
            ->latest('transaction_date')
            ->latest('ledger_id')
            ->paginate(10, ['*'], 'ledger_page');

        return view('admin.clients.show', compact('client', 'ledgers'));
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