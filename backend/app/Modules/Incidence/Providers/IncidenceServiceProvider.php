<?php

namespace App\Modules\Incidence\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class IncidenceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Registrar bindings de interfaces a implementaciones aquí
        // $this->app->bind(IncidenceRepositoryInterface::class, IncidenceRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerRoutes();
    }

    /**
     * Registra las rutas del módulo de Incidencias.
     */
    protected function registerRoutes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__ . '/../Routes/api.php');
    }
}
