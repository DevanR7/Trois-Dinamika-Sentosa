<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ChartOfAccount;

/**
 * App\Models\Expense
 *
 * @property int $expense_id
 * @property \Illuminate\Support\Carbon $expense_date
 * @property string|null $category
 * @property string $description
 * @property float $amount
 * @property int|null $user_id
 * @property int|null $chart_of_account_id
 * @property int|null $cash_bank_account_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read ChartOfAccount|null $cashBankAccount
 * @property-read ChartOfAccount|null $expenseAccount
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|Expense newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Expense newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Expense query()
 * @method static \Illuminate\Database\Eloquent\Builder|Expense whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expense whereCashBankAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expense whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expense whereChartOfAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expense whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expense whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expense whereExpenseDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expense whereExpenseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expense whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Expense whereUserId($value)
 * @mixin \Eloquent
 */
class Expense extends Model
{
    use HasFactory;

    protected $primaryKey = 'expense_id';

    /**
     * Atribut yang dapat diisi secara massal.
     */
    protected $fillable = [
        'expense_date',
        'category',
        'description',
        'amount',
        'user_id',
        'chart_of_account_id',
        'cash_bank_account_id',
    ];

    /**
     * Cast tipe data untuk atribut.
     */
    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'float',
    ];

    /**
     * Relasi ke User yang mencatat pengeluaran ini.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Relasi ke Akun Beban (COA).
     */
    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id', 'account_id');
    }

    /**
     * Relasi ke Akun Kas/Bank (COA) sebagai sumber dana.
     */
    public function cashBankAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'cash_bank_account_id', 'account_id');
    }
}