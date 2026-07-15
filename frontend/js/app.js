// Corregir automáticamente doble barra inicial en la URL para evitar que se propague en enlaces relativos
if (window.location.pathname.startsWith('//')) {
    window.location.replace(window.location.pathname.replace(/^\/+/, '/') + window.location.search + window.location.hash);
}

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
            const onLoginPage = window.location.pathname.endsWith('login.html')
                || window.location.pathname === '/'
                || window.location.pathname.endsWith('/');

            if (!onLoginPage) {
                localStorage.removeItem('auth_token');
                localStorage.removeItem('auth_user');
                showToast('Tu sesión ha expirado. Redirigiendo al inicio de sesión…', 'warning');
                setTimeout(() => {
                    const depth = window.location.pathname.includes('/pages/') ? '../../' :
                        ['/ciudadano/', '/tecnico/', '/supervisor/', '/admin/']
                          .some(p => window.location.pathname.includes(p)) ? '../' : '';
                    window.location.href = depth + 'login.html';
                }, 2000);
                return;
            }
        }

        if (!response.ok) {
            const err = await response.json().catch(() => ({}));
            const detailMsgs = err.details ? Object.values(err.details).flat() : [];
            const msg = detailMsgs[0] || err.message || `Error HTTP: ${response.status}`;

            // En la página de login, no redirigimos — dejamos que el caller maneje 401 y 429
            const isLoginPage = window.location.pathname.endsWith('login.html')
                || window.location.pathname === '/'
                || window.location.pathname.endsWith('/');

            // Manejador de redirección a pantalla de error para códigos críticos
            const redirectStatusCodes = [403, 404, 429, 500, 502, 503, 504];
            if (!isLoginPage && redirectStatusCodes.includes(response.status)) {
                const depth = window.location.pathname.includes('/pages/') ? '../../' :
                    ['/ciudadano/', '/tecnico/', '/supervisor/', '/admin/']
                      .some(p => window.location.pathname.includes(p)) ? '../' : '';
                window.location.href = `${depth}error.html?code=${response.status}&message=${encodeURIComponent(msg)}`;
                return;
            }

            // Manejo de alertas en la misma página para códigos no críticos corregibles por el usuario
            switch (response.status) {
                case 400:
                    console.warn(`400 Bad Request: ${msg}`);
                    break;
                case 405:
                    showToast('Acción no permitida por el servidor. Contacta al administrador.', 'error');
                    break;
                case 409:
                    showToast(msg || 'Conflicto: el registro ya existe o hay datos duplicados.', 'error');
                    break;
                case 415:
                    showToast('Error de formato: el servidor no reconoció los datos enviados.', 'error');
                    break;
            }

            // Lanzar error enriquecido con status y datos JSON completos
            const richError = new Error(msg);
            richError.status       = response.status;
            richError.responseJson = err;
            richError.responseText = JSON.stringify(err);
            throw richError;
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
