<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\ChartOfAccount
 *
 * @property int $account_id
 * @property string $account_number
 * @property string $account_name
 * @property string $account_type
 * @property string $normal_balance
 * @property int|null $parent_account_id
 * @property bool $is_active
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ChartOfAccount> $children
 * @property-read int|null $children_count
 * @property-read ChartOfAccount|null $parent
 * @method static \Illuminate\Database\Eloquent\Builder|ChartOfAccount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ChartOfAccount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ChartOfAccount query()
 * @method static \Illuminate\Database\Eloquent\Builder|ChartOfAccount whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChartOfAccount whereAccountName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChartOfAccount whereAccountNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChartOfAccount whereAccountType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChartOfAccount whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChartOfAccount whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChartOfAccount whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChartOfAccount whereNormalBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChartOfAccount whereParentAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChartOfAccount whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ChartOfAccount extends Model
{
    use HasFactory;

    protected $primaryKey = 'account_id';

    protected $fillable = [
        'account_number',
        'account_name',
        'account_type',
        'normal_balance',
        'parent_account_id',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relasi ke akun induk (jika ada).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_account_id', 'account_id');
    }

    /**
     * Relasi ke akun anak (jika ada).
     */
    public function children(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_account_id', 'account_id');
    }

    /**
     * (Nanti) Relasi ke semua entri jurnal umum yang memakai akun ini.
     */
    // public function journalEntries(): HasMany
    // {
    //     return $this->hasMany(GeneralLedger::class, 'chart_of_account_id', 'account_id');
    // }
}