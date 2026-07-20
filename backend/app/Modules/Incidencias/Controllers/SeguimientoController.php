<?php

namespace App\Modules\Incidencias\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Incidencias\Entities\Incidencia;
use App\Modules\Incidencias\Entities\Seguimiento;
use Illuminate\Http\Request;

class SeguimientoController extends Controller
{
    public function cambiarEstado(Request $request, $id)
    {
        $rol = $request->user()->roles->first()->codigo ?? 'CIUDADANO';
        if ($rol === 'CIUDADANO') {
            return response()->json(['message' => 'Acceso denegado. Los ciudadanos no pueden cambiar el estado de las incidencias.'], 403);
        }

        $validated = $request->validate([
            'estado_nuevo' => 'required|string|in:Pendiente,En Proceso,Resuelta,Rechazada',
            'observacion' => 'nullable|string|max:500',
        ]);

        /** @var Incidencia $incidencia */
        $incidencia = Incidencia::findOrFail($id);

        $estadoAnterior = $incidencia->estado;

        $incidencia->estado = $validated['estado_nuevo'];
        $incidencia->save();

        $seguimiento = Seguimiento::create([
            'incidencia_id' => $id,
            'tecnico_id' => $request->user()->uuid,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $validated['estado_nuevo'],
            'observacion' => $validated['observacion'] ?? 'Sin observaciones.',
            'fecha_cambio' => now(),
        ]);

        // Crear notificaciones en MongoDB
        $userUuid = $request->user()->uuid;
        $titulo = "Estado de incidencia actualizado";
        $mensaje = "La incidencia '{$incidencia->titulo}' cambió a '{$validated['estado_nuevo']}'.";

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
            'message' => 'Estado de la incidencia actualizado con éxito',
            'seguimiento' => $seguimiento,
        ], 200);
    }
}
