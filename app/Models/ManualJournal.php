<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

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