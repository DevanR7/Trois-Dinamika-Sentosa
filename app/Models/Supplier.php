<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Supplier
 *
 * @property int $supplier_id
 * @property string $supplier_name
 * @property string|null $person_in_charge
 * @property string|null $phone_number
 * @property string|null $address
 * @property string|null $npwp
 * @property string|null $bank_name
 * @property string|null $account_number
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BatchPurchasePayment> $batchPurchasePayments
 * @property-read int|null $batch_purchase_payments_count
 * @property-read float $balance
 * @property-read float $pending_balance
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SupplierLedger> $ledgers
 * @property-read int|null $ledgers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Product> $products
 * @property-read int|null $products_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PurchaseOrder> $purchaseOrders
 * @property-read int|null $purchase_orders_count
 * @method static \Illuminate\Database\Eloquent\Builder|Supplier newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Supplier newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Supplier onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Supplier query()
 * @method static \Illuminate\Database\Eloquent\Builder|Supplier whereAccountNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Supplier whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Supplier whereBankName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Supplier whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Supplier whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Supplier whereNpwp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Supplier wherePersonInCharge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Supplier wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Supplier whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Supplier whereSupplierName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Supplier whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Supplier withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Supplier withoutTrashed()
 * @mixin \Eloquent
 */
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

    public function batchPurchasePayments(): HasMany
    {
        return $this->hasMany(BatchPurchasePayment::class, 'supplier_id', 'supplier_id');
    }

    public function ledgers(): HasMany
    {
        return $this->hasMany(SupplierLedger::class, 'supplier_id', 'supplier_id');
    }

    /**
     * Accessor untuk mendapatkan saldo deposit (debit) kita saat ini.
     * Panggil ini di view/controller: $supplier->balance
     *
     * @return float
     */
    public function getBalanceAttribute(): float
    {
        // ✅ DIUBAH: Hanya menjumlahkan yang statusnya 'available'
        return $this->ledgers()->where('status', 'available')->sum('amount');
    }

    /**
     * ✅ BARU: Accessor untuk mendapatkan saldo deposit yang DITAHAN (pending).
     *
     * @return float
     */
    public function getPendingBalanceAttribute(): float
    {
        return $this->ledgers()
                    ->where('status', 'pending')
                    ->where('type', 'credit')
                    ->sum('amount');
    }
}
