<?php

namespace App\Shared\HttpStatus;

enum HttpStatus: int
{
    case OK = 200;
    case CREATED = 201;
    case NO_CONTENT = 204;
    case BAD_REQUEST = 400;
    case UNAUTHORIZED = 401;
    case FORBIDDEN = 403;
    case NOT_FOUND = 404;
    case METHOD_NOT_ALLOWED = 405;
    case CONFLICT = 409;
    case UNSUPPORTED_MEDIA_TYPE = 415;
    case UNPROCESSABLE_ENTITY = 422;
    case TOO_MANY_REQUESTS = 429;
    case INTERNAL_SERVER_ERROR = 500;
    case BAD_GATEWAY = 502;
    case SERVICE_UNAVAILABLE = 503;
    case GATEWAY_TIMEOUT = 504;

    public function label(): string
    {
        return match ($this) {
            self::OK => 'Operación exitosa',
            self::CREATED => 'Recurso creado',
            self::NO_CONTENT => 'Sin contenido',
            self::BAD_REQUEST => 'Solicitud incorrecta',
            self::UNAUTHORIZED => 'No autorizado',
            self::FORBIDDEN => 'Prohibido',
            self::NOT_FOUND => 'No encontrado',
            self::METHOD_NOT_ALLOWED => 'Método no permitido',
            self::CONFLICT => 'Conflicto',
            self::UNSUPPORTED_MEDIA_TYPE => 'Tipo de contenido no soportado',
            self::UNPROCESSABLE_ENTITY => 'Entidad no procesable',
            self::TOO_MANY_REQUESTS => 'Demasiadas solicitudes',
            self::INTERNAL_SERVER_ERROR => 'Error interno del servidor',
            self::BAD_GATEWAY => 'Puerta de enlace incorrecta',
            self::SERVICE_UNAVAILABLE => 'Servicio no disponible',
            self::GATEWAY_TIMEOUT => 'Tiempo de espera agotado',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::OK => 'La consulta o solicitud se completó correctamente.',
            self::CREATED => 'Se registró una nueva georreferencia correctamente.',
            self::NO_CONTENT => 'La operación fue exitosa pero no hay datos para devolver.',
            self::BAD_REQUEST => 'Faltan parámetros, el formato es inválido o las coordenadas están fuera de rango.',
            self::UNAUTHORIZED => 'El usuario no ha iniciado sesión o el token es inválido.',
            self::FORBIDDEN => 'El usuario está autenticado, pero no tiene permisos para acceder al recurso.',
            self::NOT_FOUND => 'El recurso, usuario o georreferencia solicitada no existe.',
            self::METHOD_NOT_ALLOWED => 'Se intenta usar un método HTTP no soportado.',
            self::CONFLICT => 'Ya existe un registro con la misma información o existe un conflicto de estado.',
            self::UNSUPPORTED_MEDIA_TYPE => 'Se envía un formato diferente al esperado (por ejemplo, XML cuando solo acepta JSON).',
            self::UNPROCESSABLE_ENTITY => 'Los datos tienen el formato correcto pero fallan las validaciones de negocio.',
            self::TOO_MANY_REQUESTS => 'Se excedió el límite de peticiones permitidas (rate limiting).',
            self::INTERNAL_SERVER_ERROR => 'Ocurrió un error inesperado en el servidor.',
            self::BAD_GATEWAY => 'El servidor recibió una respuesta inválida de otro servicio.',
            self::SERVICE_UNAVAILABLE => 'El servidor está en mantenimiento o sobrecargado.',
            self::GATEWAY_TIMEOUT => 'Un servicio externo tardó demasiado en responder.',
        };
    }
}
