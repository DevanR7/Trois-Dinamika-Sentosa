<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\PurchaseOrderAdjustment
 *
 * @property int $adjustment_id
 * @property int $purchase_order_id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon $adjustment_date
 * @property string $type
 * @property float $amount
 * @property string $reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\PurchaseOrder $purchaseOrder
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderAdjustment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderAdjustment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderAdjustment query()
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderAdjustment whereAdjustmentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderAdjustment whereAdjustmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderAdjustment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderAdjustment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderAdjustment wherePurchaseOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderAdjustment whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderAdjustment whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderAdjustment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderAdjustment whereUserId($value)
 * @mixin \Eloquent
 */
class PurchaseOrderAdjustment extends Model
{
    use HasFactory;

    protected $primaryKey = 'adjustment_id';

    protected $fillable = [
        'purchase_order_id',
        'user_id',
        'adjustment_date',
        'type',
        'amount',
        'reason',
    ];

    protected $casts = [
        'adjustment_date' => 'date',
        'amount' => 'float',
    ];

    /**
     * Dapatkan PO yang disesuaikan.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id', 'po_id');
    }

    /**
     * Dapatkan user yang membuat penyesuaian.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}