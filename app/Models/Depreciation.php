<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\Depreciation
 *
 * @property int $depreciation_id
 * @property int $fixed_asset_id
 * @property \Illuminate\Support\Carbon $depreciation_date
 * @property float $amount
 * @property string $journal_group_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\FixedAsset $fixedAsset
 * @method static \Illuminate\Database\Eloquent\Builder|Depreciation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Depreciation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Depreciation query()
 * @method static \Illuminate\Database\Eloquent\Builder|Depreciation whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Depreciation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Depreciation whereDepreciationDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Depreciation whereDepreciationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Depreciation whereFixedAssetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Depreciation whereJournalGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Depreciation whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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