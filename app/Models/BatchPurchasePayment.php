<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\BatchPurchasePayment
 *
 * @property int $batch_payment_id
 * @property int $supplier_id
 * @property int $processed_by_user_id
 * @property \Illuminate\Support\Carbon $payment_date
 * @property float $total_amount
 * @property int|null $payment_method_id
 * @property int|null $company_bank_account_id
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\CompanyBankAccount|null $companyBankAccount
 * @property-read \App\Models\PaymentMethod|null $paymentMethod
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PurchaseOrderPayment> $payments
 * @property-read int|null $payments_count
 * @property-read \App\Models\User $processor
 * @property-read \App\Models\Supplier $supplier
 * @method static \Illuminate\Database\Eloquent\Builder|BatchPurchasePayment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BatchPurchasePayment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BatchPurchasePayment query()
 * @method static \Illuminate\Database\Eloquent\Builder|BatchPurchasePayment whereBatchPaymentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BatchPurchasePayment whereCompanyBankAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BatchPurchasePayment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BatchPurchasePayment whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BatchPurchasePayment wherePaymentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BatchPurchasePayment wherePaymentMethodId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BatchPurchasePayment whereProcessedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BatchPurchasePayment whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BatchPurchasePayment whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BatchPurchasePayment whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class BatchPurchasePayment extends Model
{
    use HasFactory;

    protected $primaryKey = 'batch_payment_id';

    // ✅ PERBAIKAN: Sesuaikan dengan skema database baru
    protected $fillable = [
        'supplier_id',
        'processed_by_user_id',
        'payment_date',
        'total_amount',
        'payment_method_id',
        'company_bank_account_id', // <-- PERBAIKAN: Dari 'payment_method'
        'notes',
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
        return $this->hasMany(PurchaseOrderPayment::class, 'batch_purchase_payment_id', 'batch_payment_id');
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