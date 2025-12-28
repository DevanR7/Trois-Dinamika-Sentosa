<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Models\ChartOfAccount;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-settings');
    }

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

        $assetOrLiabilityAccounts = ChartOfAccount::whereIn('account_type', ['Aset', 'Liabilitas'])
                                        ->where('is_active', true)
                                        ->orderBy('account_number')
                                        ->get();

        $equityAccounts = ChartOfAccount::where('account_type', 'Ekuitas')->where('is_active', true)->orderBy('account_number')->get();


        return view('admin.settings.index', compact(
            'settings',
            'assetAccounts',
            'liabilityAccounts',
            'revenueAccounts',
            'cogsAccounts',
            'expenseOrRevenueAccounts',
            'assetOrLiabilityAccounts',
            'assetOrLiabilityAccounts',
            'equityAccounts' 
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
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
            'acct_default_inventory_adjustment' => 'nullable|exists:chart_of_accounts,account_id',
            'company_name' => 'required|string|max:255',
            'company_owner' => 'required|string|max:255',
            'company_address' => 'nullable|string',
            'company_city_province' => 'nullable|string|max:255',
            'company_phone' => 'nullable|string|max:20',
            'company_npwp' => 'nullable|string|max:30',
            'system_version' => 'nullable|string|max:20',
        ]);

        foreach ($request->except('_token', '_method') as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? '']
            );
        }

        return back()->with('success', 'Pengaturan berhasil diperbarui.');
    }
}