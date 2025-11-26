<?php

namespace App\Services;

use App\Models\GeneralLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Exception;

class AccountingService
{
    /**
     * Mem-posting Jurnal Umum.
     * * Format entry array diharapkan: ['account_id' => int, 'amount' => float, 'notes' => ?string]
     */
    public function postJournal(
        string $journalGroupId,
        $entryDate,
        string $description,
        array $debitEntries,
        array $creditEntries,
        Model $referenceModel
    ) {
        DB::transaction(function () use ($journalGroupId, $entryDate, $description, $debitEntries, $creditEntries, $referenceModel) {
            
            $totalDebit = 0;
            $totalCredit = 0;
            $journalsToCreate = [];
            $userId = Auth::id();
            $now = now();

            // Helper function untuk memproses entry agar tidak duplikasi kode
            $processEntry = function($entries, $isDebit) use (&$totalDebit, &$totalCredit, &$journalsToCreate, $journalGroupId, $entryDate, $description, $referenceModel, $userId, $now) {
                foreach ($entries as $entry) {
                    // Support format array lama (indexed) dan baru (named key)
                    $accountId = $entry['account_id'] ?? $entry[0];
                    $amount    = $entry['amount'] ?? $entry[1];
                    $notes     = $entry['description'] ?? ($entry[2] ?? $description);

                    if ($amount > 0) {
                        if ($isDebit) {
                            $totalDebit += $amount;
                        } else {
                            $totalCredit += $amount;
                        }

                        $journalsToCreate[] = [
                            'journal_group_id'    => $journalGroupId,
                            'chart_of_account_id' => $accountId,
                            'entry_date'          => $entryDate,
                            'debit'               => $isDebit ? $amount : 0,
                            'credit'              => $isDebit ? 0 : $amount,
                            'description'         => $notes,
                            'reference_type'      => get_class($referenceModel),
                            'reference_id'        => $referenceModel->getKey(),
                            'user_id'             => $userId,
                            'created_at'          => $now,
                            'updated_at'          => $now,
                        ];
                    }
                }
            };

            // Proses Debit & Kredit
            $processEntry($debitEntries, true);  // True = Debit
            $processEntry($creditEntries, false); // False = Credit

            // Validasi Keseimbangan dengan toleransi epsilon
            $diff = abs($totalDebit - $totalCredit);
            if ($diff > 0.001) {
                throw new Exception("Jurnal tidak seimbang. Debit: " . number_format($totalDebit, 2) . ", Kredit: " . number_format($totalCredit, 2));
            }

            // Hapus jurnal lama (Idempotency) - Pastikan journalGroupId Unik!
            GeneralLedger::where('journal_group_id', $journalGroupId)->delete();

            // Bulk Insert
            if (!empty($journalsToCreate)) {
                GeneralLedger::insert($journalsToCreate);
            }
        });
    }
}