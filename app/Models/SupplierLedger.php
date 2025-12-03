<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\Supplier;
use App\Models\User;
use App\Models\PurchaseOrder;

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