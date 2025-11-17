<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * App\Models\SupplierLedger
 *
 * @property int $ledger_id
 * @property int $supplier_id
 * @property int|null $purchase_order_id
 * @property string $reference_type
 * @property int $reference_id
 * @property \Illuminate\Support\Carbon $transaction_date
 * @property string $type
 * @property float $amount
 * @property string $status
 * @property string $description
 * @property int|null $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\PurchaseOrder|null $purchaseOrder
 * @property-read Model|\Eloquent $reference
 * @property-read \App\Models\Supplier $supplier
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|SupplierLedger newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SupplierLedger newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SupplierLedger query()
 * @method static \Illuminate\Database\Eloquent\Builder|SupplierLedger whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SupplierLedger whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SupplierLedger whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SupplierLedger whereLedgerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SupplierLedger wherePurchaseOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SupplierLedger whereReferenceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SupplierLedger whereReferenceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SupplierLedger whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SupplierLedger whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SupplierLedger whereTransactionDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SupplierLedger whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SupplierLedger whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SupplierLedger whereUserId($value)
 * @mixin \Eloquent
 */
class SupplierLedger extends Model
{
    use HasFactory;

    protected $primaryKey = 'ledger_id';

    protected $fillable = [
        'supplier_id',
        'purchase_order_id',
        'reference_type',
        'reference_id',
        'transaction_date',
        'type',
        'amount',
        'status',
        'description',
        'user_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'float',
    ];

    /**
     * Dapatkan model induk (PurchaseReturn, PurchaseOrderPayment, dll).
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Dapatkan supplier pemilik ledger.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    /**
     * Dapatkan user yang memproses.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id', 'po_id');
    }
}