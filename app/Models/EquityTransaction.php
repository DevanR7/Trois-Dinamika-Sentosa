<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ChartOfAccount;
use App\Traits\LogsActivity;

class EquityTransaction extends Model
{
    use HasFactory, LogsActivity;
    protected $primaryKey = 'transaction_id';

    protected $fillable = [
        'transaction_date',
        'type', 
        'description',
        'amount',
        'user_id',
        'equity_account_id', 
        'cash_bank_account_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function equityAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'equity_account_id', 'account_id');
    }

    public function cashBankAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'cash_bank_account_id', 'account_id');
    }
}