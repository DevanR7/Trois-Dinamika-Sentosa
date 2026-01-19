<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Models\ClientLedger;
use App\Traits\LogsActivity;

class InvoiceAdjustment extends Model
{
    use HasFactory, LogsActivity;
    protected $primaryKey = 'adjustment_id';

    protected $fillable = [
        'sales_invoice_id',
        'user_id',
        'adjustment_date',
        'type',
        'amount',
        'reason',
        'is_calculation_adjustment',
        'details',
    ];

    protected $casts = [
        'adjustment_date' => 'date',
        'amount' => 'float',
        'is_calculation_adjustment' => 'boolean',
        'details' => 'array',
    ];

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id', 'invoice_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function ledgerEntry(): MorphOne
    {
        return $this->morphOne(ClientLedger::class, 'reference');
    }
}