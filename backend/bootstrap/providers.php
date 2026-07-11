<?php

use App\Modules\Auth\Providers\AuthServiceProvider;
use App\Modules\Incidencias\Providers\IncidenciasServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    IncidenciasServiceProvider::class,
];
