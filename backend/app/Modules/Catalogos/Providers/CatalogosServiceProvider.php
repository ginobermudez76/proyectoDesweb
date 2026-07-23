<?php

namespace App\Modules\Catalogos\Providers;

use App\Modules\Auth\Observers\EntidadAuditObserver;
use App\Modules\Catalogos\Entities\CatalogoEstado;
use App\Modules\Catalogos\Entities\CatalogoPrioridad;
use App\Modules\Catalogos\Entities\CatalogoSubtipoIncidencia;
use App\Modules\Catalogos\Entities\CatalogoTipoIncidencia;
use Illuminate\Support\ServiceProvider;

class CatalogosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Intentionally empty. Las rutas del módulo ya se cargan desde routes/api.php.
    }

    public function boot(): void
    {
        CatalogoEstado::observe(new EntidadAuditObserver('catalogo_estado'));
        CatalogoPrioridad::observe(new EntidadAuditObserver('catalogo_prioridad'));
        CatalogoTipoIncidencia::observe(new EntidadAuditObserver('catalogo_tipo_incidencia'));
        CatalogoSubtipoIncidencia::observe(new EntidadAuditObserver('catalogo_subtipo_incidencia'));
    }
}
