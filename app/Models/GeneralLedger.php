<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\ChartOfAccount;

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

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id', 'account_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function bankReconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class, 'bank_reconciliation_id', 'reconciliation_id');
    }
}