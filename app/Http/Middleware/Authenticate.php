<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request; 

class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        if (! $request->expectsJson()) {
            if ($request->routeIs('client.*')) {
                return route('client.login');
            }
            return route('admin.login');
        }
        return null;
    }
}