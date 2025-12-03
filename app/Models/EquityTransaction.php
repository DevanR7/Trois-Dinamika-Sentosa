<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ChartOfAccount;

class EquityTransaction extends Model
{
    use HasFactory;
    protected $primaryKey = 'transaction_id';
    
    /**
     * ✅ DIPERBARUI: Tambahkan kolom baru
     */
    protected $fillable = [
        'transaction_date',
        'type', 
        'description',
        'amount',
        'user_id',
        'equity_account_id', 
        'cash_bank_account_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Relasi ke Akun Ekuitas (COA).
     */
    public function equityAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'equity_account_id', 'account_id');
    }

    /**
     * Relasi ke Akun Kas/Bank (COA).
     */
    public function cashBankAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'cash_bank_account_id', 'account_id');
    }
}