<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ChartOfAccount;

/**
 * App\Models\EquityTransaction
 *
 * @property int $transaction_id
 * @property \Illuminate\Support\Carbon $transaction_date
 * @property string $type
 * @property string $description
 * @property float $amount
 * @property int|null $user_id
 * @property int|null $equity_account_id
 * @property int|null $cash_bank_account_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read ChartOfAccount|null $cashBankAccount
 * @property-read ChartOfAccount|null $equityAccount
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|EquityTransaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EquityTransaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EquityTransaction query()
 * @method static \Illuminate\Database\Eloquent\Builder|EquityTransaction whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EquityTransaction whereCashBankAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EquityTransaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EquityTransaction whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EquityTransaction whereEquityAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EquityTransaction whereTransactionDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EquityTransaction whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EquityTransaction whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EquityTransaction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EquityTransaction whereUserId($value)
 * @mixin \Eloquent
 */
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