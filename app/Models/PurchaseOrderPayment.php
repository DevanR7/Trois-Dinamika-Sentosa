<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\BatchPurchasePayment;
use App\Models\PaymentMethod;
use App\Models\CompanyBankAccount;

class PurchaseOrderPayment extends Model
{
    use HasFactory;

    // ✅ PERBAIKAN: Sesuaikan dengan skema database baru
    protected $fillable = [
        'po_id', 
        'batch_purchase_payment_id',
        'received_by_user_id', 
        'payment_date', 
        'amount', 
        'payment_method_id',
        'company_bank_account_id',
        'reference_number', 
        'status',             
        'notes'
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'float', // <-- Saya tambahkan untuk konsistensi
    ];

    public function purchaseOrder(): BelongsTo // <-- Tambahkan return type
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id', 'po_id');
    }

    public function receivedBy(): BelongsTo // <-- Tambahkan return type
    {
        return $this->belongsTo(User::class, 'received_by_user_id', 'user_id');
    }
    
    public function bulkPurchasePayment(): BelongsTo
    {
    // Parameter ke-3 (owner key) harus sesuai dengan PK di tabel parent (bulk_purchase_payments)
    return $this->belongsTo(BulkPurchasePayment::class, 'bulk_purchase_payment_id', 'bulk_purchase_payment_id');
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