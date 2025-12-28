<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;
use App\Models\SalesInvoice;
use App\Models\PurchaseOrder;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;
    protected $primaryKey = 'user_id'; 

    protected $fillable = [
        'full_name',
        'username',
        'email',
        'role',
        'password',
        'sales_code',
        'google_id',
        'is_approved',
        'nik',
        'address',
        'phone_number',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function salesInvoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class, 'user_id_sales', 'user_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'user_id_admin', 'user_id');
    }
}
