<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Traits\LogsActivity;

class PurchaseOrderAdjustment extends Model
{
    use HasFactory, LogsActivity;
    protected $primaryKey = 'adjustment_id';

    protected $fillable = [
        'purchase_order_id',
        'user_id',
        'adjustment_date',
        'type',
        'amount',
        'reason',
        'details',
    ];

    protected $casts = [
        'adjustment_date' => 'date',
        'amount' => 'float',
        'details' => 'array',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id', 'po_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
