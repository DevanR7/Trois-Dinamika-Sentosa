<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\SalesReturnItem
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
 * @property-read \App\Models\SalesReturn $salesReturn
 * @method static \Illuminate\Database\Eloquent\Builder|SalesReturnItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SalesReturnItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SalesReturnItem query()
 * @method static \Illuminate\Database\Eloquent\Builder|SalesReturnItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SalesReturnItem whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SalesReturnItem wherePricePerUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SalesReturnItem whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SalesReturnItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SalesReturnItem whereReturnId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SalesReturnItem whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SalesReturnItem whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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