<?php

namespace App\Http\Controllers;

use App\Models\CompanyBankAccount;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use App\Models\ChartOfAccount;

class CompanyBankAccountController extends Controller
{
    /**
     * Konstruktor:
     * Menerapkan middleware permission agar hanya user dengan izin tertentu
     * yang bisa mengelola data akun bank perusahaan.
     */
    public function __construct()
    {
        $this->middleware('permission:manage-bank-accounts');
    }

    /**
     * Method index():
     * Menampilkan daftar seluruh akun bank perusahaan.
     */
    public function index(): View
    {
        // ✅ Eager load relasi 'account'
        $accounts = CompanyBankAccount::with('account')->orderBy('bank_name')->get();
        return view('company_bank_accounts.index', compact('accounts'));
    }

    /**
     * Method create():
     * Menampilkan form untuk menambahkan akun bank baru.
     */
    public function create(): View
    {
        // ✅ Ambil semua akun COA tipe 'Aset'
        $assetAccounts = ChartOfAccount::where('account_type', 'Aset')
                            ->where('is_active', true)
                            ->orderBy('account_number')
                            ->get();
                            
        return view('company_bank_accounts.create', compact('assetAccounts'));
    }

    /**
     * Method store():
     * Menyimpan data akun bank baru ke dalam database.
     */
    public function store(Request $request): RedirectResponse
    {
        // Validasi input dari form
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_number' => 'nullable|string|max:50',
            'is_active' => 'required|boolean',
            // ✅ Validasi baru
            'chart_of_account_id' => 'required|exists:chart_of_accounts,account_id',
        ]);

        // Simpan ke database
        CompanyBankAccount::create($validated);
        
        return redirect()
            ->route('company-bank-accounts.index')
            ->with('success', 'Akun bank baru berhasil ditambahkan.');
    }

    /**
     * Method edit():
     * Menampilkan form untuk mengedit data akun bank yang sudah ada.
     */
    public function edit(CompanyBankAccount $companyBankAccount): View
    {
        // ✅ Ambil semua akun COA tipe 'Aset'
        $assetAccounts = ChartOfAccount::where('account_type', 'Aset')
                            ->where('is_active', true)
                            ->orderBy('account_number')
                            ->get();
                            
        return view('company_bank_accounts.edit', [
            'account' => $companyBankAccount,
            'assetAccounts' => $assetAccounts // ✅ Kirim ke view
        ]);
    }

    /**
     * Method update():
     * Memperbarui data akun bank yang dipilih.
     */
    public function update(Request $request, CompanyBankAccount $companyBankAccount): RedirectResponse
    {
        // Validasi data baru dari form
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_number' => 'nullable|string|max:50',
            'is_active' => 'required|boolean',
            // ✅ Validasi baru
            'chart_of_account_id' => 'required|exists:chart_of_accounts,account_id',
        ]);

        // Update data di database
        $companyBankAccount->update($validated);
        
        return redirect()
            ->route('company-bank-accounts.index')
            ->with('success', 'Akun bank berhasil diperbarui.');
    }

    /**
     * Method destroy():
     * Menghapus akun bank dari database.
     * Data transaksi lama akan otomatis diatur NULL berkat relasi nullOnDelete().
     */
    public function destroy(CompanyBankAccount $companyBankAccount): RedirectResponse
    {
        try {
            $companyBankAccount->delete();

            return redirect()
                ->route('company-bank-accounts.index')
                ->with('success', 'Akun bank berhasil dihapus.');
        } catch (\Exception $e) {
            // Tangkap kesalahan apabila ada constraint lain di database
            return redirect()
                ->route('company-bank-accounts.index')
                ->with('error', 'Gagal menghapus akun. Pastikan tidak ada data terkait: ' . $e->getMessage());
        }
    }
}
