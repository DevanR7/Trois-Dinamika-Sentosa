<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ChartOfAccount;

/**
 * App\Models\CompanyBankAccount
 *
 * @property int $company_bank_account_id
 * @property string $bank_name
 * @property string $account_name
 * @property string|null $account_number
 * @property int|null $chart_of_account_id
 * @property int $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read ChartOfAccount|null $account
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PurchaseOrderPayment> $purchasePayments
 * @property-read int|null $purchase_payments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment> $salesPayments
 * @property-read int|null $sales_payments_count
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyBankAccount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyBankAccount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyBankAccount query()
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyBankAccount whereAccountName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyBankAccount whereAccountNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyBankAccount whereBankName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyBankAccount whereChartOfAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyBankAccount whereCompanyBankAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyBankAccount whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyBankAccount whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyBankAccount whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class CompanyBankAccount extends Model
{
    use HasFactory;

    protected $primaryKey = 'company_bank_account_id';

    protected $fillable = [
        'bank_name',
        'account_name',
        'account_number',
        'chart_of_account_id',
        'is_active',
    ];

    // Relasi ke semua transaksi yang masuk ke akun ini
    public function salesPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'company_bank_account_id', 'company_bank_account_id');
    }

    public function purchasePayments(): HasMany
    {
        return $this->hasMany(PurchaseOrderPayment::class, 'company_bank_account_id', 'company_bank_account_id');
    }

    /**
     * Relasi ke Akun Aset (COA) yang mewakili akun bank ini.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id', 'account_id');
    }
}