<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseStorageService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class PerfilController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebaseStorageService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,webp|max:20480',
        ], [
            'avatar.required' => 'Debes enviar una imagen.',
            'avatar.image'    => 'El archivo debe ser una imagen válida.',
            'avatar.max'      => 'La imagen es demasiado pesada. El límite es de 20MB.',
        ]);

        try {
            $usuario = $request->user();
            $file = $request->file('avatar');

            $url = $this->firebaseService->upload($file, 'avatars');

            $usuario->foto_perfil = $url; 
            $usuario->save();

            return response()->json([
                'message' => 'Foto de perfil actualizada correctamente',
                'url'     => $url
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al actualizar avatar: ' . $e->getMessage());
            return response()->json(['message' => 'Ocurrió un error interno al procesar la imagen.'], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        $usuario = $request->user();

        
        $request->validate([
            'nombres'            => 'required|string|max:255',
            'apellidos'          => 'required|string|max:255',
            'nombre_usuario'     => 'required|string|max:255|unique:usuario,nombre_usuario,' . $usuario->id,
            'correo_electronico' => 'required|email|max:255|unique:usuario,correo_electronico,' . $usuario->id,
            'celular'            => 'nullable|string|max:50',
            'password'           => 'nullable|string|min:8',
        ], [
            'nombres.required'            => 'El campo nombres es obligatorio.',
            'apellidos.required'          => 'El campo apellidos es obligatorio.',
            'nombre_usuario.required'     => 'El nombre de usuario es obligatorio.',
            'nombre_usuario.unique'       => 'Este nombre de usuario ya está en uso.',
            'correo_electronico.required' => 'El correo electrónico es obligatorio.',
            'correo_electronico.email'    => 'El correo electrónico debe ser válido.',
            'correo_electronico.unique'   => 'Este correo electrónico ya está registrado.',
            'password.min'                => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        try {
            
            $usuario->nombres            = $request->nombres;
            $usuario->apellidos          = $request->apellidos;
            $usuario->nombre_usuario     = $request->nombre_usuario;
            $usuario->correo_electronico = $request->correo_electronico;
            $usuario->celular            = $request->celular;

            // Si el usuario ingresó una nueva contraseña, la ciframos y actualizamos
            if ($request->filled('password')) {
                $usuario->password_hash = Hash::make($request->password);
            }

            

            $usuario->save();

            return response()->json([
                'message' => 'Datos personales actualizados correctamente',
                'user'    => $usuario
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al actualizar perfil: ' . $e->getMessage());
            return response()->json(['message' => 'Ocurrió un error interno al actualizar los datos.'], 500);
        }
    }
}