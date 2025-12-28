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
        $this->middleware('permission:manage-payment-methods');
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
            'client_input_config' => 'required|in:none,proof_only,reference_only,proof_and_reference',
            'client_status_default' => 'required|in:completed,pending_verification',
            'internal_input_config' => 'required|in:none,proof_only,reference_only,proof_and_reference',
            'internal_status_default' => 'required|in:completed,pending_verification',
        ]);

        $paymentMethod->update($validated);

        return redirect()
            ->route('admin.payment-methods.index')
            ->with('success', 'Metode pembayaran berhasil diperbarui.');
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

    public function archivedIndex(): View
    {
        $archivedMethods = PaymentMethod::onlyTrashed()
            ->orderBy('deleted_at', 'desc')
            ->get();

        return view('admin.payment_methods.archive', compact('archivedMethods'));
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