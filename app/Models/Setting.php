<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $primaryKey = 'key'; 
    public $incrementing = false; 
    protected $keyType = 'string'; 
    protected $fillable = ['key', 'value'];

    public static function getAllSettings(): array
    {
        return Cache::remember('app_settings', 60 * 60, function () {
            return self::all()->pluck('value', 'key')->toArray();
        });
    }

    public static function getValue(string $key, $default = null)
    {
        $settings = self::getAllSettings();
        return $settings[$key] ?? $default;
    }

    protected static function booted()
    {
        static::saved(function () {
            Cache::forget('app_settings');
        });

        static::deleted(function () {
            Cache::forget('app_settings');
        });
    }
}