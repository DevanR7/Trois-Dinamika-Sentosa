<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * App\Models\GeneralLedger
 *
 * @property int $ledger_id
 * @property string $journal_group_id
 * @property int $chart_of_account_id
 * @property \Illuminate\Support\Carbon $entry_date
 * @property float $debit
 * @property float $credit
 * @property string $description
 * @property string $reference_type
 * @property int $reference_id
 * @property int|null $user_id
 * @property int|null $bank_reconciliation_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ChartOfAccount $account
 * @property-read \App\Models\BankReconciliation|null $bankReconciliation
 * @property-read Model|\Eloquent $reference
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|GeneralLedger newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GeneralLedger newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GeneralLedger query()
 * @method static \Illuminate\Database\Eloquent\Builder|GeneralLedger whereBankReconciliationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GeneralLedger whereChartOfAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GeneralLedger whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GeneralLedger whereCredit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GeneralLedger whereDebit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GeneralLedger whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GeneralLedger whereEntryDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GeneralLedger whereJournalGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GeneralLedger whereLedgerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GeneralLedger whereReferenceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GeneralLedger whereReferenceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GeneralLedger whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GeneralLedger whereUserId($value)
 * @mixin \Eloquent
 */
class GeneralLedger extends Model
{
    use HasFactory;

    protected $primaryKey = 'ledger_id';

    protected $fillable = [
        'journal_group_id',
        'chart_of_account_id',
        'entry_date',
        'debit',
        'credit',
        'description',
        'reference_type',
        'reference_id',
        'user_id',
        'bank_reconciliation_id',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'debit' => 'float',
        'credit' => 'float',
    ];

    /**
     * Relasi ke akun (COA) yang digunakan.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id', 'account_id');
    }

    /**
     * Relasi ke model sumber (SalesInvoice, PurchaseOrder, etc.).
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Relasi ke user yang mem-posting.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function bankReconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class, 'bank_reconciliation_id', 'reconciliation_id');
    }
}