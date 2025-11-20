<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\AccountingService;
use App\Services\AccountingSettingService;

// Models
use App\Models\SalesInvoice;
use App\Models\PurchaseOrder;
use App\Models\Expense;
use App\Models\Payment; // Sales Payment
use App\Models\PurchaseOrderPayment;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\FixedAsset;
use App\Models\EquityTransaction;
use App\Models\ManualJournal;
use App\Models\GeneralLedger;
use App\Models\InvoiceAdjustment;
use App\Models\PurchaseOrderAdjustment;

class RepostJournals extends Command
{
    /**
     * Signature command untuk dijalankan di terminal.
     */
    protected $signature = 'accounting:repost-all {--force : Lewati konfirmasi}';

    /**
     * Deskripsi command.
     */
    protected $description = 'Menghapus dan membuat ulang seluruh Jurnal Umum berdasarkan data transaksi operasional.';

    protected $accService;
    protected $settings;

    public function __construct(AccountingService $accService, AccountingSettingService $settings)
    {
        parent::__construct();
        $this->accService = $accService;
        $this->settings = $settings;
    }

    public function handle()
    {
        // 1. Konfirmasi Keamanan
        if (!$this->option('force')) {
            $this->warn('PERINGATAN: Command ini akan MENGHAPUS SELURUH DATA di tabel:');
            $this->warn('- general_ledgers (Buku Besar)');
            $this->warn('- bank_reconciliations (Rekonsiliasi Bank)');
            
            if (!$this->confirm('Apakah Anda yakin ingin melanjutkan?')) {
                $this->info('Proses dibatalkan.');
                return;
            }
        }

        $this->info('Memulai proses Reposting Jurnal...');
        $startTime = microtime(true);

        // =================================================================
        // [PERBAIKAN] 1. BERSIHKAN TABEL DI LUAR TRANSACTION
        // Truncate di MySQL menyebabkan auto-commit, jadi harus di luar.
        // =================================================================
        $this->info('1. Membersihkan tabel akuntansi...');
        
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        GeneralLedger::truncate();
        DB::table('bank_reconciliations')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // =================================================================
        // [PERBAIKAN] 2. BARU MULAI TRANSACTION SETELAH TRUNCATE SELESAI
        // =================================================================
        DB::beginTransaction();
        
        try {
            
            // 3. Proses Module - Manual Journal
            $this->info('2. Memproses Jurnal Manual...');
            $manualJournals = ManualJournal::with('entries')->get();
            $this->withProgressBar($manualJournals, function ($journal) {
                $this->postManualJournal($journal);
            });
            $this->newLine();

            // 4. Proses Module - Sales Invoice (Penjualan)
            $this->info('3. Memproses Sales Invoices (Penjualan & HPP)...');
            $invoices = SalesInvoice::whereNotIn('status', ['draft', 'cancelled'])->get();
            $this->withProgressBar($invoices, function ($inv) {
                $this->postSalesInvoice($inv);
            });
            $this->newLine();

            // 5. Proses Module - Sales Payments (Penerimaan Pembayaran)
            $this->info('4. Memproses Pembayaran Penjualan...');
            $payments = Payment::whereIn('status', ['completed'])->get();
            $this->withProgressBar($payments, function ($pay) {
                $this->postSalesPayment($pay);
            });
            $this->newLine();

            // 6. Proses Module - Purchase Order (Penerimaan Barang)
            $this->info('5. Memproses Purchase Orders (Penerimaan Barang)...');
            $pos = PurchaseOrder::where('status', 'completed')->get();
            $this->withProgressBar($pos, function ($po) {
                $this->postPurchaseOrderReceive($po);
            });
            $this->newLine();

            // 7. Proses Module - Purchase Payments (Pembayaran Hutang)
            $this->info('6. Memproses Pembayaran Pembelian...');
            $poPayments = PurchaseOrderPayment::where('status', 'completed')->get();
            $this->withProgressBar($poPayments, function ($pop) {
                $this->postPurchasePayment($pop);
            });
            $this->newLine();

            // 8. Proses Module - Expenses (Beban)
            $this->info('7. Memproses Pengeluaran Biaya (Expenses)...');
            $expenses = Expense::all();
            $this->withProgressBar($expenses, function ($exp) {
                $this->postExpense($exp);
            });
            $this->newLine();

            // 9. Proses Module - Fixed Assets (Aset Tetap)
            $this->info('8. Memproses Pembelian Aset Tetap...');
            $assets = FixedAsset::all();
            $this->withProgressBar($assets, function ($asset) {
                $this->postFixedAsset($asset);
            });
            $this->newLine();

            // 10. Proses Module - Equity (Modal)
            $this->info('9. Memproses Transaksi Modal...');
            $equities = EquityTransaction::all();
            $this->withProgressBar($equities, function ($eq) {
                $this->postEquity($eq);
            });
            $this->newLine();

            // 11. Proses Module - Loans (Pinjaman)
            $this->info('10. Memproses Pinjaman & Pembayaran...');
            $loans = Loan::all();
            foreach($loans as $loan) {
                $this->postLoan($loan); 
            }
            $loanPayments = LoanPayment::all();
            $this->withProgressBar($loanPayments, function ($lp) {
                $this->postLoanPayment($lp);
            });
            $this->newLine();

            // 12. Invoice Adjustments
            $this->info('11. Memproses Penyesuaian Invoice...');
            $invAdjs = InvoiceAdjustment::all();
            foreach($invAdjs as $adj) {
                $this->postInvoiceAdjustment($adj);
            }
            $this->newLine();

            // 13. PO Adjustments
            $this->info('12. Memproses Penyesuaian PO...');
            $poAdjs = PurchaseOrderAdjustment::all();
            foreach($poAdjs as $adj) {
                $this->postPoAdjustment($adj);
            }
            $this->newLine();

            DB::commit(); // <--- Transaction ditutup disini
            
            $duration = round(microtime(true) - $startTime, 2);
            $this->info("SUKSES! Jurnal Umum berhasil diperbarui dalam {$duration} detik.");

        } catch (\Exception $e) {
            // Pastikan rollback aman (cek jika ada transaksi aktif)
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            
            $this->error("GAGAL: " . $e->getMessage());
            $this->error("Line: " . $e->getLine());
            $this->error("File: " . $e->getFile());
        }
    }

