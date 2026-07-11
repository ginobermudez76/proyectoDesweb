<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $token = $request->bearerToken();
            $userId = null;
            if ($token) {
                $userId = Cache::store('redis')->get('auth_token:'.$token);
            }

            // Límite de 60 peticiones por minuto por usuario o dirección IP
            return Limit::perMinute(60)->by($userId ?: $request->ip());
        });
    }
}
