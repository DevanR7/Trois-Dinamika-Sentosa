<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Unit;
use App\Models\Supplier;

class Product extends Model
{
    use HasFactory, SoftDeletes;
    protected $primaryKey = 'product_id';

    protected $fillable = [
        'product_code',
        'product_name',
        'purchase_price',
        'selling_price',
        'stock_quantity',
        'unit_id',
        'supplier_id', 
        'description',
        'image_path',
        'average_cost',
    ];

    protected $casts = [
        'stock_quantity' => 'float',
        'purchase_price' => 'float',
        'selling_price' => 'float',
        'average_cost' => 'float',
        'deleted_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id', 'unit_id');
    }
}