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
    public function register(): void
    {
        
    }

    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.admin');
        Paginator::defaultSimpleView('vendor.pagination.admin');

        if (!Type::hasType('enum')) {
            Type::addType('enum', 'Doctrine\DBAL\Types\StringType');
            DB::connection()
                ->getDoctrineSchemaManager()
                ->getDatabasePlatform()
                ->registerDoctrineTypeMapping('enum', 'string');
        }

        View::composer('layouts.client', AnnouncementComposer::class);
        View::composer('layouts.partials.sidebar-links', PendingSalesOrderComposer::class);

        try {
            if (Schema::hasTable('settings')) {
                $systemVersion = Setting::find('system_version')?->value ?? '1.0.0';
                View::share('systemVersion', $systemVersion);
            } else {
                View::share('systemVersion', '1.0.0');
            }
        } catch (\Exception $e) {
            View::share('systemVersion', '1.0.0');
        }
    }
}
