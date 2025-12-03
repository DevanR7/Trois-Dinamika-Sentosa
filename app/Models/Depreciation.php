<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Depreciation extends Model
{
    use HasFactory;
    
    protected $primaryKey = 'depreciation_id';

    protected $fillable = [
        'fixed_asset_id',
        'depreciation_date',
        'amount',
        'journal_group_id',
    ];

    protected $casts = [
        'depreciation_date' => 'date',
        'amount' => 'float',
    ];

    /**
     * Relasi ke Aset Induk
     */
    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class, 'fixed_asset_id', 'asset_id');
    }
}