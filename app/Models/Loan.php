<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\ChartOfAccount;

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

    public function loanAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'loan_account_id', 'account_id');
    }
    
    public function cashBankAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'cash_bank_account_id', 'account_id');
    }
}