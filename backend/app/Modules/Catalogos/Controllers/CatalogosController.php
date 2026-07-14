<?php

namespace App\Modules\Catalogos\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalogos\Entities\CatalogoEstado;
use App\Modules\Catalogos\Entities\CatalogoPrioridad;
use App\Modules\Catalogos\Entities\CatalogoTipoIncidencia;
use Illuminate\Http\JsonResponse;

class CatalogosController extends Controller
{
    /**
     * GET /api/catalogos
     *
     * Devuelve todos los catálogos necesarios para el frontend en un único request.
     * Incluye estados, prioridades, tipos e subtipos de incidencia.
     */
    public function index(): JsonResponse
    {
        $estados = CatalogoEstado::orderBy('orden')->get([
            'uuid', 'codigo', 'label', 'css_class', 'color_hex', 'orden',
        ]);

        $prioridades = CatalogoPrioridad::orderBy('orden')->get([
            'uuid', 'codigo', 'label', 'css_class', 'color_hex', 'orden',
        ]);

        // Tipos con sus subtipos anidados
        $tipos = CatalogoTipoIncidencia::with(['subtipos' => function ($q) {
            $q->orderBy('orden')->select(['id', 'uuid', 'id_tipo', 'nombre', 'orden']);
        }])->orderBy('orden')->get([
            'id', 'uuid', 'nombre', 'icono_clase', 'orden',
        ]);

        return response()->json([
            'estados'    => $estados,
            'prioridades' => $prioridades,
            'tipos'      => $tipos,
        ], 200);
    }
}
