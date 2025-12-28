<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FixedAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Models\ChartOfAccount;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use App\Traits\ValidatesAccountingPeriod;   

class FixedAssetController extends Controller
{
    use ValidatesAccountingPeriod;
    protected $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
        $this->middleware('can:view-reports')->only(['index']);
        // $this->middleware('can:manage-fixed-assets')->except(['index']);
    }

    public function index(Request $request): View
    {
        $query = FixedAsset::with([
            'user', 
            'assetAccount', 
            'cashBankAccount',
            'accumulatedDepreciationAccount', 
            'depreciationExpenseAccount'
        ]);
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('asset_name', 'like', "%{$search}%");
        }
        $fixedAssets = $query->latest('purchase_date')->paginate(15)->appends($request->query());
        
        return view('admin.fixed_assets.index', compact('fixedAssets'));
    }

    public function create(): View
    {
        // $this->authorize('create', FixedAsset::class);
        $assetAccounts = ChartOfAccount::where('account_type', 'Aset')
                                ->where('is_active', true)
                                ->orderBy('account_number')
                                ->get();

        $cashAccounts = $assetAccounts->filter(function($account) {
            return str_contains($account->account_name, 'Kas') || str_contains($account->account_name, 'Bank');
        });
        $contraAssetAccounts = $assetAccounts->filter(function($account) {
            return str_contains($account->account_name, 'Akumulasi');
        });

        $expenseAccounts = ChartOfAccount::where('account_type', 'Beban')
                                ->where('is_active', true)
                                ->orderBy('account_number')
                                ->get();
                                
        return view('admin.fixed_assets.create', compact(
            'assetAccounts', 
            'cashAccounts', 
            'contraAssetAccounts', 
            'expenseAccounts'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        // $this->authorize('create', FixedAsset::class);
        $validated = $request->validate([
            'asset_name' => 'required|string|max:255',
            'purchase_date' => 'required|date',
            'purchase_cost' => 'required|numeric|min:1',
            'description' => 'nullable|string',
            'fixed_asset_account_id' => 'required|exists:chart_of_accounts,account_id',
            'cash_bank_account_id' => 'required|exists:chart_of_accounts,account_id',
            'accumulated_depreciation_account_id' => 'required|exists:chart_of_accounts,account_id',
            'depreciation_expense_account_id' => 'required|exists:chart_of_accounts,account_id',
            'depreciation_method' => 'required|in:straight_line,double_declining',
            'useful_life_months' => 'required|integer|min:1',
            'salvage_value' => 'required|numeric|min:0',
        ]);
        
        if ($validated['salvage_value'] >= $validated['purchase_cost']) {
            return back()->with('error', 'Nilai Sisa tidak boleh lebih besar atau sama dengan Harga Beli.')->withInput();
        }
        
        DB::beginTransaction();
        try {
            $asset = FixedAsset::create([
                'asset_name' => $validated['asset_name'],
                'purchase_date' => $validated['purchase_date'],
                'purchase_cost' => $validated['purchase_cost'],
                'description' => $validated['description'],
                'user_id' => Auth::id(),
                'fixed_asset_account_id' => $validated['fixed_asset_account_id'],
                'cash_bank_account_id' => $validated['cash_bank_account_id'],
                'accumulated_depreciation_account_id' => $validated['accumulated_depreciation_account_id'],
                'depreciation_expense_account_id' => $validated['depreciation_expense_account_id'],
                'depreciation_method' => $validated['depreciation_method'],
                'useful_life_months' => $validated['useful_life_months'],
                'salvage_value' => $validated['salvage_value'],
            ]);

            $journalGroupId = "FASSET-" . $asset->asset_id;
            $description = "Pembelian Aset Tetap: " . $asset->asset_name;

            $debitEntries = [
                [$validated['fixed_asset_account_id'], $validated['purchase_cost']]
            ];
            $creditEntries = [
                [$validated['cash_bank_account_id'], $validated['purchase_cost']]
            ];

            $this->accountingService->postJournal(
                $journalGroupId,
                $validated['purchase_date'],
                $description,
                $debitEntries,
                $creditEntries,
                $asset, 
                Auth::id()
            );
            
            DB::commit();
            return redirect()->route('admin.fixed-assets.index')->with('success', 'Aset tetap berhasil dicatat dan dijurnal.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan data: ' . $e->getMessage())->withInput();
        }
    }

    public function show(FixedAsset $fixedAsset): RedirectResponse
    {
        return redirect()->route('admin.fixed-assets.index');
    }

    public function edit(FixedAsset $fixedAsset): View
    {
        // $this->authorize('update', $fixedAsset);
        
        $assetAccounts = ChartOfAccount::where('account_type', 'Aset')
                                ->where('is_active', true)
                                ->orderBy('account_number')
                                ->get();
        $cashAccounts = $assetAccounts->filter(function($account) {
            return str_contains($account->account_name, 'Kas') || str_contains($account->account_name, 'Bank');
        });
        $contraAssetAccounts = $assetAccounts->filter(function($account) {
            return str_contains($account->account_name, 'Akumulasi');
        });
        $expenseAccounts = ChartOfAccount::where('account_type', 'Beban')
                                ->where('is_active', true)
                                ->orderBy('account_number')
                                ->get();

        return view('admin.fixed_assets.edit', compact(
            'fixedAsset', 
            'assetAccounts', 
            'cashAccounts', 
            'contraAssetAccounts', 
            'expenseAccounts'
        ));
    }

    public function update(Request $request, FixedAsset $fixedAsset): RedirectResponse
    {   
        // $this->authorize('update', $fixedAsset);

        $journalGroupId = "FASSET-" . $fixedAsset->asset_id;

        if ($error = $this->checkTransactionLock($fixedAsset->purchase_date, $journalGroupId)) {
            return back()->with('error', "Gagal Update: " . $error);
        }   
        if ($request->filled('purchase_date') && $this->isDateClosed($request->purchase_date)) {
             return back()->with('error', "Gagal Update: Tanggal pembelian baru masuk periode tutup buku.");
        }
        if ($fixedAsset->depreciations()->exists()) {
             return back()->with('error', 'Gagal: Aset ini sudah memiliki riwayat penyusutan dan tidak dapat diubah lagi.');
        }
        
        $validated = $request->validate([
            'asset_name' => 'required|string|max:255',
            'purchase_date' => 'required|date',
            'purchase_cost' => 'required|numeric|min:1',
            'description' => 'nullable|string',
            'fixed_asset_account_id' => 'required|exists:chart_of_accounts,account_id',
            'cash_bank_account_id' => 'required|exists:chart_of_accounts,account_id',
            'accumulated_depreciation_account_id' => 'required|exists:chart_of_accounts,account_id',
            'depreciation_expense_account_id' => 'required|exists:chart_of_accounts,account_id',
            'depreciation_method' => 'required|in:straight_line,double_declining',
            'useful_life_months' => 'required|integer|min:1',
            'salvage_value' => 'required|numeric|min:0',
        ]);
        
        if ($validated['salvage_value'] >= $validated['purchase_cost']) {
            return back()->with('error', 'Nilai Sisa tidak boleh lebih besar atau sama dengan Harga Beli.')->withInput();
        }
        
        DB::beginTransaction();
        try {
            $fixedAsset->update([
                'asset_name' => $validated['asset_name'],
                'purchase_date' => $validated['purchase_date'],
                'purchase_cost' => $validated['purchase_cost'],
                'description' => $validated['description'],
                'fixed_asset_account_id' => $validated['fixed_asset_account_id'],
                'cash_bank_account_id' => $validated['cash_bank_account_id'],
                'accumulated_depreciation_account_id' => $validated['accumulated_depreciation_account_id'],
                'depreciation_expense_account_id' => $validated['depreciation_expense_account_id'],
                'depreciation_method' => $validated['depreciation_method'],
                'useful_life_months' => $validated['useful_life_months'],
                'salvage_value' => $validated['salvage_value'],
                'current_book_value' => $validated['purchase_cost'], 
            ]);

            $journalGroupId = "FASSET-" . $fixedAsset->asset_id;
            $description = "Pembelian Aset (Update): " . $fixedAsset->asset_name;

            $debitEntries = [
                [$validated['fixed_asset_account_id'], $validated['purchase_cost']]
            ];
            $creditEntries = [
                [$validated['cash_bank_account_id'], $validated['purchase_cost']]
            ];

            $this->accountingService->postJournal(
                $journalGroupId,
                $validated['purchase_date'],
                $description,
                $debitEntries,
                $creditEntries,
                $fixedAsset,
                Auth::id()
            );

            DB::commit();
            return redirect()->route('admin.fixed-assets.index')->with('success', 'Aset tetap berhasil diupdate.');
        
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mengupdate data: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(FixedAsset $fixedAsset): RedirectResponse
    {   
        // $this->authorize('delete', $fixedAsset);

        $journalGroupId = "FASSET-" . $fixedAsset->asset_id;

        if ($error = $this->checkTransactionLock($fixedAsset->purchase_date, $journalGroupId)) {
            return back()->with('error', "Gagal Hapus: " . $error);
        }
        if ($fixedAsset->depreciations()->exists()) {
             return back()->with('error', 'Gagal: Aset ini sudah memiliki riwayat penyusutan dan tidak dapat dihapus.');
        }

        DB::beginTransaction();
        try {
            $journalGroupId = "FASSET-REVERSAL-" . $fixedAsset->asset_id;
            $description = "Reversal Pembelian Aset: " . $fixedAsset->asset_name;

            $debitEntries = [
                [$fixedAsset->cash_bank_account_id, $fixedAsset->purchase_cost]
            ];
            $creditEntries = [
                [$fixedAsset->fixed_asset_account_id, $fixedAsset->purchase_cost]
            ];
            
            $this->accountingService->postJournal(
                $journalGroupId,
                now(), 
                $description,
                $debitEntries,
                $creditEntries,
                $fixedAsset,
                Auth::id()
            );

            DB::table('general_ledgers')->where('journal_group_id', "FASSET-" . $fixedAsset->asset_id)->delete();
           
            $fixedAsset->delete();
            
            DB::commit();
            return redirect()->route('admin.fixed-assets.index')->with('success', 'Aset tetap berhasil dihapus.');
        
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus aset: ' . $e->getMessage());
        }
    }
}