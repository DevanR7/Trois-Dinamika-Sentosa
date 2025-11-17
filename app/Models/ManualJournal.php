<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * App\Models\ManualJournal
 *
 * @property int $journal_id
 * @property string $journal_number
 * @property \Illuminate\Support\Carbon $entry_date
 * @property string $description
 * @property float $total_debit
 * @property float $total_credit
 * @property int|null $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ManualJournalEntry> $entries
 * @property-read int|null $entries_count
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|ManualJournal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ManualJournal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ManualJournal query()
 * @method static \Illuminate\Database\Eloquent\Builder|ManualJournal whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManualJournal whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManualJournal whereEntryDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManualJournal whereJournalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManualJournal whereJournalNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManualJournal whereTotalCredit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManualJournal whereTotalDebit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManualJournal whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManualJournal whereUserId($value)
 * @mixin \Eloquent
 */
class ManualJournal extends Model
{
    use HasFactory;

    protected $primaryKey = 'journal_id';

    protected $fillable = [
        'journal_number',
        'entry_date',
        'description',
        'total_debit',
        'total_credit',
        'user_id',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'total_debit' => 'float',
        'total_credit' => 'float',
    ];

    /**
     * Relasi ke baris-baris entri (detail)
     */
    public function entries(): HasMany
    {
        return $this->hasMany(ManualJournalEntry::class, 'journal_id', 'journal_id');
    }

    /**
     * Relasi ke user yang membuat
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Generate Nomor Jurnal Manual
     */
    public static function generateJournalNumber(): string
    {
        $yearMonth = now()->format('Ym');
        $prefix = "JUM-"; // Jurnal Umum Manual
        
        $latestJournal = self::where('journal_number', 'like', $prefix . $yearMonth . '%')
                             ->orderBy('journal_number', 'desc')
                             ->first();
        
        $nextSequence = 1;
        if ($latestJournal) {
            $lastSequence = (int) substr($latestJournal->journal_number, -4);
            $nextSequence = $lastSequence + 1;
        }
        
        $sequencePadded = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
        
        return $prefix . $yearMonth . '-' . $sequencePadded;
    }
}