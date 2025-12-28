<?php

namespace App\View\Composers;

use App\Models\Order; 

use Illuminate\View\View;
use Illuminate\Support\Facades\Auth; 

class PendingSalesOrderComposer
{
    public function compose(View $view): void
    {
        $pendingSalesOrderCount = 0; 

        if (Auth::check()) {
            $pendingSalesOrderCount = Order::where('order_source', 'sales') 
                                           ->where('status', 'pending')   
                                           ->count();
        }

        $view->with('pendingSalesOrderCount', $pendingSalesOrderCount);
    }
}