<?php

namespace App\View\Composers;

namespace App\View\Composers;
use App\Models\Order; // Import model Order
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth; // Import Auth jika perlu filter user

class PendingSalesOrderComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $pendingSalesOrderCount = 0; // Default 0

        // Hanya hitung jika user login (meskipun composer biasanya dipanggil di area login)
        if (Auth::check()) {
            // TODO: Sesuaikan query ini jika hanya role tertentu yang perlu lihat count
            //       Saat ini, semua user admin yang login akan melihat count SEMUA pending sales order.

            $pendingSalesOrderCount = Order::where('order_source', 'sales') // Hanya dari sales
                                           ->where('status', 'pending')   // Hanya yang statusnya pending
                                           ->count();
        }

        // Kirim data $pendingSalesOrderCount ke view
        $view->with('pendingSalesOrderCount', $pendingSalesOrderCount);
    }
}