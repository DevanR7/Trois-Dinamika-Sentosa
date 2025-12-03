<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BulkSalesPayment extends Model
{
    use HasFactory;

    protected $table = 'bulk_sales_payments';
    protected $primaryKey = 'bulk_sales_payment_id';

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
        'reference_number',
        'proof_of_payment_path',
        
        // Kolom untuk Approval/Rejection
        'approved_by_user_id',
        'approved_at',
        'rejected_by_user_id',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'total_amount' => 'float',
        'details' => 'array',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }

    /**
     * ✅ INI BAGIAN PENTINGNYA
     * Nama fungsi ini HARUS 'processedByUser' agar sesuai dengan Controller.
     */
    public function processedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id', 'user_id');
    }

    // Relasi Approved By
    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id', 'user_id');
    }

    // Relasi Rejected By
    public function rejectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_user_id', 'user_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'bulk_sales_payment_id', 'bulk_sales_payment_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'payment_method_id');
    }

    public function companyBankAccount(): BelongsTo
    {
        return $this->belongsTo(CompanyBankAccount::class, 'company_bank_account_id', 'company_bank_account_id');
    }
}