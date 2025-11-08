<?php

namespace App\Http\Controllers;

use App\Models\CompanyBankAccount;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

class CompanyBankAccountController extends Controller
{
    /**
     * Terapkan permission middleware ke semua method.
     */
    public function __construct()
    {
        $this->middleware('permission:manage-bank-accounts');
    }

    /**
     * Menampilkan daftar semua akun bank.
     */
    public function index(): View
    {
        $accounts = CompanyBankAccount::orderBy('bank_name')->get();
        return view('company_bank_accounts.index', compact('accounts'));
    }

    /**
     * Menampilkan form untuk membuat akun baru.
     */
    public function create(): View
    {
        return view('company_bank_accounts.create');
    }

    /**
     * Menyimpan akun baru ke database.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_number' => 'nullable|string|max:50',
            'is_active' => 'required|boolean',
        ]);

        CompanyBankAccount::create($validated);

        return redirect()->route('company-bank-accounts.index')
                         ->with('success', 'Akun bank baru berhasil ditambahkan.');
    }

    /**
     * Menampilkan form untuk mengedit akun.
     * Laravel secara otomatis akan menemukan 'company_bank_account'
     * berdasarkan 'company_bank_account_id' karena kita set $primaryKey di Model.
     */
    public function edit(CompanyBankAccount $companyBankAccount): View
    {
        // Parameter '$companyBankAccount' akan otomatis di-resolve oleh Laravel
        // menjadi $account di view berdasarkan nama variabel.
        return view('company_bank_accounts.edit', ['account' => $companyBankAccount]);
    }

    /**
     * Mengupdate akun di database.
     */
    public function update(Request $request, CompanyBankAccount $companyBankAccount): RedirectResponse
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_number' => 'nullable|string|max:50',
            'is_active' => 'required|boolean',
        ]);

        $companyBankAccount->update($validated);

        return redirect()->route('company-bank-accounts.index')
                         ->with('success', 'Akun bank berhasil diperbarui.');
    }

    /**
     * Menghapus akun dari database.
     */
    public function destroy(CompanyBankAccount $companyBankAccount): RedirectResponse
    {
        try {
            // Karena kita menggunakan ->nullOnDelete() pada migration tabel payments,
            // menghapus ini aman dan tidak akan menyebabkan error.
            // Transaksi lama akan memiliki company_bank_account_id = NULL.
            $companyBankAccount->delete();
            
            return redirect()->route('company-bank-accounts.index')
                             ->with('success', 'Akun bank berhasil dihapus.');
        } catch (\Exception $e) {
            // Menangkap error jika ada constraint lain
            return redirect()->route('company-bank-accounts.index')
                             ->with('error', 'Gagal menghapus akun. Pastikan tidak ada data terkait: ' . $e->getMessage());
        }
    }
}