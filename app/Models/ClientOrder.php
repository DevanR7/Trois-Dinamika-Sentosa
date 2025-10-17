<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientOrder extends Model
{
    use HasFactory;
    protected $primaryKey = 'client_order_id';
    protected $fillable = ['client_id', 'order_date', 'total_amount', 'status', 'notes'];
    protected $casts = ['order_date' => 'date'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ClientOrderItem::class, 'client_order_id', 'client_order_id');
    }
}