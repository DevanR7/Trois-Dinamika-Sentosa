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

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'supplier_id', 'supplier_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'supplier_id', 'supplier_id');
    }

    public function bulkPurchasePayments(): HasMany
    {
        return $this->hasMany(BulkPurchasePayment::class, 'supplier_id', 'supplier_id');
    }

    public function ledgers(): HasMany
    {
        return $this->hasMany(SupplierLedger::class, 'supplier_id', 'supplier_id');
    }

    public function getBalanceAttribute(): float
    {
        return $this->ledgers()->where('status', 'available')->sum('amount');
    }

    public function getPendingBalanceAttribute(): float
    {
        return $this->ledgers()
                    ->where('status', 'pending')
                    ->where('type', 'credit')
                    ->sum('amount');
    }
}
