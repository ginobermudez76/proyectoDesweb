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
            'archivo' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240',
            'url' => 'nullable|url',
        ]);

        /** @var Incidencia $incidencia */
        $incidencia = Incidencia::findOrFail($id);
        $rol = $request->user()->roles->first()->codigo ?? 'CIUDADANO';
        if ($rol === 'CIUDADANO' && $incidencia->usuario_id !== $request->user()->uuid) {
            return response()->json(['message' => 'Acceso denegado. No eres el propietario de esta incidencia.'], 403);
        }

        if ($request->has('url') && !empty($request->input('url'))) {
            $path = $request->input('url');
            $fileName = basename(parse_url($path, PHP_URL_PATH) ?: 'archivo.jpg');
            $mimeType = 'image/jpeg';
            $size = 0;
        } else {
            if (!$request->hasFile('archivo')) {
                return response()->json(['message' => 'No se ha enviado ningún archivo o URL.'], 400);
            }
            $file = $request->file('archivo');
            // Subir a Firebase Storage (o fallback a local)
            $path = $this->firebaseStorage->upload($file, 'evidencias');
            $fileName = $file->getClientOriginalName();
            $mimeType = $file->getClientMimeType();
            $size = $file->getSize();
        }

        $evidencia = Evidencia::create([
            'incidencia_id' => $id,
            'usuario_id' => $request->user()->uuid,
            'nombre_archivo' => $fileName,
            'ruta' => $path,
            'tipo_mime' => $mimeType,
            'tamano' => $size,
            'fecha_subida' => now(),
        ]);

        return response()->json([
            'message' => 'Evidencia subida correctamente',
            'data' => $evidencia,
        ], 201);
    }
}
