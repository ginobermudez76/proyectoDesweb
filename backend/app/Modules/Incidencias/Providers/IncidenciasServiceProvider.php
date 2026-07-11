<?php

namespace App\Modules\Incidencias\Providers; 

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class IncidenciasServiceProvider extends ServiceProvider 
{
    public function register(): void
    {
        // ...
    }

    public function boot(): void
    {
        $this->registerRoutes();
    }

    protected function registerRoutes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/../Routes/api.php');
    }
}