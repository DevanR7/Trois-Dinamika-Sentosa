<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItemDiscount extends Model
{
    use HasFactory;
    protected $fillable = ['purchase_order_item_id', 'percentage'];
}
