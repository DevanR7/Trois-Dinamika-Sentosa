<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

class ChartOfAccountController extends Controller
{
    /**
     * Terapkan middleware. Hanya yang bisa 'manage-settings' boleh akses.
     */
    public function __construct()
    {
        // Gunakan permission yang sudah ada untuk pengaturan
        $this->middleware('can:manage-settings'); 
    }

    /**
     * Menampilkan daftar akun (hierarkis).
     */
    public function index(): View
    {
        // Ambil hanya akun induk (parent) dan muat anak-anaknya
        $parentAccounts = ChartOfAccount::whereNull('parent_account_id')
                            ->with('children') // Eager load children
                            ->orderBy('account_number', 'asc')
                            ->get();
                            
        return view('chart_of_accounts.index', compact('parentAccounts'));
    }

    /**
     * Menampilkan form untuk membuat akun baru.
     */
    public function create(): View
    {
        // Ambil semua akun untuk dropdown "Akun Induk"
        $parentAccounts = ChartOfAccount::orderBy('account_number', 'asc')->get();
        $accountTypes = ['Aset', 'Liabilitas', 'Ekuitas', 'Pendapatan', 'HPP', 'Beban'];
        $normalBalances = ['Debit', 'Kredit'];

        return view('chart_of_accounts.create', compact('parentAccounts', 'accountTypes', 'normalBalances'));
    }

    /**
     * Menyimpan akun baru ke database.
     */
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
        
        // Jika parent_account_id tidak diisi, set ke null
        $validated['parent_account_id'] = $validated['parent_account_id'] ?? null;

        ChartOfAccount::create($validated);

        return redirect()->route('chart-of-accounts.index')->with('success', 'Akun baru berhasil ditambahkan.');
    }

    /**
     * Menampilkan form untuk mengedit akun.
     */
    public function edit(ChartOfAccount $chartOfAccount): View
    {
        $parentAccounts = ChartOfAccount::where('account_id', '!=', $chartOfAccount->account_id) // Tidak bisa jadi induk dirinya sendiri
                            ->orderBy('account_number', 'asc')
                            ->get();
        $accountTypes = ['Aset', 'Liabilitas', 'Ekuitas', 'Pendapatan', 'HPP', 'Beban'];
        $normalBalances = ['Debit', 'Kredit'];

        return view('chart_of_accounts.edit', compact('chartOfAccount', 'parentAccounts', 'accountTypes', 'normalBalances'));
    }

    /**
     * Mengupdate akun di database.
     */
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

        return redirect()->route('chart-of-accounts.index')->with('success', 'Akun berhasil diperbarui.');
    }

    /**
     * Menghapus akun.
     */
    public function destroy(ChartOfAccount $chartOfAccount): RedirectResponse
    {
        // PENTING: Cek apakah akun ini punya anak
        if ($chartOfAccount->children()->exists()) {
            return back()->with('error', 'Gagal: Akun ini tidak bisa dihapus karena memiliki akun anak.');
        }

        // PENTING: (Nanti) Cek apakah akun ini sudah pernah dipakai di Jurnal Umum
        // if ($chartOfAccount->journalEntries()->exists()) {
        //     return back()->with('error', 'Gagal: Akun ini tidak bisa dihapus karena sudah memiliki riwayat transaksi.');
        // }
        
        try {
            $chartOfAccount->delete();
            return redirect()->route('chart-of-accounts.index')->with('success', 'Akun berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus akun: ' . $e->getMessage());
        }
    }
}