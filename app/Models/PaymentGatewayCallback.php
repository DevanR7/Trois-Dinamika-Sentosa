<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentGatewayCallback extends Model
{
    use HasFactory;

    /**
     * Nama primary key.
     */
    protected $primaryKey = 'callback_id';

    /**
     * Memberitahu Laravel bahwa model ini tidak menggunakan
     * kolom created_at dan updated_at standar.
     */
    public $timestamps = false;

    /**
     * Atribut yang bisa diisi secara massal.
     */
    protected $fillable = [
        'invoice_id',
        'vendor_transaction_id',
        'status',
        'amount',
        'payment_type',
        'raw_response',
    ];

    /**
     * Tipe data asli dari atribut.
     */
    protected $casts = [
        'amount' => 'float',
        'raw_response' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Relasi ke SalesInvoice.
     */
    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'invoice_id', 'invoice_id');
    }
}