<?php

namespace App\Modules\Incidencias\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Incidencias\Entities\Comentario;
use App\Modules\Incidencias\Entities\Incidencia;
use Illuminate\Http\Request;

class ComentarioController extends Controller
{
    public function store(Request $request, $id)
    {
        $validated = $request->validate([
            'texto' => 'required|string|min:3|max:1000',
        ]);

        /** @var Incidencia $incidencia */
        $incidencia = Incidencia::findOrFail($id);
        $rol = $request->user()->roles->first()->codigo ?? 'CIUDADANO';
        if ($rol === 'CIUDADANO' && $incidencia->usuario_id !== $request->user()->uuid) {
            return response()->json(['message' => 'Acceso denegado. No eres el propietario de esta incidencia.'], 403);
        }

        $comentario = Comentario::create([
            'incidencia_id' => $id,
            'usuario_id' => $request->user()->uuid,
            'texto' => $validated['texto'],
            'fecha_creacion' => now(),
        ]);

        // Crear notificaciones en MongoDB
        $userUuid = $request->user()->uuid;
        $titulo = "Nueva nota agregada";
        $mensaje = "Se agregó una nueva nota a la incidencia '{$incidencia->titulo}': " . substr($validated['texto'], 0, 45) . (strlen($validated['texto']) > 45 ? '...' : '');

        if ($incidencia->usuario_id && $incidencia->usuario_id !== $userUuid) {
            \App\Modules\Incidencias\Entities\Notificacion::create([
                'usuario_id' => $incidencia->usuario_id,
                'incidencia_id' => $incidencia->id,
                'titulo' => $titulo,
                'mensaje' => $mensaje,
                'leida' => false,
            ]);
        }

        if ($incidencia->asignado_a && $incidencia->asignado_a !== $userUuid) {
            \App\Modules\Incidencias\Entities\Notificacion::create([
                'usuario_id' => $incidencia->asignado_a,
                'incidencia_id' => $incidencia->id,
                'titulo' => $titulo,
                'mensaje' => $mensaje,
                'leida' => false,
            ]);
        }

        return response()->json([
            'message' => 'Comentario agregado correctamente',
            'comentario' => $comentario,
        ], 201);
    }
}
