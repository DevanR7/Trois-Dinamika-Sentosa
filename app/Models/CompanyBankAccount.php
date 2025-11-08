<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyBankAccount extends Model
{
    use HasFactory;

    protected $primaryKey = 'company_bank_account_id';

    protected $fillable = [
        'bank_name',
        'account_name',
        'account_number',
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
}