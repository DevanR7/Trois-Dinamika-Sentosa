<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany; // <-- Tambahkan ini

class Unit extends Model
{
    use HasFactory;
    
    protected $primaryKey = 'unit_id';

    protected $fillable = [
        'name',
    ];

    /**
     * Definisikan relasi: Satu Unit bisa dimiliki oleh banyak Produk.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    // ✅ TAMBAHKAN FUNGSI INI
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'unit_id', 'unit_id');
    }
}