    // =========================================================================
    // HELPER FUNCTIONS (Mencerminkan Logika di Controller masing-masing)
    // =========================================================================

    private function postManualJournal($journal)
    {
        $debitEntries = [];
        $creditEntries = [];

        foreach ($journal->entries as $entry) {
            if ($entry->debit > 0) {
                $debitEntries[] = [$entry->chart_of_account_id, $entry->debit, $entry->description];
            }
            if ($entry->credit > 0) {
                $creditEntries[] = [$entry->chart_of_account_id, $entry->credit, $entry->description];
            }
        }

        $this->accService->postJournal(
            $journal->journal_number,
            $journal->entry_date,
            $journal->description,
            $debitEntries,
            $creditEntries,
            $journal,
            $journal->user_id
        );
    }

    private function postSalesInvoice($invoice)
    {
        $arId = $this->settings->getAccountsReceivableId();
        $revId = $this->settings->getSalesRevenueId();
        $cogsId = $this->settings->getCogsId();
        $invId = $this->settings->getInventoryId();

        if (!$arId || !$revId || !$cogsId || !$invId) return;

        $totalHpp = $invoice->items()->sum(DB::raw('quantity * hpp'));

        $this->accService->postJournal(
            "INV-" . $invoice->invoice_number,
            $invoice->order_date,
            "Penjualan Invoice #" . $invoice->invoice_number,
            [ // Debit
                [$arId, $invoice->total_amount, "Piutang atas " . ($invoice->client->client_name ?? '')],
                [$cogsId, $totalHpp, "HPP atas Invoice #" . $invoice->invoice_number]
            ],
            [ // Kredit
                [$revId, $invoice->total_amount, "Pendapatan atas Invoice #" . $invoice->invoice_number],
                [$invId, $totalHpp, "Pengurangan Persediaan"]
            ],
            $invoice,
            $invoice->user_id_sales
        );
    }

    private function postSalesPayment($payment)
    {
        $arId = $this->settings->getAccountsReceivableId();
        $cashBankId = $payment->companyBankAccount?->chart_of_account_id;
        
        // Logika sederhana: Asumsi payment direct (tanpa deposit untuk backfill ini)
        // Jika Anda menggunakan fitur deposit kompleks, logika harus disesuaikan.
        // Ini versi aman (Cash vs AR)
        
        if (!$arId || !$cashBankId) return;

        $this->accService->postJournal(
            "PAY-" . $payment->payment_id,
            $payment->payment_date,
            "Penerimaan Pembayaran Inv #" . ($payment->salesInvoice->invoice_number ?? 'N/A'),
            [ // Debit Cash
                [$cashBankId, $payment->amount, "Penerimaan ke " . ($payment->companyBankAccount->account_name ?? 'Bank')]
            ],
            [ // Kredit AR
                [$arId, $payment->amount, "Pelunasan Piutang"]
            ],
            $payment,
            $payment->received_by_user_id
        );
    }

    private function postPurchaseOrderReceive($po)
    {
        $invId = $this->settings->getInventoryId();
        $apId = $this->settings->getAccountsPayableId();

        if (!$invId || !$apId) return;

        $this->accService->postJournal(
            "PO-" . $po->po_number,
            $po->order_date,
            "Penerimaan barang PO #" . $po->po_number,
            [ [$invId, $po->grand_total] ], // Debit Persediaan
            [ [$apId, $po->grand_total] ], // Kredit Hutang
            $po,
            $po->user_id_admin
        );
    }

    private function postPurchasePayment($payment)
    {
        $apId = $this->settings->getAccountsPayableId();
        $cashBankId = $payment->companyBankAccount?->chart_of_account_id;

        if (!$apId || !$cashBankId) return;

        $this->accService->postJournal(
            "PO-PAY-" . $payment->id,
            $payment->payment_date,
            "Pembayaran PO #" . ($payment->purchaseOrder->po_number ?? 'N/A'),
            [ [$apId, $payment->amount] ], // Debit Hutang
            [ [$cashBankId, $payment->amount] ], // Kredit Kas
            $payment,
            $payment->received_by_user_id
        );
    }

