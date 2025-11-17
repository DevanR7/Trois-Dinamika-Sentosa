<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\PurchaseOrderPayment
 *
 * @property int $id
 * @property int $po_id
 * @property int|null $batch_purchase_payment_id
 * @property int|null $received_by_user_id
 * @property \Illuminate\Support\Carbon $payment_date
 * @property float $amount
 * @property int|null $payment_method_id
 * @property int|null $company_bank_account_id
 * @property string|null $reference_number Untuk No. Giro, No. Cek, atau referensi lainnya
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\BatchPurchasePayment|null $batchPurchasePayment
 * @property-read \App\Models\CompanyBankAccount|null $companyBankAccount
 * @property-read \App\Models\PaymentMethod|null $paymentMethod
 * @property-read \App\Models\PurchaseOrder $purchaseOrder
 * @property-read \App\Models\User|null $receivedBy
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderPayment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderPayment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderPayment query()
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderPayment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderPayment whereBatchPurchasePaymentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderPayment whereCompanyBankAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderPayment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderPayment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderPayment whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderPayment wherePaymentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderPayment wherePaymentMethodId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderPayment wherePoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderPayment whereReceivedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderPayment whereReferenceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderPayment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PurchaseOrderPayment whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
    
    public function batchPurchasePayment(): BelongsTo
    {
        return $this->belongsTo(BatchPurchasePayment::class, 'batch_purchase_payment_id', 'batch_payment_id');
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