<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\SalesInvoice;

class PaymentGatewayCallback extends Model
{
    use HasFactory;
    protected $primaryKey = 'callback_id';
    public $timestamps = false;

    protected $fillable = [
        'invoice_id',
        'vendor_transaction_id',
        'status',
        'amount',
        'payment_type',
        'raw_response',
    ];

    protected $casts = [
        'amount' => 'float',
        'raw_response' => 'array',
        'processed_at' => 'datetime',
    ];

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'invoice_id', 'invoice_id');
    }
}