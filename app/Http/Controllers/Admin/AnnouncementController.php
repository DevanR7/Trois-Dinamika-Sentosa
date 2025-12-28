<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-announcements');
    }

    public function index(Request $request): View
    {
        $query = Announcement::query();

        if ($request->get('status') === 'deleted') {
            $query->onlyTrashed();
        }

        $announcements = $query->latest()->paginate(15);

        return view('admin.announcements.index', compact('announcements'));
    }

    public function create(): View
    {
        $clients = Client::orderBy('client_name')->get(['client_id', 'client_name']);

        return view('admin.announcements.create', compact('clients'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'       => 'nullable|string|max:255',
            'content'     => 'required|string',
            'type'        => 'required|in:broadcast,targeted',
            'is_active'   => 'sometimes|boolean',
            'client_ids'  => 'required_if:type,targeted|array',
            'client_ids.*'=> 'exists:clients,client_id',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $announcement = Announcement::create($validated);

        if ($validated['type'] === 'targeted' && isset($validated['client_ids'])) {
            $announcement->clients()->sync($validated['client_ids']);
        }

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', 'Pengumuman baru berhasil dibuat.');
    }

    public function edit(Announcement $announcement): View
    {
        $clients = Client::orderBy('client_name')->get(['client_id', 'client_name']);
        $selectedClientIds = $announcement->clients()->pluck('clients.client_id')->toArray();

        return view('admin.announcements.edit', compact('announcement', 'clients', 'selectedClientIds'));
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $validated = $request->validate([
            'title'       => 'nullable|string|max:255',
            'content'     => 'required|string',
            'type'        => 'required|in:broadcast,targeted',
            'is_active'   => 'sometimes|boolean',
            'client_ids'  => 'required_if:type,targeted|array',
            'client_ids.*'=> 'exists:clients,client_id',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $announcement->update($validated);

        if ($validated['type'] === 'targeted' && isset($validated['client_ids'])) {
            $announcement->clients()->sync($validated['client_ids']);
        } else {
            $announcement->clients()->detach();
        }

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', 'Pengumuman berhasil diperbarui.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', 'Pengumuman berhasil diarsipkan.');
    }

    public function restore(Announcement $announcement): RedirectResponse
    {
        if ($announcement->trashed()) {
            $announcement->restore();
            return back()->with('success', 'Pengumuman berhasil dipulihkan.');
        }

        return back()->with('error', 'Pengumuman tidak terhapus.');
    }

    public function forceDelete(Announcement $announcement): RedirectResponse
    {
        if ($announcement->trashed()) {
            $announcement->forceDelete();
            return redirect()
                ->route('admin.announcements.index', ['status' => 'deleted'])
                ->with('success', 'Pengumuman telah dihapus permanen.');
        }
        
        return back()->with('error', 'Pengumuman harus diarsipkan terlebih dahulu.');
    }
}