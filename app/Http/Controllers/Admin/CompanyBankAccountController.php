<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyBankAccount;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use App\Models\ChartOfAccount;

class CompanyBankAccountController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-bank-accounts');
    }

    public function index(Request $request): View
    {
        $query = CompanyBankAccount::with('account');

        // Filter Sampah
        if ($request->get('status') === 'trash') {
            $query->onlyTrashed();
        }

        // Pencarian
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('bank_name', 'like', "%{$search}%")
                  ->orWhere('account_name', 'like', "%{$search}%")
                  ->orWhere('account_number', 'like', "%{$search}%");
            });
        }

        $accounts = $query->orderBy('bank_name')->get();
        return view('admin.company_bank_accounts.index', compact('accounts'));
    }

    public function create(): View
    {
        $assetAccounts = ChartOfAccount::where('account_type', 'Aset')
                            ->where('is_active', true)
                            ->orderBy('account_number')
                            ->get();
                            
        return view('admin.company_bank_accounts.create', compact('assetAccounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_number' => 'nullable|string|max:50',
            'is_active' => 'required|boolean',
            'chart_of_account_id' => 'required|exists:chart_of_accounts,account_id',
        ]);

        CompanyBankAccount::create($validated);
        
        return redirect()
            ->route('admin.company-bank-accounts.index')
            ->with('success', 'Akun bank baru berhasil ditambahkan.');
    }

    public function edit(CompanyBankAccount $companyBankAccount): View
    {
        $assetAccounts = ChartOfAccount::where('account_type', 'Aset')
                            ->where('is_active', true)
                            ->orderBy('account_number')
                            ->get();
                            
        return view('admin.company_bank_accounts.edit', [
            'account' => $companyBankAccount,
            'assetAccounts' => $assetAccounts 
        ]);
    }

    public function update(Request $request, CompanyBankAccount $companyBankAccount): RedirectResponse
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_number' => 'nullable|string|max:50',
            'is_active' => 'required|boolean',
            'chart_of_account_id' => 'required|exists:chart_of_accounts,account_id',
        ]);

        $companyBankAccount->update($validated);
        
        return redirect()
            ->route('admin.company-bank-accounts.index')
            ->with('success', 'Akun bank berhasil diperbarui.');
    }

    // Soft Delete (Pindah ke Sampah)
    public function destroy(CompanyBankAccount $companyBankAccount): RedirectResponse
    {
        try {
            // Cek apakah akun ini digunakan di transaksi aktif? 
            // Opsional: Anda bisa menambahkan pengecekan di sini jika ingin memblokir hapus
            // jika saldo tidak 0. Namun, Soft Delete biasanya aman dilakukan kapan saja.
            
            $companyBankAccount->delete();
            return redirect()
                ->route('admin.company-bank-accounts.index')
                ->with('success', 'Akun bank dipindahkan ke sampah.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus akun: ' . $e->getMessage());
        }
    }

    // Restore (Pulihkan)
    public function restore($id): RedirectResponse
    {
        try {
            $account = CompanyBankAccount::onlyTrashed()->findOrFail($id);
            $account->restore();
            return redirect()
                ->route('admin.company-bank-accounts.index', ['status' => 'trash'])
                ->with('success', 'Akun bank berhasil dipulihkan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memulihkan akun: ' . $e->getMessage());
        }
    }

    // Force Delete (Hapus Permanen)
    public function forceDelete($id): RedirectResponse
    {
        try {
            $account = CompanyBankAccount::onlyTrashed()->findOrFail($id);

            // Cek Integritas Data Sebelum Hapus Permanen
            if ($account->salesPayments()->exists() || $account->purchasePayments()->exists()) {
                return back()->with('error', 'Gagal: Akun ini memiliki riwayat transaksi pembayaran. Tidak bisa dihapus permanen demi integritas laporan.');
            }

            $account->forceDelete();
            return redirect()
                ->route('admin.company-bank-accounts.index', ['status' => 'trash'])
                ->with('success', 'Akun bank dihapus permanen.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus permanen: ' . $e->getMessage());
        }
    }
}