<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable; 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes; // <-- Tambahkan ini

    class Client extends Authenticatable
    {
        use HasApiTokens, HasFactory, Notifiable, SoftDeletes; 
        protected $primaryKey = 'client_id';

        /**
         * The attributes that are mass assignable.
         */
        protected $fillable = [
            'client_name',
            'email', // <-- Tambahkan
            'password', // <-- Tambahkan
            'person_in_charge',
            'address',
            'phone_number',
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
        ];

        /**
         * Mendapatkan semua invoice penjualan untuk client ini.
         */
        public function salesInvoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class, 'client_id', 'client_id');
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class, 'client_id', 'client_id');
    }
}