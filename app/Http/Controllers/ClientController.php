<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rules\Password;

class ClientController extends Controller
{
    // ✅ UBAH METHOD INDEX INI
    public function index(Request $request): View
    {
        $query = Client::query();

        // ✅ Tambahkan filter untuk melihat data terhapus
        if ($request->get('status') === 'deleted') {
            $query->onlyTrashed();
        }

        if ($request->filled('search')) {
            $search = $request->search;
            // Gunakan where agar filter 'onlyTrashed' tetap berlaku
            $query->where(function($q) use ($search) {
                $q->where('client_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        $clients = $query->latest('client_id')->paginate(10);
        return view('clients.index', compact('clients'));
    }

    public function create(): View
    {
        return view('clients.create');
    }

    public function store(Request $request): RedirectResponse
    {
        // ... (Method store Anda tetap sama)
        $validated = $request->validate([
            'client_name' => 'required|string|max:150',
            'email' => 'nullable|string|email|max:100|unique:clients,email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'person_in_charge' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'phone_number' => 'nullable|string|max:20',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        Client::create($validated);
        return redirect()->route('clients.index')->with('success', 'Klien baru berhasil ditambahkan.');
    }

    public function show(Client $client): View
    {
        return view('clients.show', compact('client'));
    }

    public function edit(Client $client): View
    {
        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        // ... (Method update Anda tetap sama)
        $validated = $request->validate([
            'client_name' => 'required|string|max:150',
            'email' => ['nullable', 'string', 'email', 'max:100', Rule::unique('clients')->ignore($client->client_id, 'client_id')],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
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
        return redirect()->route('clients.index')->with('success', 'Data klien berhasil diperbarui.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $client->delete();
        return redirect()->route('clients.index')->with('success', 'Klien berhasil dihapus.');
    }

    public function approve(Client $client): RedirectResponse
    {
        $client->update(['is_approved' => true]);
        return back()->with('success', 'Akun klien ' . $client->client_name . ' telah disetujui.');
    }
    
    // ✅ TAMBAHKAN METHOD BARU INI
    /**
     * Memulihkan client yang di-soft-delete.
     */
    public function restore(Client $client): RedirectResponse
    {
        // Cek ini memastikan kita hanya restore data yang terhapus
        if ($client->trashed()) {
            $client->restore();
            return back()->with('success', 'Akun klien ' . $client->client_name . ' telah dipulihkan.');
        }
        return back()->with('error', 'Klien tidak terhapus.');
    }

    /**
     * ✅ TAMBAHKAN METHOD INI
     * Mengunci akun klien.
     */
    public function lock(Client $client): RedirectResponse
    {
        // TODO: Tambahkan otorisasi jika perlu (misal via Policy)
        // $this->authorize('lock', $client); 

        if (!$client->trashed()) { // Hanya kunci akun yang aktif
            $client->update(['is_locked' => true]);
            return back()->with('success', 'Akun klien ' . $client->client_name . ' telah dikunci.');
        }
        return back()->with('error', 'Tidak dapat mengunci akun yang sudah diarsipkan.');
    }

    /**
     * ✅ TAMBAHKAN METHOD INI
     * Membuka kunci akun klien.
     */
    public function unlock(Client $client): RedirectResponse
    {
        // TODO: Tambahkan otorisasi jika perlu
        // $this->authorize('unlock', $client);

         if (!$client->trashed()) { // Hanya buka kunci akun yang aktif
            $client->update(['is_locked' => false]);
            return back()->with('success', 'Kunci akun klien ' . $client->client_name . ' telah dibuka.');
        }
         return back()->with('error', 'Tidak dapat membuka kunci akun yang sudah diarsipkan.');
    }
}