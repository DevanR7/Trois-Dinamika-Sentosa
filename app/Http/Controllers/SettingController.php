<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Models\ChartOfAccount;
use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    /**
     * Konstruktor: menerapkan middleware otorisasi untuk akses ke pengaturan sistem.
     */
    public function __construct()
    {
        $this->middleware('can:manage-settings');
    }

    /**
     * Menampilkan halaman form pengaturan sistem.
     */
    public function index(): View 
    {
        $settings = Setting::getAllSettings();
        
        $assetAccounts = ChartOfAccount::where('account_type', 'Aset')->where('is_active', true)->orderBy('account_number')->get();
        $liabilityAccounts = ChartOfAccount::where('account_type', 'Liabilitas')->where('is_active', true)->orderBy('account_number')->get();
        $revenueAccounts = ChartOfAccount::where('account_type', 'Pendapatan')->where('is_active', true)->orderBy('account_number')->get();
        $cogsAccounts = ChartOfAccount::where('account_type', 'HPP')->where('is_active', true)->orderBy('account_number')->get();
        $expenseOrRevenueAccounts = ChartOfAccount::whereIn('account_type', ['Beban', 'Pendapatan'])
                                        ->where('is_active', true)
                                        ->orderBy('account_number')
                                        ->get();
        // ✅ BARU: Akun untuk deposit bisa Aset (Uang Muka) atau Liabilitas
        $assetOrLiabilityAccounts = ChartOfAccount::whereIn('account_type', ['Aset', 'Liabilitas'])
                                        ->where('is_active', true)
                                        ->orderBy('account_number')
                                        ->get();


        return view('settings.index', compact(
            'settings',
            'assetAccounts',
            'liabilityAccounts',
            'revenueAccounts',
            'cogsAccounts',
            'expenseOrRevenueAccounts',
            'assetOrLiabilityAccounts',
            'assetOrLiabilityAccounts' 
        ));
    }

    /**
     * Memperbarui semua pengaturan berdasarkan input dari form.
     */
    public function update(Request $request): RedirectResponse
    {
        // ✅ VALIDASI (Penting untuk data akuntansi)
        $request->validate([
            // Validasi Akun (Minimal harus ada)
            'acct_default_ar' => 'nullable|exists:chart_of_accounts,account_id',
            'acct_default_ap' => 'nullable|exists:chart_of_accounts,account_id',
            'acct_default_inventory' => 'nullable|exists:chart_of_accounts,account_id',
            'acct_default_sales_revenue' => 'nullable|exists:chart_of_accounts,account_id',
            'acct_default_cogs' => 'nullable|exists:chart_of_accounts,account_id',
            'acct_default_sales_return' => 'nullable|exists:chart_of_accounts,account_id',
            'acct_default_purchase_return' => 'nullable|exists:chart_of_accounts,account_id',
            'acct_default_supplier_deposit' => 'nullable|exists:chart_of_accounts,account_id',
            'acct_default_client_deposit' => 'nullable|exists:chart_of_accounts,account_id',
            'acct_default_gateway' => 'nullable|exists:chart_of_accounts,account_id',
            
            // Validasi data perusahaan (dari view Anda)
            'company_name' => 'required|string|max:255',
            'company_owner' => 'required|string|max:255',
            'company_address' => 'nullable|string',
            'company_city_province' => 'nullable|string|max:255',
            'company_phone' => 'nullable|string|max:20',
            'company_npwp' => 'nullable|string|max:30',
            'system_version' => 'nullable|string|max:20',
        ]);

        // Iterasi semua input kecuali token CSRF
        foreach ($request->except('_token', '_method') as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? ''] // Simpan string kosong jika null
            );
        }
        
        // Cache akan otomatis di-forget oleh Model Setting

        return back()->with('success', 'Pengaturan berhasil diperbarui.');
    }
}