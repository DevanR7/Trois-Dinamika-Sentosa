<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Doctrine\DBAL\Types\Type;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use App\View\Composers\AnnouncementComposer;
use App\View\Composers\PendingSalesOrderComposer;
use App\Models\Setting;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 1. Konfigurasi Pagination Custom
        Paginator::defaultView('vendor.pagination.admin');
        Paginator::defaultSimpleView('vendor.pagination.admin');

        // 2. Fix untuk tipe data ENUM (Doctrine DBAL issue pada beberapa versi MySQL/MariaDB)
        if (!Type::hasType('enum')) {
            Type::addType('enum', 'Doctrine\DBAL\Types\StringType');
            DB::connection()
                ->getDoctrineSchemaManager()
                ->getDatabasePlatform()
                ->registerDoctrineTypeMapping('enum', 'string');
        }

        // 3. View Composers (Data Global untuk View tertentu)
        View::composer('layouts.client', AnnouncementComposer::class);
        View::composer('layouts.partials.sidebar-links', PendingSalesOrderComposer::class);

        // 4. Share System Version ke semua View
        // PENTING: Bungkus dengan runningInConsole() agar tidak dijalankan saat artisan command (seperti migrate)
        if (!$this->app->runningInConsole()) {
            try {
                // Pastikan tabel 'settings' sudah ada sebelum query (penting saat fresh deploy)
                if (Schema::hasTable('settings')) {
                    $systemVersion = Setting::find('system_version')?->value ?? '1.0.0';
                    View::share('systemVersion', $systemVersion);
                } else {
                    View::share('systemVersion', '1.0.0');
                }
            } catch (\Exception $e) {
                // Fallback jika koneksi database gagal atau error lainnya
                View::share('systemVersion', '1.0.0');
            }
        }
    }
}