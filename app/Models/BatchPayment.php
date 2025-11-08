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

    // ✅ PERBAIKAN: Sesuaikan dengan skema database baru
    protected $fillable = [
        'client_id',
        'processed_by_user_id',
        'payment_date',
        'total_amount',
        'payment_method_id',
        'company_bank_account_id', 
        'notes',
        'status',
        'details',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'total_amount' => 'float',
        'details' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id', 'user_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'batch_payment_id', 'batch_payment_id');
    }

    // ✅ PERBAIKAN: Tambahkan relasi ke PaymentMethod
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'payment_method_id');
    }

    public function companyBankAccount(): BelongsTo
    {
        return $this->belongsTo(CompanyBankAccount::class, 'company_bank_account_id', 'company_bank_account_id');
    }
}