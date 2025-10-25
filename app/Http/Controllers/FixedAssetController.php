<?php

namespace App\Http\Controllers;

use App\Models\FixedAsset; // Pastikan Model di-import
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Untuk melacak siapa yang input
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class FixedAssetController extends Controller
{
    /**
     * Menampilkan daftar semua aset tetap.
     */
    public function index(Request $request): View
    {
        // $this->authorize('viewAny', FixedAsset::class); // Aktifkan jika Anda membuat Policy

        $query = FixedAsset::with('user');

        // Filter pencarian
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('asset_name', 'like', "%{$search}%");
        }

        $fixedAssets = $query->latest('purchase_date')->paginate(15)->appends($request->query());

        return view('fixed_assets.index', compact('fixedAssets'));
    }

    /**
     * Menampilkan form untuk membuat aset tetap baru.
     */
    public function create(): View
    {
        // $this->authorize('create', FixedAsset::class);
        return view('fixed_assets.create');
    }

    /**
     * Menyimpan aset tetap baru ke database.
     */
    public function store(Request $request): RedirectResponse
    {
        // $this->authorize('create', FixedAsset::class);

        $validated = $request->validate([
            'asset_name' => 'required|string|max:255',
            'purchase_date' => 'required|date',
            'purchase_cost' => 'required|numeric|min:1',
            'description' => 'nullable|string',
        ]);

        FixedAsset::create([
            'asset_name' => $validated['asset_name'],
            'purchase_date' => $validated['purchase_date'],
            'purchase_cost' => $validated['purchase_cost'],
            'description' => $validated['description'],
            'user_id' => Auth::id(), // Simpan ID user yang menginput
        ]);

        return redirect()->route('fixed-assets.index')->with('success', 'Aset tetap berhasil dicatat.');
    }

    /**
     * Menampilkan detail (show) - Kita tidak pakai, jadi redirect ke index.
     */
    public function show(FixedAsset $fixedAsset): RedirectResponse
    {
         return redirect()->route('fixed-assets.index');
    }

    /**
     * Menampilkan form untuk mengedit aset tetap.
     */
    public function edit(FixedAsset $fixedAsset): View
    {
        // $this->authorize('update', $fixedAsset);
        return view('fixed_assets.edit', compact('fixedAsset'));
    }

    /**
     * Mengupdate data aset tetap di database.
     */
    public function update(Request $request, FixedAsset $fixedAsset): RedirectResponse
    {
        // $this->authorize('update', $fixedAsset);

        $validated = $request->validate([
            'asset_name' => 'required|string|max:255',
            'purchase_date' => 'required|date',
            'purchase_cost' => 'required|numeric|min:1',
            'description' => 'nullable|string',
        ]);

        $fixedAsset->update($validated);

        return redirect()->route('fixed-assets.index')->with('success', 'Aset tetap berhasil diupdate.');
    }

    /**
     * Menghapus aset tetap dari database.
     */
    public function destroy(FixedAsset $fixedAsset): RedirectResponse
    {
        // $this->authorize('delete', $fixedAsset);

        try {
            $fixedAsset->delete();
            return redirect()->route('fixed-assets.index')->with('success', 'Aset tetap berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus aset: ' . $e->getMessage());
        }
    }
}