<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\ChartOfAccount;

/**
 * App\Models\LoanPayment
 *
 * @property int $payment_id
 * @property int $loan_id
 * @property \Illuminate\Support\Carbon $payment_date
 * @property float $principal_paid
 * @property float $interest_paid
 * @property float $total_paid
 * @property string|null $notes
 * @property int|null $user_id
 * @property int|null $interest_expense_account_id
 * @property int|null $cash_bank_account_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read ChartOfAccount|null $cashBankAccount
 * @property-read ChartOfAccount|null $interestExpenseAccount
 * @property-read \App\Models\Loan $loan
 * @property-read User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|LoanPayment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LoanPayment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LoanPayment query()
 * @method static \Illuminate\Database\Eloquent\Builder|LoanPayment whereCashBankAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LoanPayment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LoanPayment whereInterestExpenseAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LoanPayment whereInterestPaid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LoanPayment whereLoanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LoanPayment whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LoanPayment wherePaymentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LoanPayment wherePaymentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LoanPayment wherePrincipalPaid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LoanPayment whereTotalPaid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LoanPayment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LoanPayment whereUserId($value)
 * @mixin \Eloquent
 */
class LoanPayment extends Model
{
    use HasFactory;
    protected $primaryKey = 'payment_id';

    protected $fillable = [
        'loan_id',
        'payment_date',
        'principal_paid',
        'interest_paid',
        'total_paid',
        'notes',
        'user_id',
        'interest_expense_account_id', 
        'cash_bank_account_id', 
    ];

    protected $casts = [
        'payment_date' => 'date',
        'principal_paid' => 'float',
        'interest_paid' => 'float',
        'total_paid' => 'float',
    ];

    /**
     * Relasi ke pinjaman induk.
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class, 'loan_id', 'loan_id');
    }

    /**
     * Relasi ke user yang mencatat.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Relasi ke Akun Beban Bunga (COA).
     */
    public function interestExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'interest_expense_account_id', 'account_id');
    }

    /**
     * Relasi ke Akun Kas/Bank (COA) sebagai sumber dana.
     */
    public function cashBankAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'cash_bank_account_id', 'account_id');
    }
}