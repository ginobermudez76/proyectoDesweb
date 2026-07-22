<?php

namespace App\Modules\Catalogos\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalogos\Entities\CatalogoEstado;
use App\Modules\Catalogos\Entities\CatalogoPrioridad;
use App\Modules\Catalogos\Entities\CatalogoSubtipoIncidencia;
use App\Modules\Catalogos\Entities\CatalogoTipoIncidencia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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

    /**
     * PUT /api/catalogos/estados/{uuid}
     *
     * Solo permite editar campos de presentación (label, css_class, color_hex, orden, activo).
     * El "codigo" no es editable: es el valor exacto que usan las incidencias en Mongo y el
     * resto del sistema (comparaciones directas como estado === 'Resuelta'); cambiarlo o
     * eliminar el registro rompería el vínculo con incidencias ya existentes.
     */
    public function updateEstado(Request $request, string $uuid): JsonResponse
    {
        $estado = CatalogoEstado::where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'label' => 'required|string|max:100',
            'css_class' => 'nullable|string|max:100',
            'color_hex' => 'nullable|string|max:10',
            'orden' => 'required|integer|min:0|max:255',
            'activo' => 'required|boolean',
        ]);

        $estado->update($validated);

        return response()->json($estado, 200);
    }

    /**
     * PUT /api/catalogos/prioridades/{uuid}
     *
     * Mismas reglas que updateEstado(): "codigo" no editable.
     */
    public function updatePrioridad(Request $request, string $uuid): JsonResponse
    {
        $prioridad = CatalogoPrioridad::where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'label' => 'required|string|max:100',
            'css_class' => 'nullable|string|max:100',
            'color_hex' => 'nullable|string|max:10',
            'orden' => 'required|integer|min:0|max:255',
            'activo' => 'required|boolean',
        ]);

        $prioridad->update($validated);

        return response()->json($prioridad, 200);
    }

    public function storeTipo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => [
                'required', 'string', 'max:100',
                Rule::unique('catalogo_tipo_incidencia', 'nombre')->where(fn ($q) => $q->where('deleted', false)),
            ],
            'icono_clase' => 'nullable|string|max:100',
            'orden' => 'nullable|integer|min:0|max:255',
        ]);

        $tipo = CatalogoTipoIncidencia::create([
            'uuid' => (string) Str::uuid(),
            'nombre' => $validated['nombre'],
            'icono_clase' => $validated['icono_clase'] ?? null,
            'orden' => $validated['orden'] ?? 0,
            'activo' => true,
            'deleted' => false,
        ]);

        return response()->json($tipo, 201);
    }

    public function updateTipo(Request $request, string $uuid): JsonResponse
    {
        $tipo = CatalogoTipoIncidencia::where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'nombre' => [
                'required', 'string', 'max:100',
                Rule::unique('catalogo_tipo_incidencia', 'nombre')->where(fn ($q) => $q->where('deleted', false))->ignore($tipo->id),
            ],
            'icono_clase' => 'nullable|string|max:100',
            'orden' => 'required|integer|min:0|max:255',
            'activo' => 'required|boolean',
        ]);

        $tipo->update($validated);

        return response()->json($tipo, 200);
    }

    public function destroyTipo(string $uuid): JsonResponse
    {
        $tipo = CatalogoTipoIncidencia::where('uuid', $uuid)->firstOrFail();

        if ($tipo->subtipos()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar: este tipo todavía tiene subtipos activos. Elimínalos primero.',
            ], 422);
        }

        $tipo->update(['activo' => false, 'deleted' => true, 'deleted_at' => now()]);

        return response()->json(['message' => 'Tipo de incidencia eliminado correctamente'], 200);
    }

    public function storeSubtipo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_tipo_uuid' => 'required|string|exists:catalogo_tipo_incidencia,uuid',
            'nombre' => 'required|string|max:100',
            'orden' => 'nullable|integer|min:0|max:255',
        ]);

        $tipo = CatalogoTipoIncidencia::where('uuid', $validated['id_tipo_uuid'])->firstOrFail();

        $yaExiste = CatalogoSubtipoIncidencia::where('id_tipo', $tipo->id)
            ->where('nombre', $validated['nombre'])
            ->where('deleted', false)
            ->exists();

        if ($yaExiste) {
            return response()->json([
                'message' => 'Ya existe un subtipo con ese nombre para este tipo de incidencia.',
                'errors' => ['nombre' => ['Ya existe un subtipo con ese nombre para este tipo de incidencia.']],
            ], 422);
        }

        $subtipo = CatalogoSubtipoIncidencia::create([
            'uuid' => (string) Str::uuid(),
            'id_tipo' => $tipo->id,
            'nombre' => $validated['nombre'],
            'orden' => $validated['orden'] ?? 0,
            'activo' => true,
            'deleted' => false,
        ]);

        return response()->json($subtipo, 201);
    }

    public function updateSubtipo(Request $request, string $uuid): JsonResponse
    {
        $subtipo = CatalogoSubtipoIncidencia::where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'orden' => 'required|integer|min:0|max:255',
            'activo' => 'required|boolean',
        ]);

        $yaExiste = CatalogoSubtipoIncidencia::where('id_tipo', $subtipo->id_tipo)
            ->where('nombre', $validated['nombre'])
            ->where('deleted', false)
            ->where('id', '!=', $subtipo->id)
            ->exists();

        if ($yaExiste) {
            return response()->json([
                'message' => 'Ya existe un subtipo con ese nombre para este tipo de incidencia.',
                'errors' => ['nombre' => ['Ya existe un subtipo con ese nombre para este tipo de incidencia.']],
            ], 422);
        }

        $subtipo->update($validated);

        return response()->json($subtipo, 200);
    }

    public function destroySubtipo(string $uuid): JsonResponse
    {
        $subtipo = CatalogoSubtipoIncidencia::where('uuid', $uuid)->firstOrFail();
        $subtipo->update(['activo' => false, 'deleted' => true, 'deleted_at' => now()]);

        return response()->json(['message' => 'Subtipo eliminado correctamente'], 200);
    }
}
