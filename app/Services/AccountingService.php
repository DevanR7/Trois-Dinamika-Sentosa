<?php

namespace App\Services;

use App\Models\GeneralLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Exception;

class AccountingService
{
    /**
     * Mem-posting Jurnal Umum yang seimbang (balanced).
     *
     * @param string $journalGroupId ID unik untuk grup jurnal ini
     * @param \Illuminate\Support\Carbon|string $entryDate Tanggal transaksi
     * @param string $description Deskripsi umum untuk grup jurnal
     * @param array $debitEntries Array [[account_id, amount, description_override (opsional)], ...]
     * @param array $creditEntries Array [[account_id, amount, description_override (opsional)], ...]
     * @param Model $referenceModel Model Eloquent yang menjadi sumber (e.g., $salesInvoice)
     * @param int|null $userId ID user yang melakukan aksi (opsional)
     * @throws \Exception Jika jurnal tidak seimbang (unbalanced)
     */
    public function postJournal(
        string $journalGroupId,
        $entryDate,
        string $description,
        array $debitEntries,
        array $creditEntries,
        Model $referenceModel,
        ?int $userId = null
    ) {
        DB::transaction(function () use ($journalGroupId, $entryDate, $description, $debitEntries, $creditEntries, $referenceModel, $userId) {

            // 1. Simpan status rekonsiliasi lama (jika ini update transaksi)
            // Agar jika transaksi diedit, status 'reconciled' pada baris jurnal yang akunnya sama tidak hilang (opsional, best effort)
            $oldReconMap = GeneralLedger::where('journal_group_id', $journalGroupId)
                ->whereNotNull('bank_reconciliation_id')
                ->pluck('bank_reconciliation_id', 'chart_of_account_id')
                ->toArray();

            // 2. Hapus jurnal lama dengan Group ID yang sama (Clean slate)
            GeneralLedger::where('journal_group_id', $journalGroupId)->delete();

            $totalDebit = 0;
            $totalCredit = 0;
            $journalsToCreate = [];

            // 3. Proses Debit Entries
            foreach ($debitEntries as $entry) {
                $amount = round((float) $entry[1], 2); // Pastikan 2 desimal
                
                if ($amount > 0) {
                    $totalDebit += $amount;
                    $journalsToCreate[] = [
                        'journal_group_id' => $journalGroupId,
                        'chart_of_account_id' => $entry[0],
                        'entry_date' => $entryDate,
                        'debit' => $amount,
                        'credit' => 0,
                        'description' => $entry[2] ?? $description, 
                        'reference_type' => get_class($referenceModel),
                        'reference_id' => $referenceModel->getKey(),
                        'user_id' => $userId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // 4. Proses Credit Entries
            foreach ($creditEntries as $entry) {
                $amount = round((float) $entry[1], 2); // Pastikan 2 desimal

                if ($amount > 0) {
                    $totalCredit += $amount;
                    $journalsToCreate[] = [
                        'journal_group_id' => $journalGroupId,
                        'chart_of_account_id' => $entry[0],
                        'entry_date' => $entryDate,
                        'debit' => 0,
                        'credit' => $amount,
                        'description' => $entry[2] ?? $description, 
                        'reference_type' => get_class($referenceModel),
                        'reference_id' => $referenceModel->getKey(),
                        'user_id' => $userId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // 5. Restore Bank Reconciliation ID jika ada
            foreach ($journalsToCreate as &$journal) {
                $accId = $journal['chart_of_account_id'];
                if (isset($oldReconMap[$accId])) {
                    $journal['bank_reconciliation_id'] = $oldReconMap[$accId];
                }
            }
            unset($journal); 

            // 6. Validasi Balance dengan Toleransi Floating Point
            // Menggunakan abs() > 0.05 untuk mentolerir selisih pembulatan mikro
            if (abs($totalDebit - $totalCredit) > 0.05) {
                throw new Exception("Jurnal tidak seimbang (Unbalanced) untuk $journalGroupId. Debit: $totalDebit, Kredit: $totalCredit. Selisih: " . ($totalDebit - $totalCredit));
            }

            // 7. Bulk Insert untuk performa
            if (!empty($journalsToCreate)) {
                GeneralLedger::insert($journalsToCreate);
            }
        });
    }
}