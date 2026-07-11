<?php

namespace App\Modules\Auth\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

use App\Modules\Auth\Entities\Usuario;
use App\Modules\Auth\Entities\Rol;
use App\Modules\Auth\Observers\UsuarioObserver;
use App\Modules\Auth\Observers\RolObserver;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Registrar bindings de repositorios y servicios
    }

    public function boot(): void
    {
        $this->registerRoutes();


        Usuario::observe(UsuarioObserver::class);
        Rol::observe(RolObserver::class);
    }

    protected function registerRoutes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__ . '/../Routes/api.php');
    }
}