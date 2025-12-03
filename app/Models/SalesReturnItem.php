<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\SalesReturn;
use App\Models\Product;

class SalesReturnItem extends Model
{
    use HasFactory;

    protected $primaryKey = 'item_id';

    protected $fillable = [
        'return_id',
        'product_id',
        'quantity',
        'price_per_unit',
        'subtotal',
    ];

    // Relasi ke tabel SalesReturn (induk)
    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class, 'return_id', 'return_id');
    }

    // Relasi ke tabel Product
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
}