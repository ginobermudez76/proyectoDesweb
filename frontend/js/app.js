// Corregir automáticamente doble barra inicial en la URL para evitar que se propague en enlaces relativos
if (window.location.pathname.startsWith('//')) {
    const safePath = '/' + window.location.pathname.replace(/^\/+/, '');
    window.location.replace(safePath + window.location.search + window.location.hash);
}

const API_URL = '/api';

/** Returns the relative path prefix based on the current page location. */
function getDepthPrefix() {
    if (window.location.pathname.includes('/pages/')) return '../../';
    const subFolders = ['/ciudadano/', '/tecnico/', '/supervisor/', '/admin/'];
    if (subFolders.some(p => window.location.pathname.includes(p))) return '../';
    return '';
}

/** Returns true if the current page is the login or root page. */
function isLoginPage() {
    const path = window.location.pathname;
    return path.endsWith('login.html') || path === '/' || path.endsWith('/');
}

/** Handles a 401 response by clearing auth and redirecting to login. */
function handleUnauthorized() {
    if (isLoginPage()) return;
    localStorage.removeItem('auth_token');
    localStorage.removeItem('auth_user');
    showToast('Tu sesión ha expirado. Redirigiendo al inicio de sesión…', 'warning');
    setTimeout(() => { window.location.href = getDepthPrefix() + 'login.html'; }, 2000);
}

/** Handles non-OK HTTP responses: redirects on critical codes, toasts otherwise. */
function handleHttpError(response, msg) {
    const redirectStatusCodes = [403, 404, 429, 500, 502, 503, 504];
    if (!isLoginPage() && redirectStatusCodes.includes(response.status)) {
        window.location.href = `${getDepthPrefix()}error.html?code=${response.status}&message=${encodeURIComponent(msg)}`;
        return;
    }
    switch (response.status) {
        case 400: console.warn(`400 Bad Request: ${msg}`); break;
        case 405: showToast('Acción no permitida por el servidor. Contacta al administrador.', 'error'); break;
        case 409: showToast(msg || 'Conflicto: el registro ya existe o hay datos duplicados.', 'error'); break;
        case 415: showToast('Error de formato: el servidor no reconoció los datos enviados.', 'error'); break;
    }
}

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
            handleUnauthorized();
            return;
        }

        if (!response.ok) {
            const err = await response.json().catch(() => ({}));
            const detailMsgs = err.details ? Object.values(err.details).flat() : [];
            const msg = detailMsgs[0] || err.message || `Error HTTP: ${response.status}`;

            handleHttpError(response, msg);

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