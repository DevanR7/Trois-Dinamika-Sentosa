<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

class PaymentMethodController extends Controller
{
    public function __construct()
    {
        // Terapkan permission ke semua method
        $this->middleware('permission:manage-payment-methods');
    }

    /**
     * Tampilkan daftar semua metode pembayaran.
     */
    public function index(): View
    {
        $paymentMethods = PaymentMethod::orderBy('name')->get();
        return view('payment_methods.index', compact('paymentMethods'));
    }

    /**
     * Tampilkan form untuk membuat metode baru.
     */
    public function create(): View
    {
        return view('payment_methods.create');
    }

    /**
     * Simpan metode pembayaran baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:payment_methods,name',
            'type' => 'required|in:direct,pending,gateway',
            'is_active' => 'required|boolean',
        ]);

        PaymentMethod::create($validated);

        return redirect()->route('payment-methods.index')->with('success', 'Metode pembayaran baru berhasil dibuat.');
    }

    /**
     * Tampilkan form untuk mengedit metode.
     */
    public function edit(PaymentMethod $paymentMethod): View
    {
        return view('payment_methods.edit', compact('paymentMethod'));
    }

    /**
     * Update metode pembayaran yang ada.
     */
    public function update(Request $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('payment_methods')->ignore($paymentMethod->payment_method_id, 'payment_method_id'),
            ],
            'type' => 'required|in:direct,pending,gateway',
            'is_active' => 'required|boolean',
        ]);

        $paymentMethod->update($validated);

        return redirect()->route('payment-methods.index')->with('success', 'Metode pembayaran berhasil diperbarui.');
    }

    /**
     * Hapus metode pembayaran.
     */
    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        try {
            $paymentMethod->delete();
            return redirect()->route('payment-methods.index')->with('success', 'Metode pembayaran berhasil dihapus.');
        } catch (\Exception $e) {
            // Tangani jika gagal hapus (misal, karena foreign key constraint jika sudah dipakai)
            return redirect()->route('payment-methods.index')->with('error', 'Gagal menghapus metode. Pastikan tidak sedang digunakan.');
        }
    }
}