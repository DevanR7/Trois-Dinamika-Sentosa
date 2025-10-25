<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedAsset extends Model
{
    use HasFactory;

    protected $primaryKey = 'asset_id';

    protected $fillable = [
        'asset_name',
        'description',
        'purchase_date',
        'purchase_cost',
        'user_id',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_cost' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}