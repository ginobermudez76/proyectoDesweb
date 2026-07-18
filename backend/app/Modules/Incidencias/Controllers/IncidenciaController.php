<?php

namespace App\Modules\Incidencias\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Entities\AccesoNoAutorizado;
use App\Modules\Incidencias\Entities\Comentario;
use App\Modules\Incidencias\Entities\Evidencia;
use App\Modules\Incidencias\Entities\Incidencia;
use App\Modules\Incidencias\Entities\Seguimiento;
use Illuminate\Http\Request;

class IncidenciaController extends Controller
{
    private const NOT_OWNER_MSG = 'Acceso denegado. No eres el propietario de esta incidencia.';

    /**
     * Registers an unauthorized access attempt and returns a 403 JSON response.
     */
    private function denyOwnership(Request $request, string $detail, string $incidenciaId): \Illuminate\Http\JsonResponse
    {
        $rol = $request->user()->roles->first()?->codigo ?? 'CIUDADANO';
        AccesoNoAutorizado::create([
            'usuario_uuid'       => $request->user()->uuid,
            'correo_electronico' => $request->user()->correo_electronico,
            'rol'                => $rol,
            'ip'                 => $request->ip(),
            'user_agent'         => $request->userAgent(),
            'metodo'             => $request->method(),
            'url'                => $request->path(),
            'tipo_violacion'     => 'IDOR',
            'detalle'            => $detail . ' ID objetivo: ' . $incidenciaId,
            'fecha_hora'         => now(),
        ]);
        return response()->json(['message' => self::NOT_OWNER_MSG], 403);
    }

