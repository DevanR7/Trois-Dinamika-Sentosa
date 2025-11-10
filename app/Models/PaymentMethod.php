<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // ✅ 1. Import trait

class PaymentMethod extends Model
{
    use HasFactory, SoftDeletes; // ✅ 2. Gunakan trait

    protected $primaryKey = 'payment_method_id';

    protected $fillable = [
        'name',
        'type',
        'required_fields_config',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}