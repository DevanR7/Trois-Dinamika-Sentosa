<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BulkPurchasePayment extends Model
{
    use HasFactory;

    // ✅ TAMBAHAN: Definisi tabel eksplisit
    protected $table = 'bulk_purchase_payments';

    // ✅ PERBAIKAN FATAL: Primary key harus sesuai migrasi
    protected $primaryKey = 'bulk_purchase_payment_id'; 

    protected $fillable = [
        'supplier_id',
        'processed_by_user_id',
        'payment_date',
        'total_amount',
        'payment_method_id',
        'company_bank_account_id',
        'notes',
        // 'status', // Tambahkan ini jika di migrasi ada kolom status
        // 'reference_number', // Tambahkan ini jika ada di migrasi
        // 'proof_of_payment_path', // Tambahkan ini jika ada di migrasi
    ];

    protected $casts = [
        'payment_date' => 'date',
        'total_amount' => 'float',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id', 'user_id');
    }

    public function payments(): HasMany
    {
        // Parameter 2: Foreign Key di tabel anak (purchase_order_payments)
        // Parameter 3: Local Key di tabel induk (bulk_purchase_payments)
        return $this->hasMany(PurchaseOrderPayment::class, 'bulk_purchase_payment_id', 'bulk_purchase_payment_id');
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