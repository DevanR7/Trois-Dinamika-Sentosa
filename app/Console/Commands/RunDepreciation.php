<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FixedAsset;
use App\Models\Depreciation;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class RunDepreciation extends Command
{
    protected $signature = 'accounting:run-depreciation {--month= : Bulan dan tahun (YYYY-MM) yang akan disusutkan. Default: bulan lalu.}';
    protected $description = 'Menghitung dan mem-posting jurnal penyusutan aset tetap untuk bulan yang ditentukan.';
    protected $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        parent::__construct();
        $this->accountingService = $accountingService;
    }

    public function handle()
    {
        $this->info('==================================================');
        $this->info('Memulai Proses Penyusutan Aset Tetap...');
        $this->info('==================================================');

        try {
            $monthInput = $this->option('month');
            $targetDate = $monthInput 
                ? Carbon::parse($monthInput)->endOfMonth() 
                : now()->subMonth()->endOfMonth(); 

            $this->warn('Menjalankan penyusutan untuk periode: ' . $targetDate->format('F Y'));

            $assets = FixedAsset::where('useful_life_months', '>', 0)
                                ->where('purchase_date', '<=', $targetDate)
                                ->where('current_book_value', '>', DB::raw('salvage_value'))
                                ->get();
            
            if ($assets->isEmpty()) {
                $this->info('Tidak ada aset yang memenuhi syarat untuk disusutkan periode ini.');
                return 0;
            }

            $successCount = 0;
            $skippedCount = 0;

            foreach ($assets as $asset) {
                
                $alreadyDepreciated = $asset->depreciations()
                    ->whereYear('depreciation_date', $targetDate->year)
                    ->whereMonth('depreciation_date', $targetDate->month)
                    ->exists();

                if ($alreadyDepreciated) {
                    $this->line("  -> Aset '{$asset->asset_name}' (ID: {$asset->asset_id}) sudah disusutkan. Dilewati.");
                    $skippedCount++;
                    continue;
                }

                $monthlyDepreciation = 0;
                $remainingValue = $asset->current_book_value - $asset->salvage_value;

                if ($asset->depreciation_method === 'double_declining') {
                    $usefulLifeYears = $asset->useful_life_months / 12;
                    
                    if ($usefulLifeYears > 0) {
                         $rate = (1 / $usefulLifeYears) * 2; 
                         $monthlyDepreciation = ($asset->current_book_value * $rate) / 12;
                    }
                } else {
                    $depreciableBase = $asset->purchase_cost - $asset->salvage_value;
                    $monthlyDepreciation = $depreciableBase / $asset->useful_life_months;
                }

                $depreciationAmount = min($monthlyDepreciation, $remainingValue);

                if ($depreciationAmount <= 0.01) {
                    $this->line("  -> Aset '{$asset->asset_name}' (ID: {$asset->asset_id}) sudah mencapai nilai sisa. Dilewati.");
                    $skippedCount++;
                    continue;
                }

                if (!$asset->depreciation_expense_account_id || !$asset->accumulated_depreciation_account_id) {
                    $this->error("  -> GAGAL: Aset '{$asset->asset_name}' (ID: {$asset->asset_id}) tidak memiliki Akun COA. Dilewati.");
                    Log::error("Penyusutan Gagal: Aset ID {$asset->asset_id} tidak punya akun COA.");
                    $skippedCount++;
                    continue;
                }

                DB::transaction(function () use ($asset, $targetDate, $depreciationAmount) {

                    $journalGroupId = "DEP-" . $asset->asset_id . "-" . $targetDate->format('Ym');
                    $description = "Penyusutan bulanan {$targetDate->format('F Y')} - {$asset->asset_name}";

                    $debitEntries = [
                        [$asset->depreciation_expense_account_id, $depreciationAmount, $description]
                    ];
                    $creditEntries = [
                        [$asset->accumulated_depreciation_account_id, $depreciationAmount, $description]
                    ];
                    
                    $this->accountingService->postJournal(
                        $journalGroupId,
                        $targetDate,
                        $description,
                        $debitEntries,
                        $creditEntries,
                        $asset,
                        null 
                    );

                    $asset->depreciations()->create([
                        'depreciation_date' => $targetDate,
                        'amount' => $depreciationAmount,
                        'journal_group_id' => $journalGroupId,
                    ]);

                    $asset->decrement('current_book_value', $depreciationAmount);
                    
                });

                $this->info("  -> SUKSES: Aset '{$asset->asset_name}' (ID: {$asset->asset_id}) disusutkan sebesar Rp " . number_format($depreciationAmount));
                $successCount++;
            }

            $this->info('==================================================');
            $this->info("Proses Selesai. $successCount aset berhasil disusutkan, $skippedCount aset dilewati.");
            return 0;

        } catch (\Exception $e) {
            $this->error('Terjadi error saat menjalankan penyusutan: ' . $e->getMessage());
            Log::error('Cronjob Penyusutan Gagal Total: ' . $e->getMessage() . ' on line ' . $e->getLine());
            return 1;
        }
    }
}