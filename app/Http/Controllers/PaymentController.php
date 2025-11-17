<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\SalesInvoice;
use App\Models\ClientLedger;
use App\Models\Client;
use App\Models\PaymentMethod;
use App\Models\GeneralLedger;
use App\Models\CompanyBankAccount;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use App\Services\AccountingService;
use App\Services\AccountingSettingService;

class PaymentController extends Controller
{   
    /**
     * ✅ Inject Service Akuntansi
     */
    protected $accountingService;
    protected $accountingSettings;

    public function __construct(
        AccountingService $accountingService, 
        AccountingSettingService $accountingSettingService
    ) {
        $this->accountingService = $accountingService;
        $this->accountingSettings = $accountingSettingService;
    }

    /**
     * Simpan pembayaran baru ke database.
     * ✅ DIPERBARUI: Menambahkan Jurnal Akuntansi
     */
    public function store(Request $request, SalesInvoice $invoice): RedirectResponse
    {
        // 1. Validasi dasar
        $rules = [
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method_id' => [
                Rule::requiredIf(fn() => $request->input('amount', 0) > 0 || !$request->has('use_credit')),
                'nullable',
                'exists:payment_methods,payment_method_id',
            ],
            'company_bank_account_id' => [
                Rule::requiredIf(fn() => $request->input('amount', 0) > 0 || !$request->has('use_credit')),
                'nullable',
                'exists:company_bank_accounts,company_bank_account_id',
            ],
            'notes' => 'nullable|string',
            'use_credit' => 'nullable|boolean',
        ];

        // 2. Validasi dinamis berdasarkan konfigurasi metode pembayaran
        $paymentMethod = $request->filled('payment_method_id')
            ? PaymentMethod::find($request->input('payment_method_id'))
            : null;

        if ($paymentMethod) {
            $config = $paymentMethod->required_fields_config;

            $rules['proof_of_payment'] =
                in_array($config, ['proof_only', 'proof_and_reference'])
                ? 'required|image|mimes:jpeg,png,jpg|max:2048'
                : 'nullable|image|mimes:jpeg,png,jpg|max:2048';

            $rules['reference_number'] =
                in_array($config, ['reference_only', 'proof_and_reference'])
                ? 'required|string|max:255'
                : 'nullable|string|max:255';
        } else {
            // Fallback untuk tanpa metode pembayaran (mis. pembayaran via kredit)
            $rules['proof_of_payment'] = 'nullable|image|mimes:jpeg,png,jpg|max:2048';
            $rules['reference_number'] = 'nullable|string|max:255';
        }

        // 3. Jalankan validasi
        $validated = $request->validate($rules);

        $client = $invoice->client;
        $danaDariInput = (float) ($validated['amount'] ?? 0);
        $pakaiKredit = $validated['use_credit'] ?? false;
        $kreditAwalKlien = $client->balance;
        $sisaTagihan = $invoice->remaining_balance;

        $catatanLog = $validated['notes'] ?? '';
        $kreditAkanDigunakan = 0;
        $danaInputAkanDigunakan = 0;
        $sisaDanaInput = 0;

        DB::beginTransaction();

