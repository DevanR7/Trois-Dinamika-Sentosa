<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ChartOfAccount;
// ✅ TAMBAHKAN IMPORT BARU
use Illuminate\Database\Eloquent\Relations\HasMany;

class FixedAsset extends Model
{
    use HasFactory;
    protected $primaryKey = 'asset_id';

    /**
     * ✅ DIPERBARUI: Tambahkan semua kolom baru
     */
    protected $fillable = [
        'asset_name',
        'description',
        'purchase_date',
        'purchase_cost',
        'user_id',
        'fixed_asset_account_id',
        'cash_bank_account_id',
        // ✅ KOLOM BARU
        'accumulated_depreciation_account_id',
        'depreciation_expense_account_id',
        'depreciation_method',
        'useful_life_months',
        'salvage_value',
        'current_book_value',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_cost' => 'float',
        'salvage_value' => 'float', // ✅ BARU
        'current_book_value' => 'float', // ✅ BARU
        'useful_life_months' => 'integer', // ✅ BARU
    ];

    /**
     * ✅ DIPERBARUI: Saat membuat Aset baru, pastikan
     * Nilai Buku Awal = Harga Beli
     */
    protected static function booted()
    {
        static::creating(function ($asset) {
            $asset->current_book_value = $asset->purchase_cost;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'fixed_asset_account_id', 'account_id');
    }

    public function cashBankAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'cash_bank_account_id', 'account_id');
    }
    
    // ✅ ===================================
    // ✅ TAMBAHKAN RELASI BARU DI BAWAH INI
    // ✅ ===================================

    /**
     * Relasi ke Akun Akumulasi Penyusutan (Kontra-Aset).
     */
    public function accumulatedDepreciationAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'accumulated_depreciation_account_id', 'account_id');
    }

    /**
     * Relasi ke Akun Beban Penyusutan (Beban).
     */
    public function depreciationExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'depreciation_expense_account_id', 'account_id');
    }

    /**
     * Relasi ke Riwayat Penyusutan.
     */
    public function depreciations(): HasMany
    {
        return $this->hasMany(Depreciation::class, 'fixed_asset_id', 'asset_id');
    }

    public function asset()
    {
        return $this->belongsTo(FixedAsset::class, 'asset_id', 'asset_id');
    }
}