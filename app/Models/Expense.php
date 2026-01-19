<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ChartOfAccount;
use App\Traits\LogsActivity;

class Expense extends Model
{
    use HasFactory, LogsActivity;
    protected $primaryKey = 'expense_id';

    protected $fillable = [
        'expense_date',
        'category',
        'description',
        'amount',
        'user_id',
        'chart_of_account_id',
        'cash_bank_account_id',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id', 'account_id');
    }

    public function cashBankAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'cash_bank_account_id', 'account_id');
    }
}