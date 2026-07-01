<?php

namespace App\Http\Controllers; 

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Modules\Auth\Entities\Usuario; 

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'correo_electronico' => 'required|email',
            'password' => 'required'
        ]);

        $usuario = Usuario::where('correo_electronico', $request->correo_electronico)->first();

        if (!$usuario || !Hash::check($request->password, $usuario->password_hash)) {
            return response()->json(['message' => 'Credenciales incorrectas'], 401);
        }

        if (!$usuario->activo) {
            return response()->json(['message' => 'Cuenta inactiva. Contacta al administrador.'], 403);
        }

        
        $token = Str::random(60);

        
        Cache::store('redis')->put('auth_token:' . $token, $usuario->id, now()->addMinutes(120));

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'usuario' => $usuario 
        ], 200);
    }

    public function logout(Request $request)
    {
        $token = $request->bearerToken();
        
        if ($token) {
            
            Cache::store('redis')->forget('auth_token:' . $token);
        }

        return response()->json([
            'message' => 'Sesión cerrada exitosamente en Redis. Token destruido.'
        ], 200);
    }
}