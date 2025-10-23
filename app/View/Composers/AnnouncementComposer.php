<?php

namespace App\View\Composers; // Pastikan namespace-nya benar

use App\Models\Announcement; 
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth; 

class AnnouncementComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $activeAnnouncements = collect(); // Default: koleksi kosong

        // Cek apakah klien sedang login
        if (Auth::guard('client')->check()) {
            $client = Auth::guard('client')->user(); // Ambil data klien yang login

            // Query untuk mengambil pengumuman yang relevan
            $activeAnnouncements = Announcement::where('is_active', true)
                ->where(function ($query) use ($client) {
                    // Ambil yang tipe 'broadcast'
                    $query->where('type', 'broadcast')
                          // ATAU yang tipe 'targeted' DAN klien ini ada di relasinya
                          ->orWhere(function ($subQuery) use ($client) {
                              $subQuery->where('type', 'targeted')
                                       ->whereHas('clients', function ($clientQuery) use ($client) {
                                           $clientQuery->where('clients.client_id', $client->client_id); 
                                       });
                          });
                })
                ->latest() // Urutkan berdasarkan terbaru
                ->get();
        }

        // Kirim data $activeAnnouncements ke view
        $view->with('activeAnnouncements', $activeAnnouncements);
    }
}