<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class LoanPayment extends Model
{
    use HasFactory;

    protected $primaryKey = 'payment_id';

    protected $fillable = [
        'loan_id',
        'payment_date',
        'principal_paid',
        'interest_paid',
        'total_paid',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'principal_paid' => 'float',
        'interest_paid' => 'float',
        'total_paid' => 'float',
    ];

    /**
     * Relasi ke pinjaman induk.
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class, 'loan_id', 'loan_id');
    }

    /**
     * Relasi ke user yang mencatat.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}