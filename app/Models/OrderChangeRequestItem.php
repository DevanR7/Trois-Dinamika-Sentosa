<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\OrderChangeRequestItem
 *
 * @property int $item_id
 * @property int $order_change_request_id
 * @property int $product_id
 * @property int|null $original_quantity
 * @property int $requested_quantity
 * @property string $action
 * @property float $price_per_unit
 * @property float $subtotal
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OrderChangeRequest $changeRequest
 * @property-read \App\Models\Product $product
 * @method static \Illuminate\Database\Eloquent\Builder|OrderChangeRequestItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderChangeRequestItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderChangeRequestItem query()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderChangeRequestItem whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderChangeRequestItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderChangeRequestItem whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderChangeRequestItem whereOrderChangeRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderChangeRequestItem whereOriginalQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderChangeRequestItem wherePricePerUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderChangeRequestItem whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderChangeRequestItem whereRequestedQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderChangeRequestItem whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderChangeRequestItem whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class OrderChangeRequestItem extends Model
{
    use HasFactory;

    protected $primaryKey = 'item_id';

    protected $fillable = [
        'order_change_request_id',
        'product_id',
        'original_quantity',
        'requested_quantity',
        'action',
        'price_per_unit',
        'subtotal',
    ];

    protected $casts = [
        'price_per_unit' => 'float',
        'subtotal' => 'float',
    ];

    public function changeRequest(): BelongsTo
    {
        return $this->belongsTo(OrderChangeRequest::class, 'order_change_request_id', 'request_id');
    }

    public function product(): BelongsTo
    {
        // Gunakan withTrashed agar produk yang sudah dihapus tetap bisa ditampilkan
        return $this->belongsTo(Product::class, 'product_id', 'product_id')->withTrashed();
    }
}