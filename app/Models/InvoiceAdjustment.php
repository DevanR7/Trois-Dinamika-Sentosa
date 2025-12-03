<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Models\ClientLedger;

class InvoiceAdjustment extends Model
{
    use HasFactory;

    protected $primaryKey = 'adjustment_id';

    protected $fillable = [
        'sales_invoice_id',
        'user_id',
        'adjustment_date',
        'type',
        'amount',
        'reason',
    ];

    protected $casts = [
        'adjustment_date' => 'date',
        'amount' => 'float',
    ];

    /**
     * Dapatkan invoice yang disesuaikan.
     */
    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id', 'invoice_id');
    }

    /**
     * Dapatkan user yang membuat penyesuaian.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Dapatkan entri ledger yang terkait dengan penyesuaian ini.
     */
    public function ledgerEntry(): MorphOne
    {
        return $this->morphOne(ClientLedger::class, 'reference');
    }
}