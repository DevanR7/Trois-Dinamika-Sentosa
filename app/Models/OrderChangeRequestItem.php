<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\OrderChangeRequest;
use App\Models\Product;

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
        'original_quantity' => 'float', 
        'requested_quantity' => 'float', 
        'price_per_unit' => 'float',
        'subtotal' => 'float',
    ];

    public function changeRequest(): BelongsTo
    {
        return $this->belongsTo(OrderChangeRequest::class, 'order_change_request_id', 'request_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id')->withTrashed();
    }
}