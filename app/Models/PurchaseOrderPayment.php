<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\PaymentMethod;
use App\Models\CompanyBankAccount;
use App\Traits\LogsActivity;

class PurchaseOrderPayment extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'po_id', 
        'bulk_purchase_payment_id',
        'received_by_user_id', 
        'payment_date', 
        'amount', 
        'payment_method_id',
        'company_bank_account_id',
        'reference_number', 
        'proof_of_payment_path',
        'status',             
        'notes'
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'float', 
    ];

    public function purchaseOrder(): BelongsTo 
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id', 'po_id');
    }

    public function receivedBy(): BelongsTo 
    {
        return $this->belongsTo(User::class, 'received_by_user_id', 'user_id');
    }
    
    public function bulkPurchasePayment()
    {
        return $this->belongsTo(BulkPurchasePayment::class, 'bulk_purchase_payment_id', 'bulk_purchase_payment_id');
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