    public function index(Request $request)
    {
        $rol = $request->user()->roles->first()->codigo ?? 'CIUDADANO';

        if ($rol === 'CIUDADANO') {
            return response()->json(Incidencia::where('usuario_id', $request->user()->uuid)->get(), 200);
        }

        if ($rol === 'TECNICO') {
            return response()->json(Incidencia::where('asignado_a', $request->user()->uuid)->get(), 200);
        }

        return response()->json(Incidencia::all(), 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'prioridad' => 'required|string',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $incidencia = new Incidencia;
        $incidencia->titulo = $validated['titulo'];
        $incidencia->descripcion = $validated['descripcion'];
        $incidencia->prioridad = $validated['prioridad'];
        $incidencia->estado = 'Pendiente';
        $incidencia->usuario_id = $request->user()->uuid;
        $incidencia->fecha_creacion = now();

        $incidencia->ubicacion = [
            'type' => 'Point',
            'coordinates' => [(float) $validated['lng'], (float) $validated['lat']],
        ];

        $incidencia->save();

        return response()->json($incidencia, 201);
    }

    public function show(Request $request, $id)
    {
        /** @var Incidencia $incidencia */
        $incidencia = Incidencia::findOrFail($id);
        $rol = $request->user()->roles->first()->codigo ?? 'CIUDADANO';

        if ($rol === 'CIUDADANO' && $incidencia->usuario_id !== $request->user()->uuid) {
            return $this->denyOwnership($request, 'Intento de lectura en incidencia ajena.', $incidencia->_id);
        }

        $evidencias = Evidencia::where('incidencia_id', $id)->get();
        $historial_seguimiento = Seguimiento::where('incidencia_id', $id)
            ->orderBy('fecha_cambio', 'desc')
            ->get();

        foreach ($historial_seguimiento as $h) {
            if (!empty($h->tecnico_id)) {
                $usr = \App\Modules\Auth\Entities\Usuario::where('uuid', $h->tecnico_id)->first();
                if ($usr) {
                    $h->setAttribute('tecnico_nombre', trim($usr->nombres . ' ' . $usr->apellidos));
                }
            }
        }

        $comentarios = Comentario::where('incidencia_id', $id)
            ->orderBy('fecha_creacion', 'asc')
            ->get();

        $tecnicoNombre = null;
        if (!empty($incidencia->asignado_a)) {
            $tecnico = \App\Modules\Auth\Entities\Usuario::where('uuid', $incidencia->asignado_a)->first();
            if ($tecnico) {
                $tecnicoNombre = trim($tecnico->nombres . ' ' . $tecnico->apellidos);
            }
        }
        $incidencia->setAttribute('asignado_a_nombre', $tecnicoNombre);

        return response()->json([
            'incidencia' => $incidencia,
            'asignado_a_nombre' => $tecnicoNombre,
            'evidencias' => $evidencias,
            'historial_seguimiento' => $historial_seguimiento,
            'comentarios' => $comentarios,
        ], 200);
    }

    public function update(Request $request, $id)
    {
        /** @var Incidencia $incidencia */
        $incidencia = Incidencia::findOrFail($id);
        $rol = $request->user()->roles->first()->codigo ?? 'CIUDADANO';

        if ($rol === 'CIUDADANO') {
            if ($incidencia->usuario_id !== $request->user()->uuid) {
                return $this->denyOwnership($request, 'Intento de actualización en incidencia ajena.', $incidencia->_id);
            }
            if ($incidencia->estado !== 'Pendiente') {
                return response()->json(['message' => 'Solo se pueden editar incidencias en estado Pendiente.'], 403);
            }

            $validated = $request->validate([
                'descripcion' => 'required|string',
                'prioridad' => 'required|string|in:Baja,Media,Alta,Urgente',
            ]);

            $incidencia->descripcion = $validated['descripcion'];
            $incidencia->prioridad = $validated['prioridad'];
            $incidencia->save();

            return response()->json($incidencia, 200);
        }

        if ($rol === 'TECNICO') {
            return response()->json(['message' => 'Los técnicos no pueden modificar los detalles del reporte.'], 403);
        }

        // Supervisor o Administrador: pueden actualizar asignado_a, prioridad, descripcion
        $validated = $request->validate([
            'descripcion' => 'nullable|string',
            'prioridad' => 'nullable|string|in:Baja,Media,Alta,Urgente',
            'asignado_a' => 'nullable|string', // UUID del tecnico
        ]);

        $anteriorAsignado = $incidencia->asignado_a;

        if (array_key_exists('descripcion', $validated) && $validated['descripcion'] !== null) {
            $incidencia->descripcion = $validated['descripcion'];
        }
        if (array_key_exists('prioridad', $validated) && $validated['prioridad'] !== null) {
            $incidencia->prioridad = $validated['prioridad'];
        }
        if (array_key_exists('asignado_a', $validated)) {
            $incidencia->asignado_a = $validated['asignado_a'];
        }

        $incidencia->save();

        if ($incidencia->asignado_a !== $anteriorAsignado) {
            $userUuid = $request->user()->uuid;
            // Notificar al técnico asignado (si no es el mismo usuario)
            if ($incidencia->asignado_a && $incidencia->asignado_a !== $userUuid) {
                \App\Modules\Incidencias\Entities\Notificacion::create([
                    'usuario_id' => $incidencia->asignado_a,
                    'incidencia_id' => $incidencia->id,
                    'titulo' => "Nueva incidencia asignada",
                    'mensaje' => "Se te ha asignado la incidencia '{$incidencia->titulo}'.",
                    'leida' => false,
                ]);
            }
            // Notificar al ciudadano creador
            if ($incidencia->usuario_id && $incidencia->usuario_id !== $userUuid) {
                \App\Modules\Incidencias\Entities\Notificacion::create([
                    'usuario_id' => $incidencia->usuario_id,
                    'incidencia_id' => $incidencia->id,
                    'titulo' => "Técnico asignado",
                    'mensaje' => "Se ha asignado un técnico a tu incidencia '{$incidencia->titulo}'.",
                    'leida' => false,
                ]);
            }
        }

        return response()->json($incidencia, 200);
    }

    public function destroy(Request $request, $id)
    {
        /** @var Incidencia $incidencia */
        $incidencia = Incidencia::findOrFail($id);
        $rol = $request->user()->roles->first()->codigo ?? 'CIUDADANO';

        if ($rol === 'CIUDADANO') {
            if ($incidencia->usuario_id !== $request->user()->uuid) {
                return $this->denyOwnership($request, 'Intento de eliminación en incidencia ajena.', $incidencia->_id);
            }
            if ($incidencia->estado !== 'Pendiente') {
                return response()->json(['message' => 'Solo se pueden eliminar incidencias en estado Pendiente.'], 403);
            }
        } elseif ($rol === 'TECNICO') {
            return response()->json(['message' => 'Los técnicos no tienen permitido eliminar incidencias.'], 403);
        }

        $incidencia->delete();

        return response()->json(['message' => 'Incidencia eliminada correctamente'], 200);
    }

    /**
     * GET /api/dashboard/stats
     * Retorna conteos globales para el dashboard de administración/supervisión.
     */
    public function dashboardStats(Request $request)
    {
        $rol = $request->user()->roles->first()->codigo ?? 'CIUDADANO';
        if ($rol !== 'ADMIN' && $rol !== 'SUPERVISOR') {
            return response()->json(['message' => 'Acceso denegado.'], 403);
        }

        $total = Incidencia::count();
        $urgentes = Incidencia::where('prioridad', 'Urgente')->count();
        $resueltas = Incidencia::where('estado', 'Resuelta')->count();
        $pendientes = Incidencia::where('estado', 'Pendiente')->count();
        $proceso = Incidencia::where('estado', 'En Proceso')->count();

        return response()->json([
            'total' => $total,
            'urgentes' => $urgentes,
            'resueltas' => $resueltas,
            'pendientes' => $pendientes,
            'proceso' => $proceso,
        ], 200);
    }
}
