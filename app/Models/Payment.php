<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

class Payment extends Model
{
    use HasFactory, LogsActivity;

    protected $primaryKey = 'payment_id';

    protected $fillable = [
        'invoice_id',
        'bulk_sales_payment_id',
        'payment_method_id',
        'company_bank_account_id',
        'reference_number',
        'payment_date',
        'amount',
        'proof_of_payment_path',
        
        // WAJIB ADA: Untuk menyimpan Transaction ID Midtrans / Referensi Unik
        'transaction_id', 
        
        'received_by_user_id',
        'status', // pending_verification, completed, failed
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'float',
    ];

    // --- RELASI ---

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'invoice_id', 'invoice_id');
    }

    public function companyBankAccount(): BelongsTo
    {
        return $this->belongsTo(CompanyBankAccount::class, 'company_bank_account_id', 'company_bank_account_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'payment_method_id');
    }
    
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_user_id', 'user_id');
    }

    public function bulkPayment(): BelongsTo
    {
        return $this->belongsTo(BulkSalesPayment::class, 'bulk_sales_payment_id', 'bulk_sales_payment_id');
    }
}