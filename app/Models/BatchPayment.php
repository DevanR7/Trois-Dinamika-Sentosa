<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BatchPayment extends Model
{
    use HasFactory;

    protected $primaryKey = 'batch_payment_id';

    protected $fillable = [
        'client_id',
        'processed_by_user_id',
        'payment_date',
        'total_amount',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'total_amount' => 'float',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id', 'user_id');
    }

    /**
     * Mendapatkan semua alokasi pembayaran (Payment) yang dihasilkan dari batch ini.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'batch_payment_id', 'batch_payment_id');
    }
}
