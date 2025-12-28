<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Order;
use App\Models\Client;
use App\Models\User;
use App\Models\OrderChangeRequestItem;

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