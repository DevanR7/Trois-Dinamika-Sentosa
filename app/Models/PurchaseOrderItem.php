<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use App\Models\PurchaseOrderItemDiscount;

class PurchaseOrderItem extends Model
{
    use HasFactory;
    protected $primaryKey = 'item_id';

    protected $fillable = [
        'po_id',
        'product_id',
        'quantity',
        'price_per_unit',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'float',
        'price_per_unit' => 'float',
        'subtotal' => 'float',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function discounts()
    {
        return $this->hasMany(PurchaseOrderItemDiscount::class, 'purchase_order_item_id', 'item_id');
    }

}