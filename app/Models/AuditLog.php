<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';
    
    public $timestamps = true; 

    protected $fillable = [
        'user_type',
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'properties', 
        'ip_address',
    ];

    protected $casts = [
        'properties' => 'array', 
    ];

    public function user()
    {
        if ($this->user_type === 'client') {
            return $this->belongsTo(Client::class, 'user_id', 'client_id');
        }
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function subject()
    {
        return $this->morphTo();
    }
}