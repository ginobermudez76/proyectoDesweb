<?php

namespace App\Modules\Incidencias\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Incidencias\StoreMensajeRequest;
use App\Modules\Incidencias\Entities\Mensaje;
use App\Modules\Incidencias\Events\NuevoMensaje;

class MensajeController extends Controller
{
    public function index($incidencia_id)
    {
        $mensajes = Mensaje::where('incidencia_id', $incidencia_id)
            ->orderBy('fecha_envio', 'asc')
            ->get();

        return response()->json($mensajes, 200);
    }

    public function store(StoreMensajeRequest $request, $incidencia_id)
    {
        $usuario = clone $request->user();
        $rol = $usuario->roles->first()->codigo ?? 'CIUDADANO';

        if ($rol === 'CIUDADANO') {
            return response()->json([
                'message' => 'Acceso denegado. Solo el personal técnico puede enviar notificaciones.',
            ], 403);
        }

        $mensaje = Mensaje::create([
            'incidencia_id' => $incidencia_id,
            'emisor_id' => $usuario->id,
            'emisor_nombre' => trim($usuario->nombres.' '.$usuario->apellidos) ?: $usuario->nombre_usuario,
            'rol_emisor' => $rol,
            'contenido' => $request->contenido,
            'leido' => false,
            'fecha_envio' => now(),
        ]);

        // Disparamos el evento a Redis y WebSockets
        broadcast(new NuevoMensaje($mensaje))->toOthers();

        return response()->json([
            'message' => 'Notificación enviada al ciudadano exitosamente.',
            'data' => $mensaje,
        ], 201);
    }
}
