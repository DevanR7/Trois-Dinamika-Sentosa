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
        Paginator::useBootstrapFive();
        // Mendaftarkan tipe ENUM ke Doctrine...
        if (!Type::hasType('enum')) {
            Type::addType('enum', 'Doctrine\DBAL\Types\StringType');
            DB::connection()->getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
        }
        // --- AKHIR KODE TAMBAHAN ---

        // Daftarkan Composer Anda
        View::composer('layouts.client', AnnouncementComposer::class);
        View::composer('layouts.partials.sidebar-links', PendingSalesOrderComposer::class);

        // ===============================================
        // ✅ TAMBAHKAN BLOK KODE INI
        // ===============================================
        try {
            // Cek apakah tabel 'settings' ada sebelum query (penting saat migrasi)
            if (Schema::hasTable('settings')) {
                // Ambil setting 'system_version', jika tidak ada, default ke '1.0.0'
                $systemVersion = Setting::find('system_version')?->value ?? '1.0.0';
                View::share('systemVersion', $systemVersion);
            } else {
                // Fallback jika tabel belum ada
                View::share('systemVersion', '1.0.0');
            }
        } catch (\Exception $e) {
            // Tangani error jika DB belum siap (misal: saat migrasi awal)
            View::share('systemVersion', '1.0.0');
        }
        // ===============================================
    }
}