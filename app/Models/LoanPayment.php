<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\ChartOfAccount;
use App\Traits\LogsActivity;

class LoanPayment extends Model
{
    use HasFactory, LogsActivity;
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

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class, 'loan_id', 'loan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function interestExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'interest_expense_account_id', 'account_id');
    }

    public function cashBankAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'cash_bank_account_id', 'account_id');
    }
}