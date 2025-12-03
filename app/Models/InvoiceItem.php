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
    protected $casts = [
        'quantity' => 'float',
        'quantity_returned' => 'float', 
        'price_per_unit' => 'float',
        'hpp' => 'float',              
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