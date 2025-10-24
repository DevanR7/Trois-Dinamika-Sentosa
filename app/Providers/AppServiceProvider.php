<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Doctrine\DBAL\Types\Type;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use App\View\Composers\AnnouncementComposer;
use App\View\Composers\PendingSalesOrderComposer;

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

        // ✅ 3. Daftarkan Composer di sini
        // Ganti 'layouts.client_app' dengan nama layout utama klien Anda
        View::composer('layouts.client', AnnouncementComposer::class);
        View::composer('layouts.partials.sidebar-links', PendingSalesOrderComposer::class);
    }
}