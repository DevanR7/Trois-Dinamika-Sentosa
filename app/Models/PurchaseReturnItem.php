<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\PurchaseReturn;
use App\Models\Product;

class PurchaseReturnItem extends Model
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

    // Relasi ke tabel PurchaseReturn (induk)
    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class, 'return_id', 'return_id');
    }

    // Relasi ke tabel Product
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
}