<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\Payment
 *
 * @property int $payment_id
 * @property int $invoice_id
 * @property int|null $batch_payment_id
 * @property int|null $received_by_user_id
 * @property \Illuminate\Support\Carbon $payment_date
 * @property float $amount
 * @property int|null $payment_method_id
 * @property int|null $company_bank_account_id
 * @property string|null $reference_number Untuk No. Giro, No. Cek, atau referensi lainnya
 * @property string|null $proof_of_payment_path
 * @property string|null $transaction_id
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\BatchPayment|null $batchPayment
 * @property-read \App\Models\CompanyBankAccount|null $companyBankAccount
 * @property-read \App\Models\PaymentMethod|null $paymentMethod
 * @property-read \App\Models\User|null $receivedBy
 * @property-read \App\Models\SalesInvoice $salesInvoice
 * @method static \Illuminate\Database\Eloquent\Builder|Payment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Payment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Payment query()
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereBatchPaymentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereCompanyBankAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereInvoiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment wherePaymentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment wherePaymentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment wherePaymentMethodId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereProofOfPaymentPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereReceivedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereReferenceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
        'reference_number',
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