        try {
            // 4. Hitung alokasi dana
            if ($pakaiKredit && $kreditAwalKlien > 0) {
                $kreditAkanDigunakan = min($kreditAwalKlien, $sisaTagihan);
            }

            $sisaTagihanSetelahKredit = max(0, $sisaTagihan - $kreditAkanDigunakan);
            $danaInputAkanDigunakan = min($danaDariInput, $sisaTagihanSetelahKredit);
            $totalPembayaran = $kreditAkanDigunakan + $danaInputAkanDigunakan;
            $sisaDanaInput = max(0, $danaDariInput - $danaInputAkanDigunakan);

            if ($totalPembayaran <= 0.01 && $sisaDanaInput <= 0.01 && $sisaTagihan > 0.01) {
                throw new \Exception("Tidak ada dana (input/kredit) yang dialokasikan.");
            }

            // 5. Siapkan metadata pembayaran
            $paymentMethodName = $paymentMethod->name ?? 'N/A';
            $paymentMethodType = $paymentMethod->type ?? 'direct';

            $metodeLog = $paymentMethodName;
            if ($kreditAkanDigunakan > 0) {
                $metodeLog = $danaInputAkanDigunakan > 0
                    ? 'Kredit Klien + ' . $paymentMethodName
                    : 'Kredit Klien';
            }

            if (!empty($catatanLog)) {
                $catatanLog .= ' | ';
            }

            $newPaymentStatus = $paymentMethodType === 'pending'
                ? 'pending_clearance'
                : 'completed';

            // 6. Upload bukti pembayaran
            $proofPath = $request->hasFile('proof_of_payment')
                ? $request->file('proof_of_payment')->store('payment_proofs', 'public')
                : null;

            // ✅ Validasi Akun Akuntansi
            $arAccountId = $this->accountingSettings->getAccountsReceivableId();
            $clientDepositAccountId = $this->accountingSettings->getClientDepositId();
            if (!$arAccountId || !$clientDepositAccountId) {
                throw new \Exception("Akun Piutang Usaha (AR) atau Akun Deposit Klien belum diatur di Pengaturan Akuntansi.");
            }
            
            $cashBankAccount = null;
            $cashBankAccountId = null;
            if ($danaDariInput > 0) {
                $cashBankAccount = CompanyBankAccount::find($validated['company_bank_account_id']);
                if (!$cashBankAccount || !$cashBankAccount->chart_of_account_id) {
                    throw new \Exception("Akun Bank Perusahaan yang dipilih belum terhubung ke Chart of Account.");
                }
                $cashBankAccountId = $cashBankAccount->chart_of_account_id;
            }
            
            // 7. Simpan pembayaran
            $payment = $invoice->payments()->create([
                'amount' => $totalPembayaran,
                'payment_date' => $validated['payment_date'],
                'payment_method_id' => $validated['payment_method_id'] ?? null,
                'company_bank_account_id' => $validated['company_bank_account_id'] ?? null,
                'status' => $newPaymentStatus,
                'notes' => $catatanLog,
                'received_by_user_id' => Auth::id(),
                'proof_of_payment_path' => $proofPath,
                'reference_number' => $validated['reference_number'] ?? null,
            ]);

            // 8. Catat transaksi ke ClientLedger
            if ($kreditAkanDigunakan > 0) {
                ClientLedger::create([
                    'client_id' => $client->client_id,
                    'reference_type' => Payment::class,
                    'reference_id' => $payment->payment_id,
                    'transaction_date' => $validated['payment_date'],
                    'type' => 'debit',
                    'amount' => -$kreditAkanDigunakan,
                    'status' => 'available',
                    'description' => 'Digunakan untuk membayar Invoice #' . $invoice->invoice_number,
                    'user_id' => Auth::id(),
                ]);

                $catatanLog .= 'Credit used: ' . number_format($kreditAkanDigunakan);
            }

            if ($sisaDanaInput > 0.01) {
                ClientLedger::create([
                    'client_id' => $client->client_id,
                    'reference_type' => Payment::class,
                    'reference_id' => $payment->payment_id,
                    'transaction_date' => $validated['payment_date'],
                    'type' => 'credit',
                    'amount' => $sisaDanaInput,
                    'status' => 'available',
                    'description' => 'Kelebihan bayar dari Invoice #' . $invoice->invoice_number,
                    'user_id' => Auth::id(),
                ]);

                $catatanLog .= '. Overpayment: ' . number_format($sisaDanaInput) . ' returned to credit.';
            }

            // 9. Update catatan dan status invoice
            $payment->update(['notes' => $catatanLog]);

            // ✅ Post Jurnal Akuntansi (JIKA COMPLETED)
            if ($newPaymentStatus == 'completed') {
                $journalGroupId = "PAY-" . $payment->payment_id;
                $description = "Penerimaan Pembayaran Inv #" . $invoice->invoice_number;

                // Jurnal seimbang:
                // (D) Kas/Bank         (Total Kas Masuk)   : $danaDariInput
                // (D) Deposit Klien    (Deposit Terpakai)  : $kreditAkanDigunakan
                // (K) Piutang Usaha    (Piutang Lunas)     : $totalPembayaran
                // (K) Deposit Klien    (Kelebihan Bayar)   : $sisaDanaInput
                
                $debitEntries = [];
                $creditEntries = [];

                if ($danaDariInput > 0 && $cashBankAccountId) {
                    $debitEntries[] = [$cashBankAccountId, $danaDariInput, "Penerimaan ke " . $cashBankAccount->account_name];
                }
                if ($kreditAkanDigunakan > 0) {
                    $debitEntries[] = [$clientDepositAccountId, $kreditAkanDigunakan, "Penggunaan deposit klien"];
                }
                
                if ($totalPembayaran > 0) {
                    $creditEntries[] = [$arAccountId, $totalPembayaran, "Pelunasan Piutang Inv #" . $invoice->invoice_number];
                }
                if ($sisaDanaInput > 0) {
                    $creditEntries[] = [$clientDepositAccountId, $sisaDanaInput, "Kelebihan bayar Inv #" . $invoice->invoice_number];
                }

                $this->accountingService->postJournal(
                    $journalGroupId,
                    $validated['payment_date'],
                    $description,
                    $debitEntries,
                    $creditEntries,
                    $payment,
                    Auth::id()
                );
            }

            $invoice->updatePaymentStatus();

            DB::commit();
            return redirect()
                ->route('invoices.show', $invoice->invoice_id)
                ->with('success', 'Pembayaran berhasil dicatat. Total: Rp ' . number_format($totalPembayaran));
        
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal mencatat pembayaran: ' . $e->getMessage() . " on line " . $e->getLine());
            return back()->with('error', 'Gagal mencatat pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * Menyetujui pembayaran yang sedang diverifikasi.
     * ✅ DIPERBARUI: Menambahkan Jurnal Akuntansi untuk persetujuan
     */
    public function approve(Payment $payment): RedirectResponse
    {
        if ($payment->status !== 'pending_verification') {
            return back()->with('error', 'Pembayaran ini tidak sedang dalam status verifikasi.');
        }

        DB::beginTransaction();
        try {
            $payment->update([
                'status' => 'completed',
                'received_by_user_id' => Auth::id(),
            ]);

            $invoice = $payment->salesInvoice;
            $invoice->updatePaymentStatus();

            // ✅ Post Jurnal Akuntansi untuk persetujuan
            $arAccountId = $this->accountingSettings->getAccountsReceivableId();
            $clientDepositAccountId = $this->accountingSettings->getClientDepositId();
            
            if ($arAccountId && $clientDepositAccountId) {
                $journalGroupId = "PAY-APP-" . $payment->payment_id;
                $description = "Persetujuan Pembayaran Inv #" . $invoice->invoice_number;

                // Jurnal untuk persetujuan (sama dengan store)
                $debitEntries = [];
                $creditEntries = [];

                // Ambil data dari payment
                $cashBankAccount = $payment->companyBankAccount;
                if ($cashBankAccount && $cashBankAccount->chart_of_account_id) {
                    $debitEntries[] = [$cashBankAccount->chart_of_account_id, $payment->amount, "Penerimaan (Approved) ke " . $cashBankAccount->account_name];
                }
                
                $creditEntries[] = [$arAccountId, $payment->amount, "Pelunasan Piutang (Approved) Inv #" . $invoice->invoice_number];

                $this->accountingService->postJournal(
                    $journalGroupId,
                    now(),
                    $description,
                    $debitEntries,
                    $creditEntries,
                    $payment,
                    Auth::id()
                );
            }

            DB::commit();
            return back()->with('success', 'Pembayaran berhasil disetujui dan jurnal telah diposting.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menyetujui pembayaran: ' . $e->getMessage());
            return back()->with('error', 'Gagal menyetujui pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * Menolak pembayaran yang masih diverifikasi.
     * ✅ DIPERBARUI: Menambahkan Jurnal untuk penolakan
     */
    public function reject(Payment $payment): RedirectResponse
    {
        if ($payment->status !== 'pending_verification') {
            return back()->with('error', 'Pembayaran ini tidak sedang dalam status verifikasi.');
        }

        DB::beginTransaction();
        try {
            $payment->update([
                'status' => 'failed',
                'received_by_user_id' => Auth::id(),
            ]);

            // ✅ Jurnal untuk penolakan (jika diperlukan)
            // Biasanya untuk penolakan, kita tidak perlu jurnal karena belum ada pencatatan
            // Tapi jika ada kebutuhan khusus, bisa ditambahkan di sini

            DB::commit();
            return back()->with('success', 'Bukti pembayaran telah ditolak.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menolak pembayaran: ' . $e->getMessage());
            return back()->with('error', 'Gagal menolak pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * ✅ Menghapus/Membatalkan Pembayaran
     * DIPERBARUI: Menambahkan Jurnal Reversal yang lengkap
     */
    public function destroy(Payment $payment): RedirectResponse
    {
        // $this->authorize('delete', $payment); // Tambahkan policy jika perlu

        DB::beginTransaction();
        try {
            $invoice = $payment->salesInvoice;
            
            // 1. Rollback ClientLedger (Hapus entri yg dibuat oleh payment ini)
            ClientLedger::where('reference_type', Payment::class)
                        ->where('reference_id', $payment->payment_id)
                        ->delete();

            // 2. Post Jurnal Reversal (JIKA payment berstatus 'completed')
            if ($payment->status == 'completed') {
                $journalGroupId = "PAY-REV-" . $payment->payment_id;
                $description = "Reversal Pembayaran Inv #" . $invoice->invoice_number;

                // Ambil jurnal asli
                $originalJournalEntries = GeneralLedger::where('journal_group_id', "PAY-" . $payment->payment_id)
                                        ->get();
                
                $debitEntries = [];
                $creditEntries = [];

                foreach ($originalJournalEntries as $entry) {
                    /** @var \App\Models\GeneralLedger $entry */
                    // Balikkan Debit jadi Kredit
                    if ($entry->debit > 0) {
                        $creditEntries[] = [$entry->chart_of_account_id, $entry->debit, "Reversal: " . $entry->description];
                    }
                    // Balikkan Kredit jadi Debit
                    if ($entry->credit > 0) {
                        $debitEntries[] = [$entry->chart_of_account_id, $entry->credit, "Reversal: " . $entry->description];
                    }
                }
                
                if (!empty($debitEntries) || !empty($creditEntries)) {
                    $this->accountingService->postJournal(
                        $journalGroupId,
                        now(),
                        $description,
                        $debitEntries,
                        $creditEntries,
                        $payment
                    );
                }

                // 3. Hapus Jurnal Asli
                DB::table('general_ledgers')->where('journal_group_id', "PAY-" . $payment->payment_id)->delete();
            }

            // 4. Hapus data pembayaran
            $payment->delete();

            // 5. Update status invoice
            if ($invoice) {
                $invoice->updatePaymentStatus();
            }

            DB::commit();
            return back()->with('success', 'Pembayaran berhasil dibatalkan (Rollback).');
        
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menghapus pembayaran: ' . $e->getMessage() . " on line " . $e->getLine());
            return back()->with('error', 'Gagal membatalkan pembayaran: ' . $e->getMessage());
        }
    }
}