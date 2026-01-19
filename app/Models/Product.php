<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Unit;
use App\Models\Supplier;
use App\Traits\LogsActivity;

class Product extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;
    protected $primaryKey = 'product_id';

    protected $fillable = [
        'product_code',
        'product_name',
        'category_id',
        'supplier_id',
        'unit_id',
        'purchase_price',
        'selling_price',
        'stock_quantity',
        'average_cost',
        'description',
        'image_path',
        'is_active'
    ];

    protected $casts = [
        'stock_quantity' => 'float',
        'purchase_price' => 'float',
        'selling_price' => 'float',
        'average_cost' => 'float',
        'deleted_at' => 'datetime',
    ];

    public function category()
    {   
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id', 'unit_id');
    }

    public function stockOpnameItems()
    {
        return $this->hasMany(StockOpnameItem::class, 'product_id', 'product_id');
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class, 'product_id', 'product_id');
    }
}