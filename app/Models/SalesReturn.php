<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesReturn extends Model
{
    use HasFactory;

    protected $primaryKey = 'return_id';

    protected $fillable = [
        'return_number',
        'client_id',
        'sales_invoice_id',
        'user_id',
        'return_date',
        'notes',
        'total_amount',
    ];

    protected $casts = [
        'return_date' => 'date',
    ];

    // Relasi ke tabel SalesInvoice (induk)
    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id', 'invoice_id');
    }

    // Relasi ke tabel Client
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }
    
    // Relasi ke tabel User yang memproses
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // Relasi ke item-item retur (anak)
    public function items(): HasMany
    {
        return $this->hasMany(SalesReturnItem::class, 'return_id', 'return_id');
    }
}