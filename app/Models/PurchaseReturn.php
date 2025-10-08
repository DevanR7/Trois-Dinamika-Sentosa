<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReturn extends Model
{
    use HasFactory;

    protected $primaryKey = 'return_id';

    protected $fillable = [
        'return_number',
        'supplier_id',
        'purchase_order_id',
        'user_id',
        'return_date',
        'notes',
        'total_amount',
    ];

    protected $casts = [
        'return_date' => 'date',
    ];

    // Relasi ke tabel PurchaseOrder (induk)
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id', 'po_id');
    }

    // Relasi ke tabel Supplier
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    // Relasi ke tabel User yang memproses
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // Relasi ke item-item retur (anak)
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class, 'return_id', 'return_id');
    }
}