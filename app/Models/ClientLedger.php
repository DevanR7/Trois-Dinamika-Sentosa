<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * App\Models\ClientLedger
 *
 * @property int $ledger_id
 * @property int $client_id
 * @property int|null $sales_invoice_id
 * @property string $reference_type
 * @property int $reference_id
 * @property \Illuminate\Support\Carbon $transaction_date
 * @property string $type
 * @property float $amount
 * @property string $status
 * @property string $description
 * @property int|null $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Client $client
 * @property-read Model|\Eloquent $reference
 * @property-read \App\Models\SalesInvoice|null $salesInvoice
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|ClientLedger newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ClientLedger newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ClientLedger query()
 * @method static \Illuminate\Database\Eloquent\Builder|ClientLedger whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientLedger whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientLedger whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientLedger whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientLedger whereLedgerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientLedger whereReferenceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientLedger whereReferenceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientLedger whereSalesInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientLedger whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientLedger whereTransactionDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientLedger whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientLedger whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientLedger whereUserId($value)
 * @mixin \Eloquent
 */
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