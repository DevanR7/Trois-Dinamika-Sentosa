<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

class ChartOfAccountController extends Controller
{

    public function __construct()
    {
        $this->middleware('can:manage-settings'); 
    }

    public function index(): View
    {
        $parentAccounts = ChartOfAccount::whereNull('parent_account_id')
                            ->with('children')
                            ->orderBy('account_number', 'asc')
                            ->get();
                            
        return view('admin.chart_of_accounts.index', compact('parentAccounts'));
    }

    public function create(): View
    {
        $parentAccounts = ChartOfAccount::orderBy('account_number', 'asc')->get();
        $accountTypes = ['Aset', 'Liabilitas', 'Ekuitas', 'Pendapatan', 'HPP', 'Beban'];
        $normalBalances = ['Debit', 'Kredit'];

        return view('admin.chart_of_accounts.create', compact('parentAccounts', 'accountTypes', 'normalBalances'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'account_number' => 'required|string|max:20|unique:chart_of_accounts,account_number',
            'account_name' => 'required|string|max:255',
            'account_type' => ['required', Rule::in(['Aset', 'Liabilitas', 'Ekuitas', 'Pendapatan', 'HPP', 'Beban'])],
            'normal_balance' => ['required', Rule::in(['Debit', 'Kredit'])],
            'parent_account_id' => 'nullable|exists:chart_of_accounts,account_id',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['parent_account_id'] = $validated['parent_account_id'] ?? null;

        ChartOfAccount::create($validated);

        return redirect()->route('admin.chart-of-accounts.index')->with('success', 'Akun baru berhasil ditambahkan.');
    }

    public function edit(ChartOfAccount $chartOfAccount): View
    {
        $parentAccounts = ChartOfAccount::where('account_id', '!=', $chartOfAccount->account_id) 
                            ->orderBy('account_number', 'asc')
                            ->get();
        $accountTypes = ['Aset', 'Liabilitas', 'Ekuitas', 'Pendapatan', 'HPP', 'Beban'];
        $normalBalances = ['Debit', 'Kredit'];

        return view('admin.chart_of_accounts.edit', compact('chartOfAccount', 'parentAccounts', 'accountTypes', 'normalBalances'));
    }

    public function update(Request $request, ChartOfAccount $chartOfAccount): RedirectResponse
    {
        $validated = $request->validate([
            'account_number' => [
                'required', 'string', 'max:20',
                Rule::unique('chart_of_accounts')->ignore($chartOfAccount->account_id, 'account_id')
            ],
            'account_name' => 'required|string|max:255',
            'account_type' => ['required', Rule::in(['Aset', 'Liabilitas', 'Ekuitas', 'Pendapatan', 'HPP', 'Beban'])],
            'normal_balance' => ['required', Rule::in(['Debit', 'Kredit'])],
            'parent_account_id' => 'nullable|exists:chart_of_accounts,account_id',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['parent_account_id'] = $validated['parent_account_id'] ?? null;
        $chartOfAccount->update($validated);

        return redirect()->route('admin.chart-of-accounts.index')->with('success', 'Akun berhasil diperbarui.');
    }

    public function destroy(ChartOfAccount $chartOfAccount): RedirectResponse
    {
        if ($chartOfAccount->children()->exists()) {
            return back()->with('error', 'Gagal: Akun ini tidak bisa dihapus karena memiliki akun anak.');
        }

        try {
            $chartOfAccount->delete();
            return redirect()->route('admin.chart-of-accounts.index')->with('success', 'Akun berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus akun: ' . $e->getMessage());
        }
    }
}