<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ChartOfAccount;
// ✅ TAMBAHKAN IMPORT BARU
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\FixedAsset
 *
 * @property int $asset_id
 * @property string $asset_name
 * @property string|null $description
 * @property \Illuminate\Support\Carbon $purchase_date
 * @property float $purchase_cost
 * @property int|null $user_id
 * @property int|null $fixed_asset_account_id
 * @property int|null $cash_bank_account_id
 * @property int|null $accumulated_depreciation_account_id
 * @property int|null $depreciation_expense_account_id
 * @property string|null $depreciation_method
 * @property int|null $useful_life_months
 * @property float $salvage_value
 * @property float|null $current_book_value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read ChartOfAccount|null $accumulatedDepreciationAccount
 * @property-read FixedAsset|null $asset
 * @property-read ChartOfAccount|null $assetAccount
 * @property-read ChartOfAccount|null $cashBankAccount
 * @property-read ChartOfAccount|null $depreciationExpenseAccount
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Depreciation> $depreciations
 * @property-read int|null $depreciations_count
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|FixedAsset newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FixedAsset newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FixedAsset query()
 * @method static \Illuminate\Database\Eloquent\Builder|FixedAsset whereAccumulatedDepreciationAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FixedAsset whereAssetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FixedAsset whereAssetName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FixedAsset whereCashBankAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FixedAsset whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FixedAsset whereCurrentBookValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FixedAsset whereDepreciationExpenseAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FixedAsset whereDepreciationMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FixedAsset whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FixedAsset whereFixedAssetAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FixedAsset wherePurchaseCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FixedAsset wherePurchaseDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FixedAsset whereSalvageValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FixedAsset whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FixedAsset whereUsefulLifeMonths($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FixedAsset whereUserId($value)
 * @mixin \Eloquent
 */
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