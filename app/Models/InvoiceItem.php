<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\SalesInvoice;
use App\Models\Product;

class InvoiceItem extends Model
{
    use HasFactory;
    protected $primaryKey = 'item_id';

    protected $fillable = [
        'invoice_id',
        'product_id',
        'quantity',
        'quantity_returned',
        'price_per_unit',
        'hpp',              
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'float',
        'quantity_returned' => 'float', 
        'price_per_unit' => 'float',
        'hpp' => 'float',              
        'subtotal' => 'float',
    ];

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'invoice_id', 'invoice_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id')->withTrashed();
    }
}