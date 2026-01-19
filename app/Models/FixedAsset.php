<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ChartOfAccount;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Depreciation;
use App\Traits\LogsActivity;

class FixedAsset extends Model
{
    use HasFactory, LogsActivity;
    protected $primaryKey = 'asset_id';

    protected $fillable = [
        'asset_name',
        'description',
        'purchase_date',
        'purchase_cost',
        'user_id',
        'fixed_asset_account_id',
        'cash_bank_account_id',
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
        'salvage_value' => 'float',
        'current_book_value' => 'float', 
        'useful_life_months' => 'integer', 
    ];

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

    public function accumulatedDepreciationAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'accumulated_depreciation_account_id', 'account_id');
    }

    public function depreciationExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'depreciation_expense_account_id', 'account_id');
    }

    public function depreciations(): HasMany
    {
        return $this->hasMany(Depreciation::class, 'fixed_asset_id', 'asset_id');
    }

}