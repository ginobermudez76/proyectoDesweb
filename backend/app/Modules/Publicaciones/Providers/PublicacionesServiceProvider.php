<?php

namespace App\Modules\Publicaciones\Providers;

use App\Modules\Auth\Observers\EntidadAuditObserver;
use App\Modules\Publicaciones\Entities\Publicacion;
use Illuminate\Support\ServiceProvider;

class PublicacionesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Intentionally empty. Las rutas del módulo ya se cargan desde routes/api.php.
    }

    public function boot(): void
    {
        Publicacion::observe(new EntidadAuditObserver('publicacion'));
    }
}
