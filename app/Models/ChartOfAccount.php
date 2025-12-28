<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    use HasFactory;

    protected $primaryKey = 'account_id';

    protected $fillable = [
        'account_number',
        'account_name',
        'account_type',
        'normal_balance',
        'parent_account_id',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_account_id', 'account_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_account_id', 'account_id');
    }

    // public function journalEntries(): HasMany
    // {
    //     return $this->hasMany(GeneralLedger::class, 'chart_of_account_id', 'account_id');
    // }
}