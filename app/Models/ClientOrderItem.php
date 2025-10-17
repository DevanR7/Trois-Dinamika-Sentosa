<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientOrderItem extends Model
{
    use HasFactory;
    protected $primaryKey = 'item_id';
    protected $fillable = ['client_order_id', 'product_id', 'quantity', 'price_per_unit', 'subtotal'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
}