<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


/**
 * App\Models\PurchaseOrderItemDiscount
 *
 * @property int $id
 * @property int $purchase_order_item_id
 * @property string $percentage
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderItemDiscount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderItemDiscount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderItemDiscount query()
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderItemDiscount whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderItemDiscount whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderItemDiscount wherePercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderItemDiscount wherePurchaseOrderItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderItemDiscount whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PurchaseOrderItemDiscount extends Model
{
    use HasFactory;
    protected $fillable = ['purchase_order_item_id', 'percentage'];
}
