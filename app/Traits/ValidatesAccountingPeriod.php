<?php

namespace App\Traits;

use App\Models\ManualJournal;
use App\Models\GeneralLedger;
use Illuminate\Support\Carbon;

trait ValidatesAccountingPeriod
{
    /**
     * Cek apakah transaksi terkunci (Tutup Buku atau Bank Recon).
     *
     * @param string|\DateTime $date Tanggal transaksi
     * @param string|null $journalNumber Nomor referensi jurnal (untuk cek bank recon)
     * @param string|null $description Deskripsi (untuk cek jurnal sistem)
     * @return string|null Mengembalikan pesan error jika terkunci, null jika aman.
     */
    public function checkTransactionLock(string|object $date, ?string $journalNumber = null, ?string $description = null): ?string
    {
        if ($description && str_contains($description, 'Jurnal Penutup Tahun')) {
            return 'Transaksi ini adalah Jurnal Penutup otomatis sistem dan tidak boleh diedit manual.';
        }

        $year = Carbon::parse($date)->year;
        
        $isClosed = ManualJournal::where('description', 'LIKE', "Jurnal Penutup Tahun $year")
            ->exists();

        if ($isClosed) {
            return "Periode tahun buku $year sudah ditutup (Closing Book). Transaksi tidak dapat diubah/dihapus.";
        }

        if ($journalNumber) {
            $isReconciled = GeneralLedger::where('journal_group_id', $journalNumber)
                ->whereNotNull('bank_reconciliation_id')
                ->exists();

            if ($isReconciled) {
                return "Transaksi ini sudah direkonsiliasi dengan Bank (Status: Reconciled/Draft). Hapus centang di modul Rekonsiliasi Bank terlebih dahulu.";
            }
        }

        return null;
    }
    
    public function isDateClosed(string|object $date): bool
    {
        $year = Carbon::parse($date)->year;
        return ManualJournal::where('description', 'LIKE', "Jurnal Penutup Tahun $year")->exists();
    }
}