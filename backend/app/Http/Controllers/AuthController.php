<?php

namespace App\Http\Controllers;

use App\Modules\Auth\Entities\HistorialSesion;
use App\Modules\Auth\Entities\IntentoLoginFallido;
use App\Modules\Auth\Entities\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /** Número máximo de intentos fallidos antes del bloqueo */
    private const MAX_FAILS = 5;

    /** Segundos de bloqueo tras alcanzar MAX_FAILS */
    private const LOCKOUT_TTL = 300; // 5 minutos

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

        // ── 1. Verificar si hay un bloqueo activo ──────────────────────────
        $failsIp    = (int) Cache::store('redis')->get($keyIp, 0);
        $failsEmail = (int) Cache::store('redis')->get($keyEmail, 0);

        if ($failsIp >= self::MAX_FAILS || $failsEmail >= self::MAX_FAILS) {
            // Obtener TTL restante directamente desde la conexión Redis subyacente
            $prefix     = config('cache.prefix', '');
            $blockedKey = $failsIp >= self::MAX_FAILS ? $keyIp : $keyEmail;
            /** @var \Illuminate\Cache\RedisStore $redisStore */
            $redisStore = Cache::store('redis')->getStore();
            /** @var mixed $redisConnection */
            $redisConnection = $redisStore->getRedis();
            $ttl        = $redisConnection->ttl($prefix.$blockedKey);
            $ttl        = ($ttl && $ttl > 0) ? $ttl : self::LOCKOUT_TTL;

            return response()->json([
                'message'             => 'Cuenta bloqueada por múltiples intentos fallidos. '
                                         .'Intenta de nuevo en '.ceil($ttl / 60).' minuto(s).',
                'retry_after_seconds' => $ttl,
            ], 429);
        }

        // ── 2. Validar credenciales (correo_electronico o nombre_usuario) ──
        /** @var Usuario|null $usuario */
        $usuario = Usuario::where('correo_electronico', $loginInput)
            ->orWhere('nombre_usuario', $loginInput)
            ->first();

        if (!$usuario || !Hash::check($request->password, $usuario->password_hash)) {

            // Incrementar contadores usando get+put para mantener TTL de 5 min
            $numIp    = (int) Cache::store('redis')->get($keyIp, 0) + 1;
            $numEmail = (int) Cache::store('redis')->get($keyEmail, 0) + 1;
            Cache::store('redis')->put($keyIp,    $numIp,    self::LOCKOUT_TTL);
            Cache::store('redis')->put($keyEmail, $numEmail, self::LOCKOUT_TTL);

            $intento   = max($numIp, $numEmail);
            $bloqueado = $intento >= self::MAX_FAILS;
            $remaining = max(0, self::MAX_FAILS - $intento);

            // ── Registrar en MongoDB ──────────────────────────────────────
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

        // ── 3. Verificar cuenta activa ─────────────────────────────────────
        if (!$usuario->activo) {
            return response()->json(['message' => 'Cuenta inactiva. Contacta al administrador.'], 403);
        }

        // ── 4. Login exitoso: limpiar contadores de Redis ─────────────────
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

                // Invalidar la caché de permisos de Redis al cerrar sesión
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

            /** @var \App\Modules\Auth\Entities\Rol|null $rol */
            $rol = $usuario?->roles->first();

            \App\Modules\Auth\Entities\AccesoNoAutorizado::create([
                'usuario_uuid'       => $usuario?->uuid,
                'correo_electronico' => $usuario?->correo_electronico,
                'rol'                => $rol ? $rol->codigo : 'SIN_AUTENTICAR',
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
            // Campos de ubicación para MongoDB
            'prefijo_celular'    => 'required|string|max:10',
            'codigo_pais'        => 'required|string|max:10',
            'ubicacion'          => 'required|array',
        ]);

        // Validar el documento dinámicamente usando regex del tipo de documento
        $tipoDoc = \App\Modules\Auth\Entities\TipoDocumento::findOrFail($validated['id_tipo_documento']);
        $reglas  = $tipoDoc->validacion;
        if (!empty($reglas['regex'])) {
            if (!preg_match('/' . $reglas['regex'] . '/', $validated['documento'])) {
                return response()->json([
                    'message' => $reglas['error_msg'] ?? 'El formato del documento es inválido.'
                ], 422);
            }
        }

        // Crear usuario en SQL
        $usuario = new Usuario();
        $usuario->nombres            = $validated['nombres'];
        $usuario->apellidos          = $validated['apellidos'];
        $usuario->correo_electronico = strtolower($validated['correo_electronico']);
        $usuario->nombre_usuario     = strtolower($validated['nombre_usuario']);
        $usuario->password_hash      = Hash::make($validated['password']);
        $usuario->id_tipo_documento  = $validated['id_tipo_documento'];
        $usuario->documento          = $validated['documento'];
        $usuario->celular            = $validated['celular'];
        $usuario->activo             = true; // Activo por defecto
        $usuario->deleted            = false;
        $usuario->save();

        // Asociar el rol CIUDADANO
        $rol = \App\Modules\Auth\Entities\Rol::where('codigo', 'CIUDADANO')->firstOrFail();
        $usuario->roles()->attach($rol->id, [
            'deleted'    => false,
            'created_at' => now(),
        ]);

        // Crear el perfil en MongoDB
        \App\Modules\Auth\Entities\CiudadanoPerfil::create([
            'usuario_uuid'    => $usuario->uuid,
            'prefijo_celular' => $validated['prefijo_celular'],
            'codigo_pais'     => $validated['codigo_pais'],
            'ubicacion'       => $validated['ubicacion'],
        ]);

        // Autenticar: generar token y registrar en MongoDB
        $token = Str::random(60);
        Cache::store('redis')->put('auth_token:'.$token, $usuario->id, now()->addDays(7));

        HistorialSesion::create([
            'usuario_id'         => $usuario->uuid,
            'correo_electronico' => $usuario->correo_electronico,
            'accion'             => 'LOGIN',
            'ip'                 => $request->ip(),
            'dispositivo'        => $request->userAgent(),
            'fecha_hora'         => now(),
        ]);

        return response()->json([
            'message'      => 'Usuario registrado e ingresado con éxito',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'usuario'      => $usuario->load('roles.opciones'),
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
        $cacheKey = 'ubicaciones:estados:' . hash('sha256', $country);

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
            
            $list = array_unique($list);
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
        $cacheKey = 'ubicaciones:ciudades:' . hash('sha256', $country . '_' . $state);

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
}
