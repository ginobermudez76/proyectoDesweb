<?php

namespace App\Modules\Incidencias\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Incidencias\Entities\Evidencia;
use App\Modules\Incidencias\Entities\Incidencia;
use App\Services\FirebaseStorageService;
use Illuminate\Http\Request;

class EvidenciaController extends Controller
{
    protected $firebaseStorage;

    public function __construct(FirebaseStorageService $firebaseStorage)
    {
        $this->firebaseStorage = $firebaseStorage;
    }

    public function store(Request $request, $id)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120',
        ]);

        $incidencia = Incidencia::findOrFail($id);
        $rol = $request->user()->roles->first()->codigo ?? 'CIUDADANO';
        if ($rol === 'CIUDADANO' && $incidencia->usuario_id !== $request->user()->uuid) {
            return response()->json(['message' => 'Acceso denegado. No eres el propietario de esta incidencia.'], 403);
        }

        $file = $request->file('archivo');

        // Subir a Firebase Storage (o fallback a local)
        $path = $this->firebaseStorage->upload($file, 'evidencias');

        $evidencia = Evidencia::create([
            'incidencia_id' => $id,
            'usuario_id' => $request->user()->uuid,
            'nombre_archivo' => $file->getClientOriginalName(),
            'ruta' => $path,
            'tipo_mime' => $file->getClientMimeType(),
            'tamano' => $file->getSize(),
            'fecha_subida' => now(),
        ]);

        return response()->json([
            'message' => 'Evidencia subida correctamente',
            'data' => $evidencia,
        ], 201);
    }
}
