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
        // Login: muy restrictivo — solo 10 req/min por IP
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip())
                ->response(fn () => response()->json([
                    'message'             => 'Demasiadas peticiones al login. Espera un momento.',
                    'retry_after_seconds' => 60,
                ], 429));
        });

        // API general: 180 req/min por usuario autenticado o IP
        RateLimiter::for('api', function (Request $request) {
            $token  = $request->bearerToken();
            $userId = $token ? Cache::store('redis')->get('auth_token:'.$token) : null;

            return Limit::perMinute(180)->by($userId ?: $request->ip())
                ->response(fn () => response()->json([
                    'message'             => 'Límite de peticiones alcanzado. Espera un momento.',
                    'retry_after_seconds' => 60,
                ], 429));
        });

        // Escritura: 60 req/min — más restrictivo para POST/PUT/DELETE
        RateLimiter::for('api-write', function (Request $request) {
            $token  = $request->bearerToken();
            $userId = $token ? Cache::store('redis')->get('auth_token:'.$token) : null;

            return Limit::perMinute(60)->by($userId ?: $request->ip())
                ->response(fn () => response()->json([
                    'message'             => 'Límite de escritura alcanzado. Espera un momento.',
                    'retry_after_seconds' => 60,
                ], 429));
        });
    }
}
