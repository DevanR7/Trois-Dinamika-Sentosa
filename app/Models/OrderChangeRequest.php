<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\OrderChangeRequest
 *
 * @property int $request_id
 * @property int $order_id
 * @property int $client_id
 * @property string $request_type Jenis permintaan
 * @property string $status
 * @property string|null $client_notes Catatan dari klien
 * @property string|null $admin_notes Catatan/alasan dari admin
 * @property int|null $processed_by_user_id
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Client $client
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OrderChangeRequestItem> $items
 * @property-read int|null $items_count
 * @property-read \App\Models\Order $order
 * @property-read \App\Models\User|null $processor
 * @method static \Illuminate\Database\Eloquent\Builder|OrderChangeRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderChangeRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderChangeRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderChangeRequest whereAdminNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderChangeRequest whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderChangeRequest whereClientNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderChangeRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderChangeRequest whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderChangeRequest whereProcessedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderChangeRequest whereProcessedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderChangeRequest whereRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderChangeRequest whereRequestType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderChangeRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderChangeRequest whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class OrderChangeRequest extends Model
{
    use HasFactory;

    protected $primaryKey = 'request_id';

    protected $fillable = [
        'order_id',
        'client_id',
        'request_type',
        'status',
        'client_notes',
        'admin_notes',
        'processed_by_user_id',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id', 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderChangeRequestItem::class, 'order_change_request_id', 'request_id');
    }
}