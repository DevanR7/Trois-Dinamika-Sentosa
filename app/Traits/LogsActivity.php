<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    protected static function bootLogsActivity()
    {
        static::created(function ($model) {
            self::logToDb($model, 'created', 'Membuat data baru');
        });

        static::updated(function ($model) {
            if (count($model->getChanges()) > 1) { 
                self::logToDb($model, 'updated', 'Mengubah data', $model->getChanges());
            }
        });

        static::deleted(function ($model) {
            self::logToDb($model, 'deleted', 'Menghapus data');
        });
    }

    protected static function logToDb($model, $action, $description, $changes = null)
    {
        $user = Auth::user();
        $guard = null;
        
        if (Auth::guard('web')->check()) $guard = 'admin';
        elseif (Auth::guard('client')->check()) $guard = 'client';

        AuditLog::create([
            'user_type'    => $guard,
            'user_id'      => $user ? $user->getKey() : null,
            'action'       => $action,
            'subject_type' => get_class($model),
            'subject_id'   => $model->getKey(),
            'description'  => $description,
            'properties'   => $changes,
            'ip_address'   => request()->ip(),
        ]);
    }
}