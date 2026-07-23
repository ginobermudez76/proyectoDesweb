<?php

namespace App\Modules\Incidencias\Providers;

use App\Modules\Incidencias\Entities\Incidencia;
use App\Modules\Incidencias\Observers\IncidenciaObserver;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class IncidenciasServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Intentionally empty. All module service boot initialization is done in the boot() method.
    }

    public function boot(): void
    {
        $this->registerRoutes();

        Incidencia::observe(IncidenciaObserver::class);
    }

    protected function registerRoutes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/../Routes/api.php');
    }
}
