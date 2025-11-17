<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\PaymentGatewayCallback
 *
 * @property int $callback_id
 * @property int|null $invoice_id
 * @property string $vendor_transaction_id
 * @property string $status
 * @property float $amount
 * @property string|null $payment_type
 * @property array|null $raw_response
 * @property \Illuminate\Support\Carbon $processed_at
 * @property-read \App\Models\SalesInvoice|null $salesInvoice
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentGatewayCallback newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentGatewayCallback newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentGatewayCallback query()
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentGatewayCallback whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentGatewayCallback whereCallbackId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentGatewayCallback whereInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentGatewayCallback wherePaymentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentGatewayCallback whereProcessedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentGatewayCallback whereRawResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentGatewayCallback whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentGatewayCallback whereVendorTransactionId($value)
 * @mixin \Eloquent
 */
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