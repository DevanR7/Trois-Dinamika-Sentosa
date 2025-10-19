<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
// use Illuminate\Database\Eloquent\Model; // This is not needed if extending Authenticatable
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Order; // ✅ TAMBAHKAN: Import model Order

class Client extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;
    protected $primaryKey = 'client_id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'client_name',
        'email',
        'password',
        'person_in_charge',
        'address',
        'phone_number',
        'is_approved',
        'google_id', // Make sure google_id is fillable if you set it during registration/login
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_approved' => 'boolean',
    ];

    /**
     * Get all of the sales invoices for the Client.
     */
    public function salesInvoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class, 'client_id', 'client_id');
    }

    /**
     * Get all of the orders for the Client.
     * ✅ BERUBAH: Nama method dan model yang dirujuk.
     */
    public function orders(): HasMany // ✅ BERUBAH: Nama method
    {
        // ✅ BERUBAH: Model yang dirujuk
        return $this->hasMany(Order::class, 'client_id', 'client_id');
    }

    // You might also want a relationship for client-created orders specifically
    public function clientOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'client_id', 'client_id')->where('order_source', 'client');
    }
}