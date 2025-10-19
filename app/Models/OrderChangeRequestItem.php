<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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