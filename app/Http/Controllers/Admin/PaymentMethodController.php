<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

class PaymentMethodController extends Controller
{
    /**
     * Terapkan middleware permission agar hanya user tertentu bisa mengakses.
     */
    public function __construct()
    {
        $this->middleware('permission:manage-payment-methods');
    }

    /**
     * Menampilkan daftar metode pembayaran aktif (tidak diarsip).
     */
    public function index(): View
    {
        // Model otomatis hanya mengambil yang belum di-soft delete
        $paymentMethods = PaymentMethod::orderBy('name')->get();

        return view('admin.payment_methods.index', compact('paymentMethods'));
    }

    /**
     * Menampilkan form untuk membuat metode pembayaran baru.
     */
    public function create(): View
    {
        return view('admin.payment_methods.create');
    }

    /**
     * Menyimpan metode pembayaran baru ke database.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:payment_methods,name',
            'type' => 'required|in:direct,pending,gateway',
            'required_fields_config' => 'required|in:none,proof_only,reference_only,proof_and_reference',
            'is_active' => 'required|boolean',
        ]);

        PaymentMethod::create($validated);

        return redirect()
            ->route('admin.payment-methods.index')
            ->with('success', 'Metode pembayaran baru berhasil dibuat.');
    }

    /**
     * Menampilkan form untuk mengedit metode pembayaran yang ada.
     */
    public function edit(PaymentMethod $paymentMethod): View
    {
        return view('admin.payment_methods.edit', compact('paymentMethod'));
    }

    /**
     * Memperbarui data metode pembayaran yang ada di database.
     */
    public function update(Request $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('payment_methods')->ignore(
                    $paymentMethod->payment_method_id,
                    'payment_method_id'
                ),
            ],
            'type' => 'required|in:direct,pending,gateway',
            'required_fields_config' => 'required|in:none,proof_only,reference_only,proof_and_reference',
            'is_active' => 'required|boolean',
        ]);

        $paymentMethod->update($validated);

        return redirect()
            ->route('admin.payment-methods.index')
            ->with('success', 'Metode pembayaran berhasil diperbarui.');
    }

    /**
     * Mengarsip (soft delete) metode pembayaran.
     */
    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        try {
            $paymentMethod->delete(); // Soft delete
            return redirect()
                ->route('admin.payment-methods.index')
                ->with('success', 'Metode pembayaran berhasil diarsip.');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.payment-methods.index')
                ->with('error', 'Gagal mengarsip metode: ' . $e->getMessage());
        }
    }

    /**
     * Menampilkan daftar metode pembayaran yang sudah diarsip (soft deleted).
     */
    public function archivedIndex(): View
    {
        $archivedMethods = PaymentMethod::onlyTrashed()
            ->orderBy('deleted_at', 'desc')
            ->get();

        return view('admin.payment_methods.archive', compact('archivedMethods'));
    }

    /**
     * Memulihkan metode pembayaran yang diarsip.
     */
    public function restore($id): RedirectResponse
    {
        try {
            $method = PaymentMethod::onlyTrashed()->findOrFail($id);
            $method->restore();

            return redirect()
                ->route('admin.payment-methods.archived.index')
                ->with('success', 'Metode pembayaran berhasil dipulihkan.');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.payment-methods.archived.index')
                ->with('error', 'Gagal memulihkan: ' . $e->getMessage());
        }
    }

    /**
     * Menghapus permanen metode pembayaran dari database.
     */
    public function forceDelete($id): RedirectResponse
    {
        try {
            $method = PaymentMethod::onlyTrashed()->findOrFail($id);
            $method->forceDelete();

            return redirect()
                ->route('admin.payment-methods.archived.index')
                ->with('success', 'Metode pembayaran berhasil dihapus permanen.');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.payment-methods.archived.index')
                ->with('error', 'Gagal menghapus permanen: ' . $e->getMessage());
        }
    }
}
