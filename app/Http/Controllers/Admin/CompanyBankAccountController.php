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
        $this->middleware('permission:manage-bank-accounts');
    }

    public function index(): View
    {
        $accounts = CompanyBankAccount::with('account')->orderBy('bank_name')->get();

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

    public function destroy(CompanyBankAccount $companyBankAccount): RedirectResponse
    {
        try {
            $companyBankAccount->delete();

            return redirect()
                ->route('admin.company-bank-accounts.index')
                ->with('success', 'Akun bank berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.company-bank-accounts.index')
                ->with('error', 'Gagal menghapus akun. Pastikan tidak ada data terkait: ' . $e->getMessage());
        }
    }
}
