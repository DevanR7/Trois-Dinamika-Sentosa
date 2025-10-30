<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BatchPurchasePayment extends Model
{
    use HasFactory;

    protected $primaryKey = 'batch_payment_id';

    protected $fillable = [
        'supplier_id',
        'processed_by_user_id',
        'payment_date',
        'total_amount',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'total_amount' => 'float',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id', 'user_id');
    }

    /**
     * Mendapatkan semua alokasi pembayaran (Payment) yang dihasilkan dari batch ini.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(PurchaseOrderPayment::class, 'batch_purchase_payment_id', 'batch_payment_id');
    }
}