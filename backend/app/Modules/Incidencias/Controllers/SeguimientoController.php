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
        $validated = $request->validate([
            'estado_nuevo' => 'required|string|in:Pendiente,En Proceso,Resuelta,Rechazada',
            'observacion' => 'nullable|string|max:500',
        ]);

        $incidencia = Incidencia::findOrFail($id);

        $estadoAnterior = $incidencia->estado;

        $incidencia->estado = $validated['estado_nuevo'];
        $incidencia->save();

        $seguimiento = Seguimiento::create([
            'incidencia_id' => $id,
            'tecnico_id' => $request->user()->id,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $validated['estado_nuevo'],
            'observacion' => $validated['observacion'] ?? 'Sin observaciones.',
            'fecha_cambio' => now(),
        ]);

        return response()->json([
            'message' => 'Estado de la incidencia actualizado con éxito',
            'seguimiento' => $seguimiento,
        ], 200);
    }
}
