<?php

namespace App\Http\Controllers;

use App\Models\FixedAsset; // Pastikan Model di-import
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Untuk melacak siapa yang input
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Models\ChartOfAccount;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;

class FixedAssetController extends Controller
{   
    protected $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
        
        // ✅ TAMBAHKAN BLOK INI
        // Hanya yang bisa 'view-reports' boleh lihat daftar (index)
        $this->middleware('can:view-reports')->only(['index']);
        
        // (Opsional) Jika Anda ingin permission terpisah
        // $this->middleware('can:manage-fixed-assets')->except(['index']);
    }

    /**
     * Menampilkan daftar semua aset tetap.
     */
    public function index(Request $request): View
    {
        // $this->authorize('viewAny', FixedAsset::class);
        
        // ✅ Perbarui query untuk load relasi baru
        $query = FixedAsset::with(['user', 'assetAccount', 'cashBankAccount']);
        
        // Filter pencarian (Sama)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('asset_name', 'like', "%{$search}%");
        }
        $fixedAssets = $query->latest('purchase_date')->paginate(15)->appends($request->query());
        return view('fixed_assets.index', compact('fixedAssets'));
    }
    /**
     * Menampilkan form untuk membuat aset tetap baru.
     */
    public function create(): View
    {
        // $this->authorize('create', FixedAsset::class);
        
        // ✅ Ambil akun Aset dari COA
        $assetAccounts = ChartOfAccount::where('account_type', 'Aset')
                                ->where('is_active', true)
                                ->orderBy('account_number')
                                ->get();
        
        // ✅ Ambil akun Sumber Dana (Kas/Bank) dari COA
        $cashAccounts = ChartOfAccount::where('account_type', 'Aset')
                                ->where('is_active', true)
                                ->orderBy('account_number')
                                ->get();
                                
        return view('fixed_assets.create', compact('assetAccounts', 'cashAccounts'));
    }

    /**
     * Menyimpan aset tetap baru ke database.
     */
    public function store(Request $request): RedirectResponse
    {
        // $this->authorize('create', FixedAsset::class);
        $validated = $request->validate([
            'asset_name' => 'required|string|max:255',
            'purchase_date' => 'required|date',
            'purchase_cost' => 'required|numeric|min:1',
            'description' => 'nullable|string',
            // ✅ Validasi kolom baru
            'fixed_asset_account_id' => 'required|exists:chart_of_accounts,account_id',
            'cash_bank_account_id' => 'required|exists:chart_of_accounts,account_id',
        ]);
        
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
            ]);

            // 2. Post Jurnal Akuntansi
            $journalGroupId = "FASSET-" . $asset->asset_id;
            $description = "Pembelian Aset Tetap: " . $asset->asset_name;

            $debitEntries = [
                // [Akun Aset, Jumlah]
                [$validated['fixed_asset_account_id'], $validated['purchase_cost']]
            ];
            $creditEntries = [
                // [Akun Kas/Bank, Jumlah]
                [$validated['cash_bank_account_id'], $validated['purchase_cost']]
            ];

            $this->accountingService->postJournal(
                $journalGroupId,
                $validated['purchase_date'],
                $description,
                $debitEntries,
                $creditEntries,
                $asset // Model referensi
            );
            
            DB::commit();
            return redirect()->route('fixed-assets.index')->with('success', 'Aset tetap berhasil dicatat dan dijurnal.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan data: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Menampilkan detail (show) - Kita tidak pakai, jadi redirect ke index.
     */
    public function show(FixedAsset $fixedAsset): RedirectResponse
    {
         return redirect()->route('fixed-assets.index');
    }

    /**
     * Menampilkan form untuk mengedit aset tetap.
     */
    public function edit(FixedAsset $fixedAsset): View
    {
        // $this->authorize('update', $fixedAsset);
        
        $assetAccounts = ChartOfAccount::where('account_type', 'Aset')
                                ->where('is_active', true)
                                ->orderBy('account_number')
                                ->get();
        $cashAccounts = ChartOfAccount::where('account_type', 'Aset')
                                ->where('is_active', true)
                                ->orderBy('account_number')
                                ->get();

        return view('fixed_assets.edit', compact('fixedAsset', 'assetAccounts', 'cashAccounts'));
    }

    /**
     * Mengupdate data aset tetap di database.
     */
    public function update(Request $request, FixedAsset $fixedAsset): RedirectResponse
    {
        // $this->authorize('update', $fixedAsset);
        $validated = $request->validate([
            'asset_name' => 'required|string|max:255',
            'purchase_date' => 'required|date',
            'purchase_cost' => 'required|numeric|min:1',
            'description' => 'nullable|string',
            // ✅ Validasi kolom baru
            'fixed_asset_account_id' => 'required|exists:chart_of_accounts,account_id',
            'cash_bank_account_id' => 'required|exists:chart_of_accounts,account_id',
        ]);
        
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
                $fixedAsset // Model referensi
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
     */
    public function destroy(FixedAsset $fixedAsset): RedirectResponse
    {
        // $this->authorize('delete', $fixedAsset);
        
        DB::beginTransaction();
        try {
            // 1. Post Jurnal Reversal (Pembalikan)
            $journalGroupId = "FASSET-REVERSAL-" . $fixedAsset->asset_id;
            $description = "Reversal Pembelian Aset: " . $fixedAsset->asset_name;

            $debitEntries = [
                // [Akun Kas/Bank, Jumlah] (Kas kembali)
                [$fixedAsset->cash_bank_account_id, $fixedAsset->purchase_cost]
            ];
            $creditEntries = [
                // [Akun Aset, Jumlah] (Aset berkurang)
                [$fixedAsset->fixed_asset_account_id, $fixedAsset->purchase_cost]
            ];
            
            $this->accountingService->postJournal(
                $journalGroupId,
                now(), // Tanggal reversal
                $description,
                $debitEntries,
                $creditEntries,
                $fixedAsset
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