<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\PurchaseOrderItem
 *
 * @property int $item_id
 * @property int $po_id
 * @property int $product_id
 * @property int $quantity
 * @property string $quantity_returned
 * @property float $price_per_unit
 * @property float $subtotal
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PurchaseOrderItemDiscount> $discounts
 * @property-read int|null $discounts_count
 * @property-read \App\Models\Product $product
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderItem query()
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderItem whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderItem wherePoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderItem wherePricePerUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderItem whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderItem whereQuantityReturned($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderItem whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderItem whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
    'quantity' => 'integer',
    'price_per_unit' => 'float', // <-- Benar
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