<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

if (!function_exists('setting')) {
    /**
     * Mengambil nilai dari tabel settings.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function setting($key, $default = null)
    {
        return Cache::rememberForever('settings.' . $key, function () use ($key, $default) {
            $setting = Setting::find($key);
            return $setting ? $setting->value : $default;
        });
    }
}