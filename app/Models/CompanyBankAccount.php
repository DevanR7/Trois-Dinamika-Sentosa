<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ChartOfAccount;
use App\Traits\LogsActivity;

class CompanyBankAccount extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;
    protected $primaryKey = 'company_bank_account_id';

    protected $fillable = [
        'bank_name',
        'account_name',
        'account_number',
        'chart_of_account_id',
        'is_active',
    ];

    public function salesPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'company_bank_account_id', 'company_bank_account_id');
    }

    public function purchasePayments(): HasMany
    {
        return $this->hasMany(PurchaseOrderPayment::class, 'company_bank_account_id', 'company_bank_account_id');
    }
    
    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id', 'account_id');
    }
}