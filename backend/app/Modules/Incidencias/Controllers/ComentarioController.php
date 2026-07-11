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

        return response()->json([
            'message' => 'Comentario agregado correctamente',
            'comentario' => $comentario,
        ], 201);
    }
}
