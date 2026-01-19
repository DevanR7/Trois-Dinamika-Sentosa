<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class StockOpnameItem extends Model
{
    use HasFactory, LogsActivity;
    protected $primaryKey = 'opname_item_id';
    protected $fillable = ['opname_id', 'product_id', 'system_qty', 'physical_qty', 'difference', 'cost_per_unit', 'adjustment_value'];
    
    protected $casts = [
        'system_qty' => 'float',   
        'physical_qty' => 'float', 
        'difference' => 'float',  
        'cost_per_unit' => 'float',
        'adjustment_value' => 'float'
    ];

    public function product() 
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
    
}