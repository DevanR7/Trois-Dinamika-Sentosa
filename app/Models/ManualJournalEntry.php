<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\ManualJournalEntry
 *
 * @property int $entry_id
 * @property int $journal_id
 * @property int $chart_of_account_id
 * @property float $debit
 * @property float $credit
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ChartOfAccount $account
 * @property-read \App\Models\ManualJournal $manualJournal
 * @method static \Illuminate\Database\Eloquent\Builder|ManualJournalEntry newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ManualJournalEntry newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ManualJournalEntry query()
 * @method static \Illuminate\Database\Eloquent\Builder|ManualJournalEntry whereChartOfAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManualJournalEntry whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManualJournalEntry whereCredit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManualJournalEntry whereDebit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManualJournalEntry whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManualJournalEntry whereEntryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManualJournalEntry whereJournalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManualJournalEntry whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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