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
    /**
     * Nama dan signature dari console command.
     */
    protected $signature = 'accounting:run-depreciation {--month= : Bulan dan tahun (YYYY-MM) yang akan disusutkan. Default: bulan lalu.}';

    /**
     * Deskripsi console command.
     */
    protected $description = 'Menghitung dan mem-posting jurnal penyusutan aset tetap untuk bulan yang ditentukan.';

    protected $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        parent::__construct();
        $this->accountingService = $accountingService;
    }

    /**
     * Jalankan console command.
     */
    public function handle()
    {
        $this->info('==================================================');
        $this->info('Memulai Proses Penyusutan Aset Tetap...');
        $this->info('==================================================');

        try {
            // 1. Tentukan Tanggal (Periode) Penyusutan
            $monthInput = $this->option('month');
            $targetDate = $monthInput 
                ? Carbon::parse($monthInput)->endOfMonth() 
                : now()->subMonth()->endOfMonth(); // Default: Akhir bulan lalu

            $this->warn('Menjalankan penyusutan untuk periode: ' . $targetDate->format('F Y'));

            // 2. Ambil semua aset yang memenuhi syarat
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
                
                // 3. Cek apakah sudah disusutkan untuk bulan ini
                $alreadyDepreciated = $asset->depreciations()
                    ->whereYear('depreciation_date', $targetDate->year)
                    ->whereMonth('depreciation_date', $targetDate->month)
                    ->exists();

                if ($alreadyDepreciated) {
                    $this->line("  -> Aset '{$asset->asset_name}' (ID: {$asset->asset_id}) sudah disusutkan. Dilewati.");
                    $skippedCount++;
                    continue;
                }

                // ========================================================
                // 4. ✅ LOGIKA BARU: DUAL METHOD (GARIS LURUS / SALDO MENURUN)
                // ========================================================
                
                $monthlyDepreciation = 0;
                $remainingValue = $asset->current_book_value - $asset->salvage_value;

                if ($asset->depreciation_method === 'double_declining') {
                    // --- METODE SALDO MENURUN GANDA (Double Declining) ---
                    // Rumus: (Nilai Buku Saat Ini x Rate) / 12
                    // Rate = (100% / Umur Tahun) * 2
                    
                    // Konversi bulan ke tahun (misal 48 bulan = 4 tahun)
                    $usefulLifeYears = $asset->useful_life_months / 12;
                    
                    if ($usefulLifeYears > 0) {
                         $rate = (1 / $usefulLifeYears) * 2; // Double rate
                         $monthlyDepreciation = ($asset->current_book_value * $rate) / 12;
                    }
                } else {
                    // --- METODE GARIS LURUS (Straight Line) - DEFAULT ---
                    // Rumus: (Harga Beli - Residu) / Umur Bulan
                    $depreciableBase = $asset->purchase_cost - $asset->salvage_value;
                    $monthlyDepreciation = $depreciableBase / $asset->useful_life_months;
                }

                // 5. Cek Batas Nilai Sisa (Safety Net)
                // Penyusutan tidak boleh membuat nilai buku turun di bawah nilai sisa
                $depreciationAmount = min($monthlyDepreciation, $remainingValue);

                if ($depreciationAmount <= 0.01) {
                    $this->line("  -> Aset '{$asset->asset_name}' (ID: {$asset->asset_id}) sudah mencapai nilai sisa. Dilewati.");
                    $skippedCount++;
                    continue;
                }
                
                // ========================================================
                // AKHIR LOGIKA BARU
                // ========================================================

                // 6. Validasi Akun
                if (!$asset->depreciation_expense_account_id || !$asset->accumulated_depreciation_account_id) {
                    $this->error("  -> GAGAL: Aset '{$asset->asset_name}' (ID: {$asset->asset_id}) tidak memiliki Akun COA. Dilewati.");
                    Log::error("Penyusutan Gagal: Aset ID {$asset->asset_id} tidak punya akun COA.");
                    $skippedCount++;
                    continue;
                }

                // 7. Jalankan Transaksi & Jurnal
                DB::transaction(function () use ($asset, $targetDate, $depreciationAmount) {
                    
                    // A. Post Jurnal ke General Ledger
                    $journalGroupId = "DEP-" . $asset->asset_id . "-" . $targetDate->format('Ym');
                    $description = "Penyusutan bulanan {$targetDate->format('F Y')} - {$asset->asset_name}";

                    $debitEntries = [
                        // [Akun Beban Penyusutan, Jumlah]
                        [$asset->depreciation_expense_account_id, $depreciationAmount, $description]
                    ];
                    $creditEntries = [
                        // [Akun Akumulasi Penyusutan, Jumlah]
                        [$asset->accumulated_depreciation_account_id, $depreciationAmount, $description]
                    ];
                    
                    // Panggil service tanpa User ID (karena ini command otomatis/robot)
                    $this->accountingService->postJournal(
                        $journalGroupId,
                        $targetDate,
                        $description,
                        $debitEntries,
                        $creditEntries,
                        $asset,
                        null // User ID null untuk sistem
                    );

                    // B. Simpan Riwayat Penyusutan
                    $asset->depreciations()->create([
                        'depreciation_date' => $targetDate,
                        'amount' => $depreciationAmount,
                        'journal_group_id' => $journalGroupId,
                    ]);

                    // C. Update Nilai Buku Aset
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