    private function postExpense($expense)
    {
        $this->accService->postJournal(
            "EXP-" . $expense->expense_id,
            $expense->expense_date,
            "Beban: " . $expense->description,
            [ [$expense->chart_of_account_id, $expense->amount] ], // Debit Beban
            [ [$expense->cash_bank_account_id, $expense->amount] ], // Kredit Kas
            $expense,
            $expense->user_id
        );
    }

    private function postFixedAsset($asset)
    {
        $this->accService->postJournal(
            "FASSET-" . $asset->asset_id,
            $asset->purchase_date,
            "Pembelian Aset Tetap: " . $asset->asset_name,
            [ [$asset->fixed_asset_account_id, $asset->purchase_cost] ], // Debit Aset
            [ [$asset->cash_bank_account_id, $asset->purchase_cost] ], // Kredit Kas
            $asset,
            $asset->user_id
        );
    }

    private function postEquity($equity)
    {
        $debit = [];
        $credit = [];

        if ($equity->type == 'investment') {
            // D: Kas, K: Modal
            $debit[] = [$equity->cash_bank_account_id, $equity->amount];
            $credit[] = [$equity->equity_account_id, $equity->amount];
        } else {
            // D: Prive, K: Kas
            $debit[] = [$equity->equity_account_id, $equity->amount];
            $credit[] = [$equity->cash_bank_account_id, $equity->amount];
        }

        $this->accService->postJournal(
            "EQ-" . $equity->transaction_id,
            $equity->transaction_date,
            "Modal: " . $equity->description,
            $debit,
            $credit,
            $equity,
            $equity->user_id
        );
    }

    private function postLoan($loan)
    {
        // Penerimaan Pinjaman: D Kas, K Utang
        $this->accService->postJournal(
            "LOAN-" . $loan->loan_id,
            $loan->loan_date,
            "Penerimaan Pinjaman dari " . $loan->lender_name,
            [ [$loan->cash_bank_account_id, $loan->principal_amount] ],
            [ [$loan->loan_account_id, $loan->principal_amount] ],
            $loan,
            $loan->user_id
        );
    }

    private function postLoanPayment($payment)
    {
        $loan = $payment->loan;
        $debit = [];
        $debit[] = [$loan->loan_account_id, $payment->principal_paid]; // D Utang
        
        if ($payment->interest_paid > 0 && $payment->interest_expense_account_id) {
            $debit[] = [$payment->interest_expense_account_id, $payment->interest_paid]; // D Bunga
        }

        $this->accService->postJournal(
            "LOANPAY-" . $payment->payment_id,
            $payment->payment_date,
            "Bayar Cicilan Pinjaman: " . $loan->lender_name,
            $debit,
            [ [$payment->cash_bank_account_id, $payment->total_paid] ], // K Kas
            $payment,
            $payment->user_id
        );
    }

    private function postInvoiceAdjustment($adj)
    {
        $arId = $this->settings->getAccountsReceivableId();
        $retId = $this->settings->getSalesReturnId();
        $revId = $this->settings->getSalesRevenueId();

        if (!$arId || !$retId) return;

        $debit = [];
        $credit = [];

        if ($adj->type === 'credit_note') {
            // D: Retur, K: Piutang
            $debit[] = [$retId, $adj->amount];
            $credit[] = [$arId, $adj->amount];
        } else {
            // D: Piutang, K: Pendapatan
            $debit[] = [$arId, $adj->amount];
            $credit[] = [$revId, $adj->amount];
        }

        $this->accService->postJournal(
            "INV-ADJ-" . $adj->adjustment_id,
            $adj->adjustment_date,
            "Penjualan Adj #" . $adj->salesInvoice->invoice_number,
            $debit,
            $credit,
            $adj,
            $adj->user_id
        );
    }

    private function postPoAdjustment($adj)
    {
        $apId = $this->settings->getAccountsPayableId();
        $retId = $this->settings->getPurchaseReturnId(); // Asumsi retur ke persediaan
        $invId = $this->settings->getInventoryId();

        if (!$apId || !$retId || !$invId) return;

        $debit = [];
        $credit = [];

        if ($adj->type === 'credit_note') {
            // D: Hutang, K: Persediaan (Retur)
            $debit[] = [$apId, $adj->amount];
            $credit[] = [$retId, $adj->amount];
        } else {
            // D: Persediaan, K: Hutang
            $debit[] = [$invId, $adj->amount];
            $credit[] = [$apId, $adj->amount];
        }

        $this->accService->postJournal(
            "PO-ADJ-" . $adj->adjustment_id,
            $adj->adjustment_date,
            "Pembelian Adj #" . $adj->purchaseOrder->po_number,
            $debit,
            $credit,
            $adj,
            $adj->user_id
        );
    }
}