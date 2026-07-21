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
    private const ROLE_CIUDADANO = 'CIUDADANO';
    private const ROLE_TECNICO = 'TECNICO';
    private const ROLE_ADMIN = 'ADMIN';
    private const ROLE_SUPERVISOR = 'SUPERVISOR';
    private const ESTADO_PENDIENTE = 'Pendiente';

    /**
     * Registers an unauthorized access attempt and returns a 403 JSON response.
     */
    private function denyOwnership(Request $request, string $detail, string $incidenciaId): \Illuminate\Http\JsonResponse
    {
        $rol = $this->currentRole($request);
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

    private function currentRole(Request $request): string
    {
        return $request->user()->roles->first()?->codigo ?? self::ROLE_CIUDADANO;
    }

    private function jsonError(string $message, int $status = 403): \Illuminate\Http\JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }

    private function updateCitizenIncidencia(Request $request, Incidencia $incidencia)
    {
        if ($incidencia->usuario_id !== $request->user()->uuid) {
            return $this->denyOwnership($request, 'Intento de actualización en incidencia ajena.', $incidencia->_id);
        }

        if ($incidencia->estado !== self::ESTADO_PENDIENTE) {
            return $this->jsonError('Solo se pueden editar incidencias en estado Pendiente.');
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

    private function updateSupervisorIncidencia(Request $request, Incidencia $incidencia)
    {
        $validated = $request->validate([
            'descripcion' => 'nullable|string',
            'prioridad' => 'nullable|string|in:Baja,Media,Alta,Urgente',
            'asignado_a' => 'nullable|string',
        ]);

        $anteriorAsignado = $incidencia->asignado_a;

        foreach (['descripcion', 'prioridad', 'asignado_a'] as $campo) {
            if (!array_key_exists($campo, $validated) || $validated[$campo] === null) {
                continue;
            }

            $incidencia->{$campo} = $validated[$campo];
        }

        $incidencia->save();
        $this->notifyAssignmentChanges($request, $incidencia, $anteriorAsignado);

        return response()->json($incidencia, 200);
    }

    private function notifyAssignmentChanges(Request $request, Incidencia $incidencia, ?string $anteriorAsignado): void
    {
        if ($incidencia->asignado_a === $anteriorAsignado) {
            return;
        }

        $userUuid = $request->user()->uuid;

        if ($incidencia->asignado_a && $incidencia->asignado_a !== $userUuid) {
            \App\Modules\Incidencias\Entities\Notificacion::create([
                'usuario_id' => $incidencia->asignado_a,
                'incidencia_id' => $incidencia->id,
                'titulo' => 'Nueva incidencia asignada',
                'mensaje' => "Se te ha asignado la incidencia '{$incidencia->titulo}'.",
                'leida' => false,
            ]);
        }

        if ($incidencia->usuario_id && $incidencia->usuario_id !== $userUuid) {
            \App\Modules\Incidencias\Entities\Notificacion::create([
                'usuario_id' => $incidencia->usuario_id,
                'incidencia_id' => $incidencia->id,
                'titulo' => 'Técnico asignado',
                'mensaje' => "Se ha asignado un técnico a tu incidencia '{$incidencia->titulo}'.",
                'leida' => false,
            ]);
        }
    }

    private function deleteCitizenIncidencia(Request $request, Incidencia $incidencia)
    {
        if ($incidencia->usuario_id !== $request->user()->uuid) {
            return $this->denyOwnership($request, 'Intento de eliminación en incidencia ajena.', $incidencia->_id);
        }

        if ($incidencia->estado !== self::ESTADO_PENDIENTE) {
            return $this->jsonError('Solo se pueden eliminar incidencias en estado Pendiente.');
        }

        $incidencia->delete();

        return response()->json(['message' => 'Incidencia eliminada correctamente'], 200);
    }

    public function index(Request $request)
    {
        $rol = $this->currentRole($request);

        if ($rol === self::ROLE_CIUDADANO) {
            return response()->json(Incidencia::where('usuario_id', $request->user()->uuid)->get(), 200);
        }

        if ($rol === self::ROLE_TECNICO) {
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
        $rol = $this->currentRole($request);

        if ($rol === self::ROLE_CIUDADANO && $incidencia->usuario_id !== $request->user()->uuid) {
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
        $rol = $this->currentRole($request);

        if ($rol === self::ROLE_CIUDADANO) {
            return $this->updateCitizenIncidencia($request, $incidencia);
        }

        if ($rol === self::ROLE_TECNICO) {
            return $this->jsonError('Los técnicos no pueden modificar los detalles del reporte.');
        }

        return $this->updateSupervisorIncidencia($request, $incidencia);
    }

    public function destroy(Request $request, $id)
    {
        /** @var Incidencia $incidencia */
        $incidencia = Incidencia::findOrFail($id);
        $rol = $this->currentRole($request);

        if ($rol === self::ROLE_CIUDADANO) {
            return $this->deleteCitizenIncidencia($request, $incidencia);
        }

        if ($rol === self::ROLE_TECNICO) {
            return $this->jsonError('Los técnicos no tienen permitido eliminar incidencias.');
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
        $rol = $this->currentRole($request);
        if ($rol !== self::ROLE_ADMIN && $rol !== self::ROLE_SUPERVISOR && $rol !== self::ROLE_CIUDADANO) {
            return $this->jsonError('Acceso denegado.');
        }

        // El ciudadano solo ve estadísticas de sus propias incidencias
        // (usuario_id en Incidencia guarda el UUID, igual que en index()/store())
        $usuarioUuid = $rol === self::ROLE_CIUDADANO ? $request->user()->uuid : null;

        $base = fn () => $usuarioUuid ? Incidencia::where('usuario_id', $usuarioUuid) : Incidencia::query();

        $total = $base()->count();
        $urgentes = $base()->where('prioridad', 'Urgente')->count();
        $resueltas = $base()->where('estado', 'Resuelta')->count();
        $pendientes = $base()->where('estado', 'Pendiente')->count();
        $proceso = $base()->where('estado', 'En Proceso')->count();

        return response()->json([
            'total' => $total,
            'urgentes' => $urgentes,
            'resueltas' => $resueltas,
            'pendientes' => $pendientes,
            'proceso' => $proceso,
            'recientes' => $this->incidenciasRecientes($usuarioUuid),
            'porSemana' => $this->incidenciasPorSemana($usuarioUuid),
            'actividad' => $this->actividadReciente($usuarioUuid),
        ], 200);
    }

    /**
     * Últimas 5 incidencias con el nombre del técnico asignado (si tiene).
     */
    private function incidenciasRecientes(?string $usuarioUuid = null)
    {
        $query = Incidencia::orderBy('fecha_creacion', 'desc');
        if ($usuarioUuid) {
            $query->where('usuario_id', $usuarioUuid);
        }
        $recientes = $query->limit(5)->get();

        foreach ($recientes as $inc) {
            $asignadoNombre = null;
            if (!empty($inc->asignado_a)) {
                $tecnico = \App\Modules\Auth\Entities\Usuario::where('uuid', $inc->asignado_a)->first();
                if ($tecnico) {
                    $asignadoNombre = trim($tecnico->nombres.' '.$tecnico->apellidos);
                }
            }
            $inc->setAttribute('asignado_a_nombre', $asignadoNombre);
        }

        return $recientes;
    }

    /**
     * Conteo de incidencias creadas en los últimos 7 días, agrupado por día (Lun-Dom).
     */
    private function incidenciasPorSemana(?string $usuarioUuid = null)
    {
        $dias = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
        $conteo = array_fill(0, 7, 0);

        $inicioSemana = now()->subDays(6)->startOfDay();
        $query = Incidencia::where('fecha_creacion', '>=', $inicioSemana);
        if ($usuarioUuid) {
            $query->where('usuario_id', $usuarioUuid);
        }
        $query->get(['fecha_creacion'])
            ->each(function ($inc) use (&$conteo) {
                if ($inc->fecha_creacion) {
                    $conteo[$inc->fecha_creacion->dayOfWeekIso - 1]++;
                }
            });

        return collect($dias)->map(fn ($dia, $i) => ['dia' => $dia, 'total' => $conteo[$i]])->values();
    }

    /**
     * Últimos eventos: para admin/supervisor son del sistema completo;
     * para el ciudadano, solo eventos de sus propias incidencias.
     */
    private function actividadReciente(?string $usuarioUuid = null)
    {
        $eventos = collect();

        if ($usuarioUuid) {
            $misIncidenciaIds = Incidencia::where('usuario_id', $usuarioUuid)->pluck('_id');

            Seguimiento::whereIn('incidencia_id', $misIncidenciaIds)
                ->orderBy('fecha_cambio', 'desc')->limit(5)->get()->each(function ($s) use ($eventos) {
                    $texto = $s->estado_nuevo === 'Resuelta'
                        ? 'Tu incidencia fue marcada como resuelta'
                        : "Tu incidencia cambió de estado a '{$s->estado_nuevo}'";
                    $eventos->push(['texto' => $texto, 'fecha' => $s->fecha_cambio]);
                });

            Incidencia::where('usuario_id', $usuarioUuid)
                ->orderBy('fecha_creacion', 'desc')->limit(3)->get()->each(function ($inc) use ($eventos) {
                    $eventos->push(['texto' => 'Reportaste una incidencia', 'fecha' => $inc->fecha_creacion]);
                });

            return $eventos->sortByDesc('fecha')->take(5)->values();
        }

        Seguimiento::orderBy('fecha_cambio', 'desc')->limit(5)->get()->each(function ($s) use ($eventos) {
            $tecnico = \App\Modules\Auth\Entities\Usuario::where('uuid', $s->tecnico_id)->first();
            $nombre = $tecnico ? trim($tecnico->nombres.' '.$tecnico->apellidos) : 'Alguien';
            $texto = $s->estado_nuevo === 'Resuelta'
                ? "{$nombre} resolvió una incidencia"
                : "{$nombre} cambió el estado a '{$s->estado_nuevo}'";
            $eventos->push(['texto' => $texto, 'fecha' => $s->fecha_cambio]);
        });

        Incidencia::orderBy('fecha_creacion', 'desc')->limit(3)->get()->each(function ($inc) use ($eventos) {
            $texto = $inc->prioridad === 'Urgente'
                ? 'Nueva incidencia urgente reportada'
                : 'Nueva incidencia reportada';
            $eventos->push(['texto' => $texto, 'fecha' => $inc->fecha_creacion]);
        });

        \App\Modules\Auth\Entities\Usuario::orderBy('created_at', 'desc')->limit(2)->get()->each(function ($u) use ($eventos) {
            $eventos->push(['texto' => 'Nuevo usuario registrado', 'fecha' => $u->created_at]);
        });

        return $eventos->sortByDesc('fecha')->take(5)->values();
    }

    /**
     * Estadísticas para la tarjeta de perfil del técnico:
     * asignadas hoy, resueltas (histórico) y tiempo promedio de resolución.
     */
    public function perfilStatsTecnico(Request $request)
    {
        $rol = $request->user()->roles->first()->codigo ?? 'CIUDADANO';
        if ($rol !== 'TECNICO') {
            return response()->json(['message' => 'Esta métrica es solo para técnicos.'], 403);
        }

        $uuid = $request->user()->uuid;
        $inicioHoy = now()->startOfDay();

        // Aproximación: "asignadas hoy" son las incidencias asignadas a este
        // técnico cuya fecha de creación es de hoy (no existe un campo de
        // "fecha de asignación" independiente en el modelo actual).
        $asignadasHoy = Incidencia::where('asignado_a', $uuid)
            ->where('fecha_creacion', '>=', $inicioHoy)
            ->count();

        $resueltas = Incidencia::where('asignado_a', $uuid)
            ->where('estado', 'Resuelta')
            ->get(['_id', 'fecha_creacion']);

        $tiempos = [];
        foreach ($resueltas as $inc) {
            $seguimiento = Seguimiento::where('incidencia_id', (string) $inc->_id)
                ->where('estado_nuevo', 'Resuelta')
                ->orderBy('fecha_cambio', 'desc')
                ->first();

            if ($seguimiento && $inc->fecha_creacion) {
                $tiempos[] = $inc->fecha_creacion->diffInMinutes($seguimiento->fecha_cambio);
            }
        }

        $tiempoPromedio = count($tiempos) ? (int) round(array_sum($tiempos) / count($tiempos)) : null;

        return response()->json([
            'asignadas_hoy' => $asignadasHoy,
            'resueltas' => $resueltas->count(),
            'tiempo_promedio_minutos' => $tiempoPromedio,
        ], 200);
    }
}
