<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\PurchaseReturnItem
 *
 * @property int $item_id
 * @property int $return_id
 * @property int $product_id
 * @property int $quantity
 * @property string $price_per_unit
 * @property string $subtotal
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\PurchaseReturn $purchaseReturn
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseReturnItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseReturnItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseReturnItem query()
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseReturnItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseReturnItem whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseReturnItem wherePricePerUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseReturnItem whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseReturnItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseReturnItem whereReturnId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseReturnItem whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseReturnItem whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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