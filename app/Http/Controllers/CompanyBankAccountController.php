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
        $accounts = CompanyBankAccount::orderBy('bank_name')->get();

        return view('company_bank_accounts.index', compact('accounts'));
    }

    /**
     * Method create():
     * Menampilkan form untuk menambahkan akun bank baru.
     */
    public function create(): View
    {
        return view('company_bank_accounts.create');
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
        // Laravel otomatis melakukan model binding berdasarkan ID di route
        return view('company_bank_accounts.edit', [
            'account' => $companyBankAccount
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
