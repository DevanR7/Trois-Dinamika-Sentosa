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
     * Kita tambahkan opsi --month (YYYY-MM) agar bisa menyusutkan bulan tertentu.
     *
     * @var string
     */
    protected $signature = 'accounting:run-depreciation {--month= : Bulan dan tahun (YYYY-MM) yang akan disusutkan. Default: bulan lalu.}';

    /**
     * Deskripsi console command.
     *
     * @var string
     */
    protected $description = 'Menghitung dan mem-posting jurnal penyusutan aset tetap untuk bulan yang ditentukan.';

    /**
     * Inject AccountingService
     */
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
            $assets = FixedAsset::where('useful_life_months', '>', 0) // Punya masa manfaat
                                ->where('purchase_date', '<=', $targetDate) // Dibeli sebelum periode ini berakhir
                                ->where('current_book_value', '>', DB::raw('salvage_value')) // Belum habis nilainya
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

                // 4. Hitung Beban Penyusutan Bulanan (Garis Lurus)
                $depreciableBase = $asset->purchase_cost - $asset->salvage_value;
                $monthlyDepreciation = $depreciableBase / $asset->useful_life_months;

                // 5. Cek apakah ini penyusutan terakhir (pastikan tidak minus)
                $remainingValue = $asset->current_book_value - $asset->salvage_value;
                $depreciationAmount = min($monthlyDepreciation, $remainingValue);

                if ($depreciationAmount <= 0.01) {
                    $this->line("  -> Aset '{$asset->asset_name}' (ID: {$asset->asset_id}) sudah mencapai nilai sisa. Dilewati.");
                    $skippedCount++;
                    continue;
                }
                
                // 6. Validasi Akun
                if (!$asset->depreciation_expense_account_id || !$asset->accumulated_depreciation_account_id) {
                    $this->error("  -> GAGAL: Aset '{$asset->asset_name}' (ID: {$asset->asset_id}) tidak memiliki Akun Beban atau Akun Akumulasi. Dilewati.");
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
                    
                    $this->accountingService->postJournal(
                        $journalGroupId,
                        $targetDate,
                        $description,
                        $debitEntries,
                        $creditEntries,
                        $asset // Referensi ke Aset
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