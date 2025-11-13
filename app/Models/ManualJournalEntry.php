<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualJournalEntry extends Model
{
    use HasFactory;

    protected $primaryKey = 'entry_id';
    
    protected $fillable = [
        'journal_id',
        'chart_of_account_id',
        'debit',
        'credit',
        'description',
    ];

    protected $casts = [
        'debit' => 'float',
        'credit' => 'float',
    ];

    /**
     * Relasi ke header
     */
    public function manualJournal(): BelongsTo
    {
        return $this->belongsTo(ManualJournal::class, 'journal_id', 'journal_id');
    }

    /**
     * Relasi ke Akun COA
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id', 'account_id');
    }
}