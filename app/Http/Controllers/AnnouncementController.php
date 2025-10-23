<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Client; // Import Client model
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function __construct()
{
    // Otorisasi SEMUA method resource KECUALI index & show (jika ada)
    $this->authorizeResource(Announcement::class, 'announcement');
    // Jika perlu otorisasi method custom (restore, forceDelete)
    // $this->middleware('can:restore,announcement')->only('restore');
    // $this->middleware('can:forceDelete,announcement')->only('forceDelete');
}

    /**
     * Menampilkan daftar pengumuman (termasuk arsip).
     */
    public function index(Request $request): View
    {
        $query = Announcement::query();

        if ($request->get('status') === 'deleted') {
            $query->onlyTrashed();
        }

        $announcements = $query->latest()->paginate(15);
        return view('announcements.index', compact('announcements'));
    }

    /**
     * Menampilkan form untuk membuat pengumuman baru.
     */
    public function create(): View
    {
        $clients = Client::orderBy('client_name')->get(['client_id', 'client_name']); // Ambil klien untuk pilihan
        return view('announcements.create', compact('clients'));
    }

    /**
     * Menyimpan pengumuman baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:broadcast,targeted',
            'is_active' => 'sometimes|boolean', // 'sometimes' agar tidak error jika checkbox tidak dicentang
            'client_ids' => 'required_if:type,targeted|array', // Wajib jika 'targeted'
            'client_ids.*' => 'exists:clients,client_id', // Pastikan ID klien valid
        ]);

        // Set is_active ke false jika tidak ada di request
        $validated['is_active'] = $request->has('is_active');

        $announcement = Announcement::create($validated);

        // Jika tipe 'targeted', simpan relasi klien
        if ($validated['type'] === 'targeted' && isset($validated['client_ids'])) {
            $announcement->clients()->sync($validated['client_ids']);
        }

        return redirect()->route('announcements.index')->with('success', 'Pengumuman baru berhasil dibuat.');
    }

    /**
     * Menampilkan form untuk mengedit pengumuman.
     */
    public function edit(Announcement $announcement): View
    {
        $clients = Client::orderBy('client_name')->get(['client_id', 'client_name']);
        
        // ✅ PERBAIKI BARIS INI
        $selectedClientIds = $announcement->clients()->pluck('clients.client_id')->toArray(); 

        return view('announcements.edit', compact('announcement', 'clients', 'selectedClientIds'));
    }

    /**
     * Memperbarui pengumuman yang ada.
     */
    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
         $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:broadcast,targeted',
            'is_active' => 'sometimes|boolean',
            'client_ids' => 'required_if:type,targeted|array',
            'client_ids.*' => 'exists:clients,client_id',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $announcement->update($validated);

        // Update relasi klien
        if ($validated['type'] === 'targeted' && isset($validated['client_ids'])) {
            $announcement->clients()->sync($validated['client_ids']);
        } else {
            // Jika tipe diubah ke broadcast, hapus relasi lama
            $announcement->clients()->detach();
        }

        return redirect()->route('announcements.index')->with('success', 'Pengumuman berhasil diperbarui.');
    }

    /**
     * Mengarsipkan pengumuman (soft delete).
     */
    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete(); // Soft delete
        return redirect()->route('announcements.index')->with('success', 'Pengumuman berhasil diarsipkan.');
    }

    /**
     * Memulihkan pengumuman yang diarsipkan.
     */
    public function restore(Announcement $announcement): RedirectResponse
    {
        // TODO: Tambahkan otorisasi jika perlu
        if ($announcement->trashed()) {
            $announcement->restore();
            return back()->with('success', 'Pengumuman berhasil dipulihkan.');
        }
        return back()->with('error', 'Pengumuman tidak terhapus.');
    }

     /**
     * Menghapus pengumuman secara permanen.
     */
    public function forceDelete(Announcement $announcement): RedirectResponse
    {
        // TODO: Tambahkan otorisasi jika perlu (misal hanya superadmin)
        if ($announcement->trashed()) {
            // Hapus relasi pivot terlebih dahulu (opsional, cascade delete sudah menangani)
            // $announcement->clients()->detach(); 
            
            $announcement->forceDelete();
            return redirect()->route('announcements.index', ['status' => 'deleted'])
                             ->with('success', 'Pengumuman telah dihapus permanen.');
        }
        return back()->with('error', 'Pengumuman harus diarsipkan terlebih dahulu.');
    }
}