<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class AnnouncementController extends Controller
{
    public function index(): JsonResponse
    {
        $clientId = Auth::guard('client')->id();
        $announcements = Announcement::where('is_active', true)
            ->where(function ($query) use ($clientId) {
                $query->where('type', 'broadcast')
                      ->orWhereHas('clients', function ($q) use ($clientId) {
                          $q->where('clients.client_id', $clientId);
                      });
            })
            ->latest() 
            ->take(5)  
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title ?? 'Informasi Penting',
                    'content' => $item->content,
                    'date' => $item->created_at->diffForHumans(), 
                    'type' => $item->type,
                ];
            });

        return response()->json([
            'count' => $announcements->count(),
            'data' => $announcements
        ]);
    }
}