<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ChartOfAccount;

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