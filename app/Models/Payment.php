<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $primaryKey = 'payment_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'invoice_id',
        'batch_payment_id',
        'payment_method_id', // ✅ Ini adalah ID, bukan teks
        'company_bank_account_id',
        'payment_date',
        'amount',
        'proof_of_payment_path',
        'transaction_id',
        'received_by_user_id',
        'status', // Akan berisi 'completed', 'pending_verification', 'pending_clearance', 'failed'
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'float',
    ];

    public function companyBankAccount(): BelongsTo
    {
        return $this->belongsTo(CompanyBankAccount::class, 'company_bank_account_id', 'company_bank_account_id');
    }
    
    /**
     * Relasi ke metode pembayaran.
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'payment_method_id');
    }

    /**
     * Mendapatkan invoice yang terkait dengan pembayaran ini.
     */
    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'invoice_id', 'invoice_id');
    }

    /**
     * Mendapatkan user yang menerima/memverifikasi pembayaran ini.
     */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_user_id', 'user_id');
    }

    /**
     * Relasi ke batch payment (jika ada).
     */
    public function batchPayment(): BelongsTo
    {
        return $this->belongsTo(BatchPayment::class, 'batch_payment_id', 'batch_payment_id');
    }
}