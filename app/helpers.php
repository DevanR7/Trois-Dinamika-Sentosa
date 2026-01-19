<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

if (!function_exists('setting')) {
    function setting($key, $default = null)
    {
        // Ambil semua setting yang sudah di-cache dalam 1 array
        $settings = \App\Models\Setting::getAllSettings(); 
        return $settings[$key] ?? $default;
    }
}