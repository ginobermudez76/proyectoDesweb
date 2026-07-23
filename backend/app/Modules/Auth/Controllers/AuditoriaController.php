<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Entities\Auditoria;
use Illuminate\Http\Request;

class AuditoriaController extends Controller
{
    public function index(Request $request)
    {
        $query = Auditoria::orderBy('fecha', 'desc');

        if ($request->filled('entidad')) {
            $query->where('entidad', $request->string('entidad'));
        }
        if ($request->filled('accion')) {
            $query->where('accion', $request->string('accion'));
        }
        if ($request->filled('usuario')) {
            $query->where('usuario', 'ilike', '%' . $request->string('usuario') . '%');
        }
        if ($request->filled('desde')) {
            $query->where('fecha', '>=', $request->string('desde'));
        }
        if ($request->filled('hasta')) {
            $query->where('fecha', '<=', $request->string('hasta'));
        }

        return response()->json($query->limit(200)->get(), 200);
    }
}
