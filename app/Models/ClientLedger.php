<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ClientLedger extends Model
{
    use HasFactory;

    protected $primaryKey = 'ledger_id';

    protected $fillable = [
        'client_id',
        'sales_invoice_id',
        'reference_type',
        'reference_id',
        'transaction_date',
        'type',
        'amount',
        'status',
        'description',
        'user_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'float',
    ];

    /**
     * Dapatkan model induk (SalesReturn, Payment, dll).
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Dapatkan klien pemilik ledger.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }

    /**
     * Dapatkan user yang memproses.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id', 'invoice_id');
    }
}