const API_URL = '/api';

async function apiFetch(endpoint, options = {}) {
    const token = localStorage.getItem('auth_token');
    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...options.headers
    };
    if (token) headers['Authorization'] = `Bearer ${token}`;

    try {
        const response = await fetch(`${API_URL}${endpoint}`, { ...options, headers });

        if (response.status === 401) {
            localStorage.removeItem('auth_token');
            localStorage.removeItem('auth_user');
            alert('Sesión expirada o no autorizada. Redirigiendo al inicio de sesión.');
            const isSubpage = ['/ciudadano/', '/tecnico/', '/supervisor/', '/admin/']
                              .some(p => window.location.pathname.includes(p));
            window.location.href = isSubpage ? '../login.html' : 'login.html';
            return;
        }

        if (!response.ok) {
            const err = await response.json().catch(() => ({}));
            const msg = err.message || `Error HTTP: ${response.status}`;

            // Manejador centralizado de errores por Código de Estado HTTP
            switch (response.status) {
                case 400:
                    console.warn(`400 Bad Request: ${msg}`);
                    break;
                case 403:
                    alert(`Acceso denegado (403):\nNo tienes permisos para acceder a esta sección o recurso.`);
                    break;
                case 404:
                    console.warn(`404 Not Found: ${msg}`);
                    break;
                case 405:
                    alert(`Método no permitido (405):\nLa acción solicitada no es compatible con el servidor.`);
                    break;
                case 409:
                    alert(`Conflicto (409):\n${msg}`);
                    break;
                case 415:
                    alert(`Tipo de contenido no soportado (415):\nEl servidor esperaba un formato diferente.`);
                    break;
                case 429:
                    alert(`Demasiadas solicitudes (429):\nSe ha excedido el límite de peticiones. Por favor, espera un momento e intenta de nuevo.`);
                    break;
                case 500:
                    alert(`Error interno del servidor (500):\nOcurrió un fallo inesperado en el sistema. Inténtalo de nuevo más tarde.`);
                    break;
                case 502:
                    alert(`Puerta de enlace incorrecta (502):\nEl servidor recibió una respuesta inválida de un servicio externo (GPS o Mapas).`);
                    break;
                case 503:
                    alert(`Servicio no disponible (503):\nEl servidor se encuentra temporalmente en mantenimiento o sobrecargado.`);
                    break;
                case 504:
                    alert(`Tiempo de espera agotado (504):\nUn servicio externo tardó demasiado tiempo en responder.`);
                    break;
            }

            throw new Error(msg);
        }

        return await response.json();
    } catch (error) {
        console.error('Error en la petición a la API:', error);
        throw error;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    console.log('Frontend listo.');
});
