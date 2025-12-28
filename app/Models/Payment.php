<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\CompanyBankAccount;
use App\Models\PaymentMethod;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Models\BulkSalesPayment;

class Payment extends Model
{
    use HasFactory;
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
        'transaction_id',
        'received_by_user_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'float',
    ];

    public function companyBankAccount(): BelongsTo
    {
        return $this->belongsTo(CompanyBankAccount::class, 'company_bank_account_id', 'company_bank_account_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'payment_method_id');
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'invoice_id', 'invoice_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_user_id', 'user_id');
    }

    public function batchPayment(): BelongsTo
    {
        return $this->belongsTo(BulkSalesPayment::class, 'batch_payment_id', 'batch_payment_id');
    }
}