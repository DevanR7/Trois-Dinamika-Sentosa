<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    use HasFactory;
    protected $primaryKey = 'supplier_id';

    protected $fillable = [
        'supplier_name',     
        'person_in_charge', 
        'phone_number',
        'address',
        'npwp',
        'bank_name',
        'account_number',
    ];
    /**
     * Mendapatkan semua produk yang disuplai oleh supplier ini.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'supplier_id', 'supplier_id');
    }

    /**
     * Mendapatkan semua pesanan pembelian (purchase order) ke supplier ini.
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'supplier_id', 'supplier_id');
    }
}
