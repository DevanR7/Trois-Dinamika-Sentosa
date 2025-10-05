<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;
    protected $primaryKey = 'product_id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'product_code',
        'product_name',
        'purchase_price',
        'selling_price',
        'stock_quantity',
        'unit_id',
        'supplier_id', // <-- TAMBAHKAN BARIS INI
        'description',
        'image_path',
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