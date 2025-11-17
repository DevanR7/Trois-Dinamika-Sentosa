<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * App\Models\InvoiceAdjustment
 *
 * @property int $adjustment_id
 * @property int $sales_invoice_id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon $adjustment_date
 * @property string $type
 * @property float $amount
 * @property string $reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ClientLedger|null $ledgerEntry
 * @property-read \App\Models\SalesInvoice $salesInvoice
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|InvoiceAdjustment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|InvoiceAdjustment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|InvoiceAdjustment query()
 * @method static \Illuminate\Database\Eloquent\Builder|InvoiceAdjustment whereAdjustmentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InvoiceAdjustment whereAdjustmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InvoiceAdjustment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InvoiceAdjustment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InvoiceAdjustment whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InvoiceAdjustment whereSalesInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InvoiceAdjustment whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InvoiceAdjustment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InvoiceAdjustment whereUserId($value)
 * @mixin \Eloquent
 */
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