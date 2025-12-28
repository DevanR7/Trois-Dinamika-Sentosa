<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItemDiscount extends Model
{
    use HasFactory;
    protected $fillable = ['purchase_order_item_id', 'percentage'];
}
