<?php

namespace App\Http\Controllers;

use App\Modules\Auth\Entities\HistorialSesion;
use App\Modules\Auth\Entities\IntentoLoginFallido;
use App\Modules\Auth\Entities\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    
    private const MAX_FAILS = 5;

    
    private const LOCKOUT_TTL = 300; 

    public function login(Request $request)
    {
        $request->validate([
            'correo_electronico' => 'required|string',
            'password'           => 'required',
        ]);

        $ip    = $request->ip();
        $loginInput = strtolower($request->correo_electronico);

        $keyIp    = "login_fails:ip:{$ip}";
        $keyEmail = "login_fails:input:{$loginInput}";

        
        $failsIp    = (int) Cache::store('redis')->get($keyIp, 0);
        $failsEmail = (int) Cache::store('redis')->get($keyEmail, 0);

        if ($failsIp >= self::MAX_FAILS || $failsEmail >= self::MAX_FAILS) {
            
            $prefix    = config('cache.prefix', '');
            $blockedKey = $failsIp >= self::MAX_FAILS ? $keyIp : $keyEmail;
            $ttl        = Cache::store('redis')->getRedis()->ttl($prefix.$blockedKey);
            $ttl        = ($ttl && $ttl > 0) ? $ttl : self::LOCKOUT_TTL;

            return response()->json([
                'message'             => 'Cuenta bloqueada por múltiples intentos fallidos. '
                                         .'Intenta de nuevo en '.ceil($ttl / 60).' minuto(s).',
                'retry_after_seconds' => $ttl,
            ], 429);
        }

        
        $usuario = Usuario::where('correo_electronico', $loginInput)
            ->orWhere('nombre_usuario', $loginInput)
            ->first();

        if (!$usuario || !Hash::check($request->password, $usuario->password_hash)) {

            
            $numIp    = (int) Cache::store('redis')->get($keyIp, 0) + 1;
            $numEmail = (int) Cache::store('redis')->get($keyEmail, 0) + 1;
            Cache::store('redis')->put($keyIp,    $numIp,    self::LOCKOUT_TTL);
            Cache::store('redis')->put($keyEmail, $numEmail, self::LOCKOUT_TTL);

            $intento   = max($numIp, $numEmail);
            $bloqueado = $intento >= self::MAX_FAILS;
            $remaining = max(0, self::MAX_FAILS - $intento);

          
            IntentoLoginFallido::create([
                'correo_electronico' => $loginInput,
                'ip'                 => $ip,
                'user_agent'         => $request->userAgent(),
                'intento_numero'     => $intento,
                'bloqueado'          => $bloqueado,
                'fecha_hora'         => now(),
            ]);

            return response()->json([
                'message'             => $bloqueado
                    ? 'Cuenta bloqueada. Intenta de nuevo en '.self::LOCKOUT_TTL / 60 .' minuto(s).'
                    : "Credenciales incorrectas. Te quedan {$remaining} intento(s) antes del bloqueo.",
                'attempts_remaining'  => $remaining,
                'retry_after_seconds' => $bloqueado ? self::LOCKOUT_TTL : null,
            ], 401);
        }

        
        if (!$usuario->activo) {
            return response()->json(['message' => 'Cuenta inactiva o pendiente de verificación. Por favor, revisa tu correo o contacta al administrador.'], 403);
        }

        
        Cache::store('redis')->forget($keyIp);
        Cache::store('redis')->forget($keyEmail);

        $token = Str::random(60);
        Cache::store('redis')->put('auth_token:'.$token, $usuario->id, now()->addDays(7));

        HistorialSesion::create([
            'usuario_id'         => $usuario->uuid,
            'correo_electronico' => $usuario->correo_electronico,
            'accion'             => 'LOGIN',
            'ip'                 => $ip,
            'dispositivo'        => $request->userAgent(),
            'fecha_hora'         => now(),
        ]);

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'usuario'      => $usuario->load('roles.opciones'),
        ], 200);
    }

    public function logout(Request $request)
    {
        $token = $request->bearerToken();

        if ($token) {
            $usuarioId = Cache::store('redis')->get('auth_token:'.$token);

            if ($usuarioId) {
                $usuario = Usuario::find($usuarioId);

                HistorialSesion::create([
                    'usuario_id'         => $usuario ? $usuario->uuid : $usuarioId,
                    'correo_electronico' => $usuario ? $usuario->correo_electronico : 'Desconocido',
                    'accion'             => 'LOGOUT',
                    'ip'                 => $request->ip(),
                    'dispositivo'        => $request->userAgent(),
                    'fecha_hora'         => now(),
                ]);

                
                Cache::store('redis')->forget('user_profile:'.$usuarioId);
            }

            Cache::store('redis')->forget('auth_token:'.$token);
        }

        return response()->json([
            'message' => 'Sesión cerrada exitosamente en Redis. Token destruido.',
        ], 200);
    }

    public function logUnauthorizedAccess(Request $request)
    {
        $request->validate([
            'tipo_violacion' => 'required|string',
            'url'            => 'required|string',
            'detalle'        => 'required|string',
            'metodo'         => 'nullable|string',
        ]);

        $token = $request->bearerToken();
        $usuario = null;

        if ($token) {
            $userId = Cache::store('redis')->get('auth_token:'.$token);
            if ($userId) {
                $usuario = Usuario::find($userId);
            }
        }

        \App\Modules\Auth\Entities\AccesoNoAutorizado::create([
            'usuario_uuid'       => $usuario?->uuid,
            'correo_electronico' => $usuario?->correo_electronico,
            'rol'                => $usuario?->roles->first()?->codigo ?? 'SIN_AUTENTICAR',
            'ip'                 => $request->ip(),
            'user_agent'         => $request->userAgent(),
            'metodo'             => $request->input('metodo', 'GET'),
            'url'                => $request->input('url'),
            'tipo_violacion'     => $request->input('tipo_violacion'),
            'detalle'            => $request->input('detalle'),
            'fecha_hora'         => now(),
        ]);

        return response()->json(['message' => 'Acceso no autorizado registrado en MongoDB.'], 201);
    }

    public function tiposDocumento()
    {
        $tipos = \App\Modules\Auth\Entities\TipoDocumento::all();
        return response()->json($tipos, 200);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'nombres'            => 'required|string|max:50',
            'apellidos'          => 'required|string|max:50',
            'correo_electronico' => 'required|email|max:255|unique:usuario,correo_electronico',
            'nombre_usuario'     => 'required|string|max:50|unique:usuario,nombre_usuario',
            'password'           => 'required|string|min:8',
            'id_tipo_documento'  => 'required|integer|exists:tipo_documento,id',
            'documento'          => 'required|string|max:50',
            'celular'            => 'required|string|max:30',
            
            'prefijo_celular'    => 'required|string|max:10',
            'codigo_pais'        => 'required|string|max:10',
            'ubicacion'          => 'required|array',
        ]);

        
        $tipoDoc = \App\Modules\Auth\Entities\TipoDocumento::findOrFail($validated['id_tipo_documento']);
        $reglas  = $tipoDoc->validacion;
        if (!empty($reglas['regex'])) {
            if (!preg_match('/' . $reglas['regex'] . '/', $validated['documento'])) {
                return response()->json([
                    'message' => $reglas['error_msg'] ?? 'El formato del documento es inválido.'
                ], 422);
            }
        }

       
        $usuario = new Usuario();
        $usuario->nombres            = $validated['nombres'];
        $usuario->apellidos          = $validated['apellidos'];
        $usuario->correo_electronico = strtolower($validated['correo_electronico']);
        $usuario->nombre_usuario     = strtolower($validated['nombre_usuario']);
        $usuario->password_hash      = Hash::make($validated['password']);
        $usuario->id_tipo_documento  = $validated['id_tipo_documento'];
        $usuario->documento          = $validated['documento'];
        $usuario->celular            = $validated['celular'];
        
     
        $usuario->activo             = false; 
        $usuario->deleted            = false;
        $usuario->save();

        
        $rol = \App\Modules\Auth\Entities\Rol::where('codigo', 'CIUDADANO')->firstOrFail();
        $usuario->roles()->attach($rol->id, [
            'deleted'    => false,
            'created_at' => now(),
        ]);

       
        \App\Modules\Auth\Entities\CiudadanoPerfil::create([
            'usuario_uuid'    => $usuario->uuid,
            'prefijo_celular' => $validated['prefijo_celular'],
            'codigo_pais'     => $validated['codigo_pais'],
            'ubicacion'       => $validated['ubicacion'],
        ]);

        
        $codigoVerificacion = random_int(100000, 999999);

        
        Cache::store('redis')->put('codigo_verificacion:' . $usuario->correo_electronico, $codigoVerificacion, now()->addMinutes(15));

        
        Mail::raw("Bienvenido al Sistema de Incidencias. Tu código de verificación de 6 dígitos es: {$codigoVerificacion}\n\nEste código expirará en 15 minutos.", function ($message) use ($usuario) {
            $message->to($usuario->correo_electronico)
                    ->subject('Código de Verificación - Sistema de Incidencias');
        });

        
        return response()->json([
            'message'              => 'Usuario registrado. Por favor, revisa tu correo electrónico para verificar tu cuenta.',
            'require_verification' => true,
            'correo'               => $usuario->correo_electronico
        ], 201);
    }

    public function paises()
    {
        $paises = Cache::store('redis')->remember('ubicaciones:paises', now()->addDays(7), function () {
            $response = \Illuminate\Support\Facades\Http::get('https://countriesnow.space/api/v0.1/countries/info?returns=dialCode,unicodeFlag');
            if ($response->failed()) {
                return [];
            }
            $data = $response->json()['data'] ?? [];
            
            $list = [];
            foreach ($data as $c) {
                if (!empty($c['name'])) {
                    $list[] = [
                        'name' => $c['name'],
                        'dial_code' => $c['dialCode'] ?? '',
                        'flag' => $c['unicodeFlag'] ?? '',
                    ];
                }
            }
            
            usort($list, function ($a, $b) {
                return strcasecmp($a['name'], $b['name']);
            });
            
            return $list;
        });

        return response()->json($paises, 200);
    }

    public function estados(Request $request)
    {
        $request->validate([
            'country' => 'required|string',
        ]);

        $country = $request->input('country');
        $cacheKey = 'ubicaciones:estados:' . md5($country);

        $estados = Cache::store('redis')->remember($cacheKey, now()->addDays(7), function () use ($country) {
            $response = \Illuminate\Support\Facades\Http::post('https://countriesnow.space/api/v0.1/countries/states', [
                'country' => $country
            ]);
            if ($response->failed()) {
                return [];
            }
            $states = $response->json()['data']['states'] ?? [];
            
            $list = [];
            foreach ($states as $s) {
                if (!empty($s['name'])) {
                    $list[] = $s['name'];
                }
            }
            
            natcasesort($list);
            return array_values($list);
        });

        return response()->json($estados, 200);
    }

    public function ciudades(Request $request)
    {
        $request->validate([
            'country' => 'required|string',
            'state'   => 'required|string',
        ]);

        $country = $request->input('country');
        $state   = $request->input('state');
        $cacheKey = 'ubicaciones:ciudades:' . md5($country . '_' . $state);

        $ciudades = Cache::store('redis')->remember($cacheKey, now()->addDays(7), function () use ($country, $state) {
            $response = \Illuminate\Support\Facades\Http::post('https://countriesnow.space/api/v0.1/countries/state/cities', [
                'country' => $country,
                'state'   => $state
            ]);
            if ($response->failed()) {
                return [];
            }
            $cities = $response->json()['data'] ?? [];
            
            natcasesort($cities);
            return array_values($cities);
        });

        return response()->json($ciudades, 200);
    }

    
    public function verificarCodigo(Request $request)
    {
        $request->validate([
            'correo_electronico' => 'required|email',
            'codigo'             => 'required|numeric'
        ]);

        $correo = strtolower($request->correo_electronico);
        $codigoIngresado = $request->codigo;
        $key = 'codigo_verificacion:' . $correo;

       
        $codigoGuardado = Cache::store('redis')->get($key);

        if (!$codigoGuardado || $codigoGuardado != $codigoIngresado) {
            return response()->json([
                'message' => 'Código inválido o ha expirado. Por favor, regístrate nuevamente o solicita un nuevo código.'
            ], 400);
        }

        
        $usuario = Usuario::where('correo_electronico', $correo)->first();
        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        $usuario->activo = true;
        $usuario->save();

       
        Cache::store('redis')->forget($key);

        
        $token = Str::random(60);
        Cache::store('redis')->put('auth_token:'.$token, $usuario->id, now()->addDays(7));

        HistorialSesion::create([
            'usuario_id'         => $usuario->uuid,
            'correo_electronico' => $usuario->correo_electronico,
            'accion'             => 'VERIFICACION_Y_LOGIN',
            'ip'                 => $request->ip(),
            'dispositivo'        => $request->userAgent(),
            'fecha_hora'         => now(),
        ]);

        return response()->json([
            'message'      => 'Cuenta verificada y activada exitosamente.',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'usuario'      => $usuario->load('roles.opciones'),
        ], 200);
    }
}