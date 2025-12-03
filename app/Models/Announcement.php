<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Announcement extends Model
{
    // ✅ Tambahkan traits ini
    use HasFactory, SoftDeletes;

    // ✅ Definisikan fillable
    protected $fillable = [
        'title',
        'content',
        'type',
        'is_active',
    ];

    // ✅ Definisikan cast (opsional tapi bagus)
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * ✅ Tambahkan relasi ini:
     * Klien yang ditargetkan oleh pengumuman ini (jika type='targeted').
     */
    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'announcement_client', 'announcement_id', 'client_id');
    }
}