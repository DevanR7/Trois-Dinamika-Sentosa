<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}