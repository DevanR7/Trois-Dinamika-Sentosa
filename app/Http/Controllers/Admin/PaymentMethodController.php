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
    public function __construct()
    {
        $this->middleware('can:manage-payment-methods');
    }

    public function index(): View
    {
        $paymentMethods = PaymentMethod::orderBy('name')->get();

        return view('admin.payment_methods.index', compact('paymentMethods'));
    }

    public function create(): View
    {
        return view('admin.payment_methods.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:payment_methods,name',
            'type' => 'required|in:direct,pending,gateway',
            'is_active' => 'required|boolean',
            'client_input_config' => 'required|in:none,proof_only,reference_only,proof_and_reference',
            'client_status_default' => 'required|in:completed,pending_verification',
            'internal_input_config' => 'required|in:none,proof_only,reference_only,proof_and_reference',
            'internal_status_default' => 'required|in:completed,pending_verification',
        ]);

        PaymentMethod::create($validated);

        return redirect()
            ->route('admin.payment-methods.index')
            ->with('success', 'Metode pembayaran baru berhasil dibuat.');
    }

    public function edit(PaymentMethod $paymentMethod): View
    {
        return view('admin.payment_methods.edit', compact('paymentMethod'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        // 1. Cari Data Manual (Menghindari masalah Route Model Binding)
        $paymentMethod = PaymentMethod::where('payment_method_id', $id)->firstOrFail();

        // 2. Normalisasi Input Checkbox & Boolean
        // Form mengirim string "0" atau "1", kita ubah jadi boolean murni untuk database
        $isActive = $request->input('is_active') == '1';

        // 3. Validasi
        $validated = $request->validate([
            'name' => [
                'required', 
                'string', 
                'max:255',
                // Pastikan ignore ID menggunakan nama kolom primary key yang benar
                Rule::unique('payment_methods', 'name')->ignore($paymentMethod->payment_method_id, 'payment_method_id'),
            ],
            'type' => 'required|in:direct,pending,gateway',
            'description' => 'nullable|string|max:1000',
            
            // Validasi Config (Boleh nullable jika tidak diisi)
            'client_input_config' => 'nullable|in:none,proof_only,reference_only,proof_and_reference',
            'client_status_default' => 'nullable|in:completed,pending,pending_verification',
            
            'internal_input_config' => 'nullable|in:none,proof_only,reference_only,proof_and_reference',
            'internal_status_default' => 'nullable|in:completed,pending,pending_verification',
        ]);

        // 4. Update Data
        try {
            $paymentMethod->update([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'description' => $validated['description'] ?? null,
                
                // Gunakan nilai default jika null (terutama jika tipe Gateway)
                'client_input_config' => $validated['client_input_config'] ?? 'none',
                'client_status_default' => $validated['client_status_default'] ?? 'pending',
                'internal_input_config' => $validated['internal_input_config'] ?? 'none',
                'internal_status_default' => $validated['internal_status_default'] ?? 'completed',
                
                'is_active' => $isActive, // Pakai variabel yang sudah dinormalisasi
            ]);

            return redirect()
                ->route('admin.payment-methods.index')
                ->with('success', 'Metode pembayaran berhasil diperbarui.');
                
        } catch (\Exception $e) {
            // Jika ada error database, kembalikan ke form dengan pesan
            return back()
                ->withInput()
                ->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }
    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        try {
            $paymentMethod->delete(); 
            return redirect()
                ->route('admin.payment-methods.index')
                ->with('success', 'Metode pembayaran berhasil diarsip.');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.payment-methods.index')
                ->with('error', 'Gagal mengarsip metode: ' . $e->getMessage());
        }
    }

    public function archivedIndex(Request $request)
    {
        $query = \App\Models\PaymentMethod::onlyTrashed();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $paymentMethods = $query->orderBy('deleted_at', 'desc')->paginate(10);

        return view('admin.payment_methods.archive', compact('paymentMethods'));
    }

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