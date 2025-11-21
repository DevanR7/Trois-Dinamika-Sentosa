<?php

namespace App\Http\Controllers;

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
        // (Anda bisa tambahkan permission baru 'manage-fixed-assets' jika mau)
        // $this->middleware('can:manage-fixed-assets')->except(['index']);
    }

    /**
     * Menampilkan daftar semua aset tetap.
     * (Versi ini sudah di-update di langkah sebelumnya, tidak perlu diubah)
     */
    public function index(Request $request): View
    {
        $query = FixedAsset::with([
            'user', 
            'assetAccount', 
            'cashBankAccount',
            'accumulatedDepreciationAccount', // ✅ Tambahkan relasi baru
            'depreciationExpenseAccount' // ✅ Tambahkan relasi baru
        ]);
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('asset_name', 'like', "%{$search}%");
        }
        $fixedAssets = $query->latest('purchase_date')->paginate(15)->appends($request->query());
        
        return view('fixed_assets.index', compact('fixedAssets'));
    }

    /**
     * Menampilkan form untuk membuat aset tetap baru.
     * ✅ DIPERBARUI: Ambil akun Aset, Kas, dan Beban
     */
    public function create(): View
    {
        // $this->authorize('create', FixedAsset::class);
        
        // Akun Aset (untuk Aset itu sendiri)
        $assetAccounts = ChartOfAccount::where('account_type', 'Aset')
                                ->where('is_active', true)
                                ->orderBy('account_number')
                                ->get();
        
        // Akun Kas/Bank (untuk Sumber Dana)
        $cashAccounts = $assetAccounts->filter(function($account) {
            // Asumsi akun kas/bank punya 'Kas' atau 'Bank' di namanya
            return str_contains($account->account_name, 'Kas') || str_contains($account->account_name, 'Bank');
        });

        // ✅ BARU: Akun Kontra-Aset (untuk Akumulasi Penyusutan)
        $contraAssetAccounts = $assetAccounts->filter(function($account) {
            return str_contains($account->account_name, 'Akumulasi');
        });

        // ✅ BARU: Akun Beban (untuk Beban Penyusutan)
        $expenseAccounts = ChartOfAccount::where('account_type', 'Beban')
                                ->where('is_active', true)
                                ->orderBy('account_number')
                                ->get();
                                
        return view('fixed_assets.create', compact(
            'assetAccounts', 
            'cashAccounts', 
            'contraAssetAccounts', 
            'expenseAccounts'
        ));
    }

    /**
     * Menyimpan aset tetap baru ke database.
     * ✅ DIPERBARUI: Validasi & Simpan kolom penyusutan
     */
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
            
            // ✅ Validasi kolom baru
            'accumulated_depreciation_account_id' => 'required|exists:chart_of_accounts,account_id',
            'depreciation_expense_account_id' => 'required|exists:chart_of_accounts,account_id',
            'depreciation_method' => 'required|in:straight_line,double_declining',
            'useful_life_months' => 'required|integer|min:1',
            'salvage_value' => 'required|numeric|min:0',
        ]);
        
        // Validasi nilai sisa
        if ($validated['salvage_value'] >= $validated['purchase_cost']) {
            return back()->with('error', 'Nilai Sisa tidak boleh lebih besar atau sama dengan Harga Beli.')->withInput();
        }
        
        DB::beginTransaction();
        try {
            // 1. Simpan data aset
            $asset = FixedAsset::create([
                'asset_name' => $validated['asset_name'],
                'purchase_date' => $validated['purchase_date'],
                'purchase_cost' => $validated['purchase_cost'],
                'description' => $validated['description'],
                'user_id' => Auth::id(),
                'fixed_asset_account_id' => $validated['fixed_asset_account_id'],
                'cash_bank_account_id' => $validated['cash_bank_account_id'],
                // ✅ Simpan data baru
                'accumulated_depreciation_account_id' => $validated['accumulated_depreciation_account_id'],
                'depreciation_expense_account_id' => $validated['depreciation_expense_account_id'],
                'depreciation_method' => $validated['depreciation_method'],
                'useful_life_months' => $validated['useful_life_months'],
                'salvage_value' => $validated['salvage_value'],
                // 'current_book_value' diatur otomatis oleh Model
            ]);

            // 2. Post Jurnal Akuntansi (Pembelian Aset)
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
                $asset, // Model referensi
                Auth::id()
            );
            
            DB::commit();
            return redirect()->route('fixed-assets.index')->with('success', 'Aset tetap berhasil dicatat dan dijurnal.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan data: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * show()
     * (Tidak ada perubahan)
     */
    public function show(FixedAsset $fixedAsset): RedirectResponse
    {
         return redirect()->route('fixed-assets.index');
    }

    /**
     * Menampilkan form untuk mengedit aset tetap.
     * ✅ DIPERBARUI: Ambil data COA
     */
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

        return view('fixed_assets.edit', compact(
            'fixedAsset', 
            'assetAccounts', 
            'cashAccounts', 
            'contraAssetAccounts', 
            'expenseAccounts'
        ));
    }

    /**
     * Mengupdate data aset tetap di database.
     * ✅ DIPERBARUI: Validasi & Update kolom penyusutan
     */
    public function update(Request $request, FixedAsset $fixedAsset): RedirectResponse
    {   
        $journalGroupId = "FASSET-" . $fixedAsset->asset_id;
        if ($error = $this->checkTransactionLock($fixedAsset->purchase_date, $journalGroupId)) {
            return back()->with('error', "Gagal Update: " . $error);
        }
        
        if ($request->filled('purchase_date') && $this->isDateClosed($request->purchase_date)) {
             return back()->with('error', "Gagal Update: Tanggal pembelian baru masuk periode tutup buku.");
        }

        // $this->authorize('update', $fixedAsset);
        
        // Pengecekan apakah aset sudah mulai disusutkan
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
            
            // ✅ Validasi kolom baru
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
            // 1. Update data aset
            $fixedAsset->update([
                'asset_name' => $validated['asset_name'],
                'purchase_date' => $validated['purchase_date'],
                'purchase_cost' => $validated['purchase_cost'],
                'description' => $validated['description'],
                'fixed_asset_account_id' => $validated['fixed_asset_account_id'],
                'cash_bank_account_id' => $validated['cash_bank_account_id'],
                // ✅ Update data baru
                'accumulated_depreciation_account_id' => $validated['accumulated_depreciation_account_id'],
                'depreciation_expense_account_id' => $validated['depreciation_expense_account_id'],
                'depreciation_method' => $validated['depreciation_method'],
                'useful_life_months' => $validated['useful_life_months'],
                'salvage_value' => $validated['salvage_value'],
                // ✅ Update Nilai Buku Awal
                'current_book_value' => $validated['purchase_cost'], 
            ]);

            // 2. Post ulang Jurnal Akuntansi (Service akan hapus yg lama)
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
                $fixedAsset, // Model referensi
                Auth::id()
            );

            DB::commit();
            return redirect()->route('fixed-assets.index')->with('success', 'Aset tetap berhasil diupdate.');
        
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mengupdate data: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Menghapus aset tetap dari database.
     * (Tidak ada perubahan)
     */
    public function destroy(FixedAsset $fixedAsset): RedirectResponse
    {   
        $journalGroupId = "FASSET-" . $fixedAsset->asset_id;
        if ($error = $this->checkTransactionLock($fixedAsset->purchase_date, $journalGroupId)) {
            return back()->with('error', "Gagal Hapus: " . $error);
        }
        
        // $this->authorize('delete', $fixedAsset);
        
        // Pengecekan apakah aset sudah mulai disusutkan
        if ($fixedAsset->depreciations()->exists()) {
             return back()->with('error', 'Gagal: Aset ini sudah memiliki riwayat penyusutan dan tidak dapat dihapus.');
        }

        DB::beginTransaction();
        try {
            // 1. Post Jurnal Reversal (Pembalikan)
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
                now(), // Tanggal reversal
                $description,
                $debitEntries,
                $creditEntries,
                $fixedAsset,
                Auth::id()
            );

            // 2. Hapus Jurnal Asli (FASSET-...)
            DB::table('general_ledgers')->where('journal_group_id', "FASSET-" . $fixedAsset->asset_id)->delete();
            
            // 3. Hapus data aset
            $fixedAsset->delete();
            
            DB::commit();
            return redirect()->route('fixed-assets.index')->with('success', 'Aset tetap berhasil dihapus.');
        
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus aset: ' . $e->getMessage());
        }
    }
}