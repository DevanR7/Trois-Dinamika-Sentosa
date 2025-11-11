<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ChartOfAccount;

class FixedAsset extends Model
{
    use HasFactory;

    protected $primaryKey = 'asset_id';

    protected $fillable = [
        'asset_name',
        'description',
        'purchase_date',
        'purchase_cost',
        'user_id',
        'fixed_asset_account_id',
        'cash_bank_account_id',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_cost' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Relasi ke Akun Aset Tetap (COA).
     */
    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'fixed_asset_account_id', 'account_id');
    }

    /**
     * Relasi ke Akun Kas/Bank (COA) sebagai sumber dana.
     */
    public function cashBankAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'cash_bank_account_id', 'account_id');
    }
}