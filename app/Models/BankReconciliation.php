<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id', 'account_id');
    }

    public function companyBankAccount(): BelongsTo
    {
        return $this->belongsTo(CompanyBankAccount::class, 'company_bank_account_id', 'company_bank_account_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(GeneralLedger::class, 'bank_reconciliation_id', 'reconciliation_id');
    }
}