<?php

namespace App\Modules\Incidencias\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Incidencias\Entities\Notificacion;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    public function index(Request $request)
    {
        $userUuid = $request->user()->uuid;
        $notificaciones = Notificacion::where('usuario_id', $userUuid)
            ->orderBy('created_at', 'desc')
            ->limit(30)
            ->get();

        return response()->json($notificaciones, 200);
    }

    public function marcarLeida(Request $request, $id)
    {
        $userUuid = $request->user()->uuid;
        $notificacion = Notificacion::where('usuario_id', $userUuid)->findOrFail($id);
        $notificacion->leida = true;
        $notificacion->save();

        return response()->json(['message' => 'Notificación marcada como leída'], 200);
    }

    public function marcarTodasLeidas(Request $request)
    {
        $userUuid = $request->user()->uuid;
        Notificacion::where('usuario_id', $userUuid)
            ->where('leida', false)
            ->update(['leida' => true]);

        return response()->json(['message' => 'Todas las notificaciones marcadas como leídas'], 200);
    }
}
