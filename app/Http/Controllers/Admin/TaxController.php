<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class TaxController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-settings');
    }

    public function index(): View
    {
        $taxes = Tax::latest()->paginate(10);
        return view('admin.taxes.index', compact('taxes'));
    }

    public function create(): View
    {
        return view('admin.taxes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0|max:100',
        ]);

        $validated['is_active'] = $request->has('is_active');

        Tax::create($validated);

        return redirect()->route('admin.taxes.index')->with('success', 'Tarif pajak baru berhasil dibuat.');
    }

    public function edit(Tax $tax): View
    {
        return view('admin.taxes.edit', compact('tax'));
    }

    public function update(Request $request, Tax $tax): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0|max:100',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $tax->update($validated);

        return redirect()->route('admin.taxes.index')->with('success', 'Tarif pajak berhasil diupdate.');
    }

    public function destroy(Tax $tax): RedirectResponse
    {
        $tax->delete();

        return redirect()->route('admin.taxes.index')->with('success', 'Tarif pajak berhasil dihapus.');
    }
}