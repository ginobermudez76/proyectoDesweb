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

            // Manejador de redirección a pantalla de error para códigos críticos
            const redirectStatusCodes = [403, 404, 429, 500, 502, 503, 504];
            if (redirectStatusCodes.includes(response.status)) {
                const isSubpage = ['/ciudadano/', '/tecnico/', '/supervisor/', '/admin/']
                                  .some(p => window.location.pathname.includes(p));
                const errorPageUrl = isSubpage ? '../error.html' : 'error.html';
                window.location.href = `${errorPageUrl}?code=${response.status}&message=${encodeURIComponent(msg)}`;
                return;
            }

            // Manejo de alertas en la misma página para códigos no críticos corregibles por el usuario
            switch (response.status) {
                case 400:
                    console.warn(`400 Bad Request: ${msg}`);
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
