<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\AccountingService;
use App\Services\AccountingSettingService;
use App\Models\SalesInvoice;
use App\Models\PurchaseOrder;
use App\Models\Expense;
use App\Models\Payment;
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
    protected $signature = 'accounting:repost-all {--force : Lewati konfirmasi}';
    protected $description = 'HARD RESET: Menghapus dan membuat ulang seluruh Jurnal Umum. PERHATIAN: Riwayat Rekonsiliasi Bank akan HILANG.';
    
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
        if (!$this->option('force')) {
            $this->error('PERINGATAN KERAS!!');
            $this->warn('Command ini akan MENGHAPUS SELURUH DATA JURNAL & REKONSILIASI BANK.');
            $this->warn('Data akan digenerate ulang dari transaksi, TAPI status "Reconciled" akan hilang (kembali ke pending).');
            $this->warn('Gunakan ini hanya jika data akuntansi rusak total atau saat setup awal.');
            
            if (!$this->confirm('Ketik "yes" jika Anda benar-benar yakin ingin mereset total pembukuan:')) {
                $this->info('Proses dibatalkan.');
                return;
            }
        }

        $this->info('Memulai Hard Reset Jurnal...');
        $startTime = microtime(true);
        
        // 1. Bersihkan Tabel
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        GeneralLedger::truncate();
        DB::table('bank_reconciliations')->truncate(); // Reset Bank Recon juga karena ID GL berubah total
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        // Mulai Transaksi Besar
        DB::beginTransaction();
       
        try {
            // Helper untuk memproses chunk
            $processChunked = function($query, $callback, $label) {
                $count = $query->count();
                if ($count === 0) {
                    $this->info("$label: Tidak ada data.");
                    return;
                }
                $this->info("$label ($count records)...");
                $bar = $this->output->createProgressBar($count);
                
                $query->chunk(100, function($items) use ($bar, $callback) {
                    foreach($items as $item) {
                        try {
                            $callback($item);
                        } catch (\Exception $e) {
                            // Log error tapi lanjut ke item berikutnya (agar 1 data rusak tidak stop semua)
                            \Illuminate\Support\Facades\Log::error("Gagal repost item ID " . $item->getKey() . ": " . $e->getMessage());
                        }
                        $bar->advance();
                    }
                });
                
                $bar->finish();
                $this->newLine();
            };

            // --- EKSEKUSI RE-POSTING PER MODUL ---

            $processChunked(ManualJournal::with('entries'), function($journal) {
                $this->postManualJournal($journal);
            }, '2. Memproses Jurnal Manual');

            $processChunked(SalesInvoice::whereNotIn('status', ['draft', 'cancelled']), function($inv) {
                $this->postSalesInvoice($inv);
            }, '3. Memproses Sales Invoices');

            $processChunked(Payment::whereIn('status', ['completed']), function($pay) {
                $this->postSalesPayment($pay);
            }, '4. Memproses Pembayaran Penjualan');

            $processChunked(PurchaseOrder::where('status', 'completed'), function($po) {
                $this->postPurchaseOrderReceive($po);
            }, '5. Memproses Penerimaan Barang PO');

            $processChunked(PurchaseOrderPayment::where('status', 'completed'), function($pop) {
                $this->postPurchasePayment($pop);
            }, '6. Memproses Pembayaran Pembelian');

            $processChunked(Expense::query(), function($exp) {
                $this->postExpense($exp);
            }, '7. Memproses Pengeluaran Biaya');

            $processChunked(FixedAsset::query(), function($asset) {
                $this->postFixedAsset($asset);
            }, '8. Memproses Pembelian Aset Tetap');

            $processChunked(EquityTransaction::query(), function($eq) {
                $this->postEquity($eq);
            }, '9. Memproses Transaksi Modal');

            $processChunked(Loan::query(), function($loan) {
                $this->postLoan($loan);
            }, '10. Memproses Pinjaman Awal');
            
            $processChunked(LoanPayment::query(), function($lp) {
                $this->postLoanPayment($lp);
            }, '11. Memproses Pembayaran Pinjaman');

            $processChunked(InvoiceAdjustment::with('salesInvoice.client'), function($adj) {
                $this->postInvoiceAdjustment($adj);
            }, '12. Memproses Penyesuaian Invoice');

            $processChunked(PurchaseOrderAdjustment::with('purchaseOrder.supplier'), function($adj) {
                $this->postPoAdjustment($adj);
            }, '13. Memproses Penyesuaian PO');

            DB::commit(); 
            
            $duration = round(microtime(true) - $startTime, 2);
            $this->info("SUKSES! Jurnal Umum berhasil diperbarui dalam {$duration} detik.");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("GAGAL TOTAL: " . $e->getMessage());
            $this->error("Line: " . $e->getLine());
            $this->error("File: " . $e->getFile());
        }
    }

    // --- PRIVATE METHODS (Sama dengan controller masing-masing) ---

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
        $totalAdditionalCosts = $invoice->additionalCosts()->sum('amount');
        
        // PPN (Revisi: Ambil dari pivot table)
        $totalTax = $invoice->taxes()->sum('invoice_tax.amount');
        
        // Revenue Murni = Total - Biaya Tambahan - Pajak
        $revenueProducts = $invoice->total_amount - $totalAdditionalCosts - $totalTax;

        $debitEntries = [
            [$arId, $invoice->total_amount, "Piutang atas " . ($invoice->client->client_name ?? '')],
            [$cogsId, $totalHpp, "HPP atas Invoice #" . $invoice->invoice_number]
        ];

        $creditEntries = [
            [$revId, $revenueProducts, "Pendapatan atas Invoice #" . $invoice->invoice_number],
            [$invId, $totalHpp, "Pengurangan Persediaan"]
        ];

        if ($totalAdditionalCosts > 0) {
             $creditEntries[] = [$revId, $totalAdditionalCosts, "Pendapatan Biaya Tambahan #" . $invoice->invoice_number];
        }
        
        if ($totalTax > 0) {
             // Pastikan akun hutang pajak diset (misal disatukan ke revenue atau akun khusus, disini kita asumsi revenue dulu jika belum ada setting khusus tax payable)
             $creditEntries[] = [$revId, $totalTax, "Hutang Pajak (PPN)"]; 
        }

        $this->accService->postJournal(
            "INV-" . $invoice->invoice_number,
            $invoice->order_date,
            "Penjualan Invoice #" . $invoice->invoice_number,
            $debitEntries,
            $creditEntries,
            $invoice,
            $invoice->user_id_sales
        );
    }

    private function postSalesPayment($payment)
    {
        $arId = $this->settings->getAccountsReceivableId();
        $cashBankId = $payment->companyBankAccount?->chart_of_account_id;
        
        // Jika payment via gateway, akun bank mungkin null di relasi tapi logicnya masuk gateway account
        if (!$cashBankId && $payment->payment_method_id) {
             $cashBankId = $this->settings->getGatewayAccountId();
        }

        if (!$arId || !$cashBankId) return;

        $this->accService->postJournal(
            "PAY-" . $payment->payment_id,
            $payment->payment_date,
            "Penerimaan Pembayaran Inv #" . ($payment->salesInvoice->invoice_number ?? 'N/A'),
            [
                [$cashBankId, $payment->amount, "Penerimaan Pembayaran"]
            ],
            [ 
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
            [ [$invId, $po->grand_total] ], 
            [ [$apId, $po->grand_total] ], 
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
            [ [$apId, $payment->amount] ], 
            [ [$cashBankId, $payment->amount] ], 
            $payment,
            $payment->received_by_user_id
        );
    }

    private function postExpense($expense)
    {
        if (!$expense->chart_of_account_id || !$expense->cash_bank_account_id) return;
        
        $this->accService->postJournal(
            "EXP-" . $expense->expense_id,
            $expense->expense_date,
            "Beban: " . $expense->description,
            [ [$expense->chart_of_account_id, $expense->amount] ], 
            [ [$expense->cash_bank_account_id, $expense->amount] ], 
            $expense,
            $expense->user_id
        );
    }

    private function postFixedAsset($asset)
    {
        if (!$asset->fixed_asset_account_id || !$asset->cash_bank_account_id) return;

        $this->accService->postJournal(
            "FASSET-" . $asset->asset_id,
            $asset->purchase_date,
            "Pembelian Aset Tetap: " . $asset->asset_name,
            [ [$asset->fixed_asset_account_id, $asset->purchase_cost] ],
            [ [$asset->cash_bank_account_id, $asset->purchase_cost] ],
            $asset,
            $asset->user_id
        );
    }

    private function postEquity($equity)
    {
        if (!$equity->equity_account_id || !$equity->cash_bank_account_id) return;

        $debit = [];
        $credit = [];

        if ($equity->type == 'investment') {
            $debit[] = [$equity->cash_bank_account_id, $equity->amount];
            $credit[] = [$equity->equity_account_id, $equity->amount];
        } else {
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
        if (!$loan->loan_account_id || !$loan->cash_bank_account_id) return;

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
        if (!$loan || !$payment->cash_bank_account_id) return;

        $debit = [];
        $debit[] = [$loan->loan_account_id, $payment->principal_paid]; 
        
        if ($payment->interest_paid > 0 && $payment->interest_expense_account_id) {
            $debit[] = [$payment->interest_expense_account_id, $payment->interest_paid]; 
        }

        $this->accService->postJournal(
            "LOANPAY-" . $payment->payment_id,
            $payment->payment_date,
            "Bayar Cicilan Pinjaman: " . $loan->lender_name,
            $debit,
            [ [$payment->cash_bank_account_id, $payment->total_paid] ], 
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
            $debit[] = [$retId, $adj->amount];
            $credit[] = [$arId, $adj->amount];
        } else {
            $debit[] = [$arId, $adj->amount];
            $credit[] = [$revId, $adj->amount];
        }

        $this->accService->postJournal(
            "INV-ADJ-" . $adj->adjustment_id,
            $adj->adjustment_date,
            "Penjualan Adj #" . ($adj->salesInvoice->invoice_number ?? 'N/A'),
            $debit,
            $credit,
            $adj,
            $adj->user_id
        );
    }

    private function postPoAdjustment($adj)
    {
        $apId = $this->settings->getAccountsPayableId();
        $retId = $this->settings->getPurchaseReturnId();
        $invId = $this->settings->getInventoryId();

        if (!$apId || !$retId || !$invId) return;

        $debit = [];
        $credit = [];

        if ($adj->type === 'credit_note') {
            $debit[] = [$apId, $adj->amount];
            $credit[] = [$retId, $adj->amount];
        } else {
            $debit[] = [$invId, $adj->amount];
            $credit[] = [$apId, $adj->amount];
        }

        $this->accService->postJournal(
            "PO-ADJ-" . $adj->adjustment_id,
            $adj->adjustment_date,
            "Pembelian Adj #" . ($adj->purchaseOrder->po_number ?? 'N/A'),
            $debit,
            $credit,
            $adj,
            $adj->user_id
        );
    }
}