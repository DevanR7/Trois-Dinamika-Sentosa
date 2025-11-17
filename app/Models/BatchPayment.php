<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\BatchPayment
 *
 * @property int $batch_payment_id
 * @property int $client_id
 * @property int|null $processed_by_user_id
 * @property \Illuminate\Support\Carbon $payment_date
 * @property float $total_amount
 * @property int|null $payment_method_id
 * @property int|null $company_bank_account_id
 * @property string $status
 * @property string|null $notes
 * @property array|null $details
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Client $client
 * @property-read \App\Models\CompanyBankAccount|null $companyBankAccount
 * @property-read \App\Models\PaymentMethod|null $paymentMethod
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment> $payments
 * @property-read int|null $payments_count
 * @property-read \App\Models\User|null $processor
 * @method static \Illuminate\Database\Eloquent\Builder|BatchPayment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BatchPayment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BatchPayment query()
 * @method static \Illuminate\Database\Eloquent\Builder|BatchPayment whereBatchPaymentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BatchPayment whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BatchPayment whereCompanyBankAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BatchPayment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BatchPayment whereDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BatchPayment whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BatchPayment wherePaymentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BatchPayment wherePaymentMethodId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BatchPayment whereProcessedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BatchPayment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BatchPayment whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BatchPayment whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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