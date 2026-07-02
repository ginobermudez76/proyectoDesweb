const TOKEN_KEY = 'auth_token';
const USER_KEY  = 'auth_user';

function getToken() { return localStorage.getItem(TOKEN_KEY); }
function getUser()  { try { return JSON.parse(localStorage.getItem(USER_KEY)); } catch { return null; } }
function getRole()  {
    const u = getUser();
    return (u && u.roles && u.roles.length) ? u.roles[0].codigo : null;
}

function isCiudadano() { return getRole() === 'CIUDADANO'; }
function isTecnico()   { return getRole() === 'TECNICO'; }
function isSupervisor(){ return getRole() === 'SUPERVISOR'; }
function isAdmin()     { return getRole() === 'ADMIN'; }

// Ruta de dashboard según rol
function dashboardUrl(basePath = '') {
    const sep = basePath ? '/' : '';
    switch (getRole()) {
        case 'CIUDADANO':  return `${basePath}${sep}ciudadano/home.html`;
        case 'TECNICO':    return `${basePath}${sep}tecnico/panel.html`;
        case 'SUPERVISOR': return `${basePath}${sep}supervisor/panel.html`;
        case 'ADMIN':      return `${basePath}${sep}admin/panel.html`;
        default:           return `${basePath}${sep}login.html`;
    }
}

function saveAuth(token, user) {
    localStorage.setItem(TOKEN_KEY, token);
    localStorage.setItem(USER_KEY, JSON.stringify(user));
}
function clearAuth() {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(USER_KEY);
}

async function logout(basePath = '..') {
    try { await apiFetch('/logout', { method: 'POST' }); } catch {}
    clearAuth();
    window.location.href = `${basePath}/login.html`;
}

function requireAuth(basePath = '..') {
    if (!getToken()) { window.location.href = `${basePath}/login.html`; return false; }
    return true;
}

function requireRole(allowed) {
    if (!requireAuth()) return false;
    if (!allowed.includes(getRole())) {
        window.location.href = dashboardUrl('..');
        return false;
    }
    return true;
}

function requireCiudadano() { return requireRole(['CIUDADANO']); }
function requireTecnico()   { return requireRole(['TECNICO']); }
function requireSupervisor(){ return requireRole(['SUPERVISOR']); }
function requireAdmin()     { return requireRole(['ADMIN']); }
function requireOperador()  { return requireRole(['TECNICO', 'SUPERVISOR', 'ADMIN']); }

function getUserName() {
    const u = getUser();
    if (!u) return '';
    return `${u.nombres || ''} ${u.apellidos || ''}`.trim() || u.nombre_usuario || '';
}
function getUserInitials() {
    const u = getUser();
    if (!u) return '?';
    const n = (u.nombres || u.nombre_usuario || '?')[0].toUpperCase();
    const a = (u.apellidos || '')[0]?.toUpperCase() || '';
    return n + a;
}

function timeAgo(dateStr) {
    if (!dateStr) return '';
    const diff = Date.now() - new Date(dateStr).getTime();
    const m = Math.floor(diff / 60000);
    if (m < 1)  return 'Ahora';
    if (m < 60) return `Hace ${m} min`;
    const h = Math.floor(m / 60);
    if (h < 24) return `Hace ${h} h`;
    return `Hace ${Math.floor(h / 24)} días`;
}

function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleString('es-MX', { day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit' });
}

// Backend states: Pendiente | En Proceso | Resuelta | Rechazada
const ESTADO_MAP = {
    'Pendiente':  { label: 'Recibido',   cls: 'badge-recibido', step: 0 },
    'En Proceso': { label: 'En proceso', cls: 'badge-proceso',  step: 1 },
    'Resuelta':   { label: 'Resuelto',   cls: 'badge-resuelto', step: 2 },
    'Rechazada':  { label: 'Rechazado',  cls: 'badge-urgente',  step: -1 },
};
function estadoInfo(e) { return ESTADO_MAP[e] || ESTADO_MAP['Pendiente']; }

const PRIORIDAD_MAP = {
    'Urgente': { cls: 'text-urgente', badgeCls: 'badge-urgente', borderCls: 'border-urgente' },
    'Alta':    { cls: 'text-urgente', badgeCls: 'badge-urgente', borderCls: 'border-urgente' },
    'Media':   { cls: 'text-media',   badgeCls: 'badge-media',   borderCls: 'border-media'   },
    'Normal':  { cls: 'text-media',   badgeCls: 'badge-media',   borderCls: 'border-media'   },
    'Baja':    { cls: 'text-baja',    badgeCls: 'badge-baja',    borderCls: 'border-baja'    },
};
function prioridadInfo(p) { return PRIORIDAD_MAP[p] || PRIORIDAD_MAP['Media']; }

function progressBars(estado) {
    const step = estadoInfo(estado).step;
    return ['Recibido', 'En proceso', 'Resuelto'].map((s, i) => {
        let cls = 'step-bar' + (i < step ? ' done' : i === step ? ' active' : '');
        return `<div class="step-bar-wrap"><div class="${cls}"></div><div class="step-label">${s}</div></div>`;
    }).join('');
}

function incidentCardHTML(inc, href) {
    const p = prioridadInfo(inc.prioridad);
    const e = estadoInfo(inc.estado);
    return `
    <div class="incident-card ${p.borderCls}" onclick="window.location.href='${href}'">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px">
            <span style="font-size:14px;font-weight:600;color:var(--gray-900)">${inc.titulo}</span>
            <span class="badge-status ${p.badgeCls}">${inc.prioridad || 'Media'}</span>
        </div>
        <div class="text-muted-sm" style="margin-bottom:6px">
            <i class="bi bi-geo-alt" style="font-size:11px"></i>
            ${inc.ubicacion ? 'Ver ubicación' : 'Sin ubicación'} · ${timeAgo(inc.fecha_creacion)}
        </div>
        <span class="badge-status ${e.cls}">${e.label}</span>
    </div>`;
}
