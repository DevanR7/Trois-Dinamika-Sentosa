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
        $this->middleware('permission:manage-payment-methods');
    }

    /**
     * Tampilkan daftar (HANYA YANG AKTIF / non-archived).
     */
    public function index(): View
    {
        // ✅ Model akan otomatis HANYA mengambil yang tidak di-soft-delete
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

        // Notifikasi SweetAlert akan ditangani oleh layout Anda
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

        // Notifikasi SweetAlert akan ditangani oleh layout Anda
        return redirect()->route('payment-methods.index')->with('success', 'Metode pembayaran berhasil diperbarui.');
    }

    /**
     * ✅ PERUBAHAN: 'destroy' sekarang berarti 'Arsip' (Soft Delete).
     */
    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        try {
            // Karena model menggunakan SoftDeletes, ->delete() akan mengisi 'deleted_at'
            $paymentMethod->delete(); 
            return redirect()->route('payment-methods.index')->with('success', 'Metode pembayaran berhasil diarsip.');
        } catch (\Exception $e) {
            return redirect()->route('payment-methods.index')->with('error', 'Gagal mengarsip metode: ' . $e->getMessage());
        }
    }

    /**
     * ✅ BARU: Menampilkan daftar metode yang diarsip.
     */
    public function archivedIndex(): View
    {
        $archivedMethods = PaymentMethod::onlyTrashed()->orderBy('deleted_at', 'desc')->get();
        return view('payment_methods.archive', compact('archivedMethods'));
    }

    /**
     * ✅ BARU: Memulihkan metode yang diarsip.
     */
    public function restore($id): RedirectResponse
    {
        try {
            $method = PaymentMethod::onlyTrashed()->findOrFail($id);
            $method->restore();
            return redirect()->route('payment-methods.archived.index')->with('success', 'Metode pembayaran berhasil dipulihkan.');
        } catch (\Exception $e) {
            return redirect()->route('payment-methods.archived.index')->with('error', 'Gagal memulihkan: ' . $e->getMessage());
        }
    }

    /**
     * ✅ BARU: Menghapus permanen metode yang diarsip.
     */
    public function forceDelete($id): RedirectResponse
    {
        try {
            $method = PaymentMethod::onlyTrashed()->findOrFail($id);
            // Cek relasi jika perlu (tapi karena sudah nullOnDelete, seharusnya aman)
            // ...
            $method->forceDelete();
            return redirect()->route('payment-methods.archived.index')->with('success', 'Metode pembayaran berhasil dihapus permanen.');
        } catch (\Exception $e) {
            return redirect()->route('payment-methods.archived.index')->with('error', 'Gagal menghapus permanen: ' . $e->getMessage());
        }
    }
}