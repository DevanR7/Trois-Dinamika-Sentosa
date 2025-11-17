<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\InvoiceItem
 *
 * @property int $item_id
 * @property int $invoice_id
 * @property int $product_id
 * @property int $quantity
 * @property int $quantity_returned
 * @property float $price_per_unit
 * @property float|null $hpp
 * @property float $subtotal
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\SalesInvoice $salesInvoice
 * @method static \Illuminate\Database\Eloquent\Builder|InvoiceItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|InvoiceItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|InvoiceItem query()
 * @method static \Illuminate\Database\Eloquent\Builder|InvoiceItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InvoiceItem whereHpp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InvoiceItem whereInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InvoiceItem whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InvoiceItem wherePricePerUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InvoiceItem whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InvoiceItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InvoiceItem whereQuantityReturned($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InvoiceItem whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InvoiceItem whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class InvoiceItem extends Model
{
    use HasFactory;

    protected $primaryKey = 'item_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    // [UPDATED] Menambahkan hpp dan quantity_returned
    protected $fillable = [
        'invoice_id',
        'product_id',
        'quantity',
        'quantity_returned', // <-- DARI MIGRASI
        'price_per_unit',
        'hpp',               // <-- DARI MIGRASI KITA
        'subtotal',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    // [UPDATED] Menambahkan hpp dan quantity_returned
    protected $casts = [
        'quantity' => 'integer',
        'quantity_returned' => 'integer', // <-- DARI MIGRASI
        'price_per_unit' => 'float',
        'hpp' => 'float',               // <-- DARI MIGRASI KITA
        'subtotal' => 'float',
    ];

    // =================================================================
    // RELASI-RELASI
    // =================================================================

    /**
     * ✅ [FIX] INI ADALAH RELASI YANG HILANG
     * Mendapatkan invoice induk (SalesInvoice) dari item ini.
     */
    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'invoice_id', 'invoice_id');
    }

    /**
     * Relasi ke Product (Sudah ada, saya tambahkan withTrashed)
     */
    public function product(): BelongsTo
    {
        // withTrashed() adalah best practice jika produk dihapus
        // agar data di invoice lama tidak error.
        return $this->belongsTo(Product::class, 'product_id', 'product_id')->withTrashed();
    }
}