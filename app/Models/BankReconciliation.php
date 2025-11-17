<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\BankReconciliation
 *
 * @property int $reconciliation_id
 * @property int $chart_of_account_id
 * @property int|null $company_bank_account_id
 * @property \Illuminate\Support\Carbon $statement_date
 * @property float $statement_balance
 * @property float $closing_balance
 * @property float $difference
 * @property string $status
 * @property int|null $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ChartOfAccount $account
 * @property-read \App\Models\CompanyBankAccount|null $companyBankAccount
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GeneralLedger> $journalEntries
 * @property-read int|null $journal_entries_count
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|BankReconciliation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BankReconciliation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BankReconciliation query()
 * @method static \Illuminate\Database\Eloquent\Builder|BankReconciliation whereChartOfAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankReconciliation whereClosingBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankReconciliation whereCompanyBankAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankReconciliation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankReconciliation whereDifference($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankReconciliation whereReconciliationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankReconciliation whereStatementBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankReconciliation whereStatementDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankReconciliation whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankReconciliation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankReconciliation whereUserId($value)
 * @mixin \Eloquent
 */
class BankReconciliation extends Model
{
    use HasFactory;

    protected $primaryKey = 'reconciliation_id';

    protected $fillable = [
        'chart_of_account_id',
        'company_bank_account_id',
        'statement_date',
        'statement_balance',
        'closing_balance',
        'difference',
        'status',
        'user_id',
    ];

    protected $casts = [
        'statement_date' => 'date',
        'statement_balance' => 'float',
        'closing_balance' => 'float',
        'difference' => 'float',
    ];

    /**
     * Relasi ke Akun COA yang direkonsiliasi
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id', 'account_id');
    }

    /**
     * Relasi ke Akun Bank Perusahaan
     */
    public function companyBankAccount(): BelongsTo
    {
        return $this->belongsTo(CompanyBankAccount::class, 'company_bank_account_id', 'company_bank_account_id');
    }

    /**
     * Relasi ke user yang memproses
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Relasi ke semua entri Jurnal Umum yang "dicentang" dalam rekonsiliasi ini
     */
    public function journalEntries(): HasMany
    {
        return $this->hasMany(GeneralLedger::class, 'bank_reconciliation_id', 'reconciliation_id');
    }
}