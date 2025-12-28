<?php

namespace App\View\Composers; // Pastikan namespace-nya benar

use App\Models\Announcement; 
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth; 

class AnnouncementComposer
{
    public function compose(View $view): void
    {
        $activeAnnouncements = collect(); 

        if (Auth::guard('client')->check()) {
            $client = Auth::guard('client')->user(); 

            $activeAnnouncements = Announcement::where('is_active', true)
                ->where(function ($query) use ($client) {
                    $query->where('type', 'broadcast')
                          ->orWhere(function ($subQuery) use ($client) {
                              $subQuery->where('type', 'targeted')
                                       ->whereHas('clients', function ($clientQuery) use ($client) {
                                           $clientQuery->where('clients.client_id', $client->client_id); 
                                       });
                          });
                })
                ->latest()
                ->get();
        }

        $view->with('activeAnnouncements', $activeAnnouncements);
    }
}