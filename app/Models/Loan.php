<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\ChartOfAccount;

/**
 * App\Models\Loan
 *
 * @property int $loan_id
 * @property string $lender_name
 * @property string|null $description
 * @property \Illuminate\Support\Carbon $loan_date
 * @property float $principal_amount
 * @property float $remaining_balance
 * @property string $status
 * @property int|null $user_id
 * @property int|null $loan_account_id
 * @property int|null $cash_bank_account_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read ChartOfAccount|null $cashBankAccount
 * @property-read ChartOfAccount|null $loanAccount
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LoanPayment> $payments
 * @property-read int|null $payments_count
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|Loan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Loan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Loan query()
 * @method static \Illuminate\Database\Eloquent\Builder|Loan whereCashBankAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Loan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Loan whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Loan whereLenderName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Loan whereLoanAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Loan whereLoanDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Loan whereLoanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Loan wherePrincipalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Loan whereRemainingBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Loan whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Loan whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Loan whereUserId($value)
 * @mixin \Eloquent
 */
class Loan extends Model
{
    use HasFactory;

    protected $primaryKey = 'loan_id';

    protected $fillable = [
        'lender_name',
        'description',
        'loan_date',
        'principal_amount',
        'remaining_balance',
        'status',
        'user_id',
        'loan_account_id',
        'cash_bank_account_id', 
    ];

    protected $casts = [
        'loan_date' => 'date',
        'principal_amount' => 'float',
        'remaining_balance' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LoanPayment::class, 'loan_id', 'loan_id')->latest('payment_date');
    }

    /**
     * Relasi ke Akun Utang Pinjaman (COA).
     */
    public function loanAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'loan_account_id', 'account_id');
    }

    /**
     * Relasi ke Akun Kas/Bank (COA) tempat menerima dana.
     */
    public function cashBankAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'cash_bank_account_id', 'account_id');
    }
}