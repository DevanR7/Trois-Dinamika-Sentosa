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

            $oldReconMap = GeneralLedger::where('journal_group_id', $journalGroupId)
                ->whereNotNull('bank_reconciliation_id')
                ->pluck('bank_reconciliation_id', 'chart_of_account_id')
                ->toArray();

            GeneralLedger::where('journal_group_id', $journalGroupId)->delete();

            $totalDebit = 0;
            $totalCredit = 0;
            $journalsToCreate = [];

            foreach ($debitEntries as $entry) {
                $amount = (float) $entry[1];
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

            foreach ($creditEntries as $entry) {
                $amount = (float) $entry[1];
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

            foreach ($journalsToCreate as &$journal) {
                $accId = $journal['chart_of_account_id'];
                if (isset($oldReconMap[$accId])) {
                    $journal['bank_reconciliation_id'] = $oldReconMap[$accId];
                }
            }
            unset($journal); 

            if (abs(round($totalDebit, 2) - round($totalCredit, 2)) > 0.01) {
                throw new Exception("Jurnal tidak seimbang (Unbalanced Journal) untuk $journalGroupId. Debit: $totalDebit, Kredit: $totalCredit");
            }
            if (!empty($journalsToCreate)) {
                GeneralLedger::insert($journalsToCreate);
            }
        });
    }
}