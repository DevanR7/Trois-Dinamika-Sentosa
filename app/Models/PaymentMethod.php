<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\LogsActivity;

class PaymentMethod extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;
    protected $primaryKey = 'payment_method_id';

    protected $fillable = [
        'name',
        'type',
        'client_input_config',
        'client_status_default',
        'internal_input_config',
        'internal_status_default',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getCurrentInputConfigAttribute(): string
    {
        if (Auth::guard('client')->check()) {
            return $this->client_input_config;
        }
        return $this->internal_input_config;
    }

    public function getCurrentStatusAttribute(): string
    {
        if (Auth::guard('client')->check()) {
            return $this->client_status_default;
        }
        return $this->internal_status_default;
    }
}