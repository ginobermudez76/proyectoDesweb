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
        const isSubpage = ['/ciudadano/', '/tecnico/', '/supervisor/', '/admin/']
                          .some(p => window.location.pathname.includes(p));
        const errorPageUrl = isSubpage ? '../error.html' : 'error.html';
        window.location.href = `${errorPageUrl}?code=403&message=${encodeURIComponent('Acceso denegado (403)')}`;
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

function tiempoResolucion(fechaCreacion, historial) {
    const fin = historial?.find(h => h.estado_nuevo === 'Resuelta')?.fecha_cambio;
    if (!fin || !fechaCreacion) return null;
    const mins = Math.round((new Date(fin) - new Date(fechaCreacion)) / 60000);
    if (mins < 60)   return `${mins} min`;
    if (mins < 1440) return `${Math.round(mins / 60)} h`;
    return `${Math.round(mins / 1440)} días`;
}

function showNotifBadge(count) {
    if (!count) return;
    const bell = document.querySelector('.bi-bell');
    if (!bell) return;
    const wrap = document.createElement('span');
    wrap.style.cssText = 'position:relative;display:inline-flex';
    bell.parentNode.replaceChild(wrap, bell);
    wrap.appendChild(bell);
    const badge = document.createElement('span');
    badge.className = 'badge bg-danger rounded-pill position-absolute';
    badge.style.cssText = 'font-size:10px;top:-6px;right:-8px;min-width:18px;padding:2px 5px;line-height:1.2';
    badge.textContent = count > 9 ? '9+' : String(count);
    wrap.appendChild(badge);
}

/* ===== DESKTOP SIDEBAR INJECTION ===== */

const NAV_LINKS = {
    CIUDADANO: [
        { href: 'home.html',    icon: 'bi-house-fill', label: 'Inicio'   },
        { href: 'mapa.html',    icon: 'bi-map',        label: 'Mapa'     },
        { href: 'perfil.html',  icon: 'bi-person',     label: 'Perfil'   },
    ],
    TECNICO: [
        { href: 'panel.html',   icon: 'bi-house-fill', label: 'Inicio'   },
        { href: 'mapa.html',    icon: 'bi-map',        label: 'Mapa'     },
        { href: 'perfil.html',  icon: 'bi-person',     label: 'Perfil'   },
    ],
    SUPERVISOR: [
        { href: 'panel.html',   icon: 'bi-house-fill', label: 'Inicio'   },
        { href: 'mapa.html',    icon: 'bi-map',        label: 'Mapa'     },
        { href: 'perfil.html',  icon: 'bi-person',     label: 'Perfil'   },
    ],
    ADMIN: [
        { href: 'panel.html',    icon: 'bi-house-fill', label: 'Inicio'  },
        { href: 'usuarios.html', icon: 'bi-people',     label: 'Usuarios'},
        { href: 'perfil.html',   icon: 'bi-person',     label: 'Perfil'  },
    ],
};

function _navPrefix() {
    const p = window.location.pathname;
    return ['/ciudadano/', '/tecnico/', '/supervisor/', '/admin/'].some(s => p.includes(s))
        ? '../' : '';
}

function _roleFolder() {
    const p = window.location.pathname;
    for (const f of ['ciudadano','tecnico','supervisor','admin']) {
        if (p.includes(`/${f}/`)) return f;
    }
    return { CIUDADANO:'ciudadano', TECNICO:'tecnico', SUPERVISOR:'supervisor', ADMIN:'admin' }[getRole()] || '';
}

function _decorateExistingNav(nav) {
    if (!nav.querySelector('.nav-brand')) {
        const brand = document.createElement('div');
        brand.className = 'nav-brand';
        brand.innerHTML = '<span class="nav-brand-icon">📍</span> Incidencias';
        nav.prepend(brand);
    }
    if (!nav.querySelector('.nav-logout-link')) {
        const spacer = document.createElement('div');
        spacer.className = 'nav-divider';
        const btn = document.createElement('a');
        btn.href = '#';
        btn.className = 'nav-logout-link';
        btn.innerHTML = '<i class="bi bi-box-arrow-right"></i><span>Cerrar sesión</span>';
        btn.addEventListener('click', e => { e.preventDefault(); logout(); });
        nav.appendChild(spacer);
        nav.appendChild(btn);
    }
}

function _injectSidebarForPage() {
    const role  = getRole();
    const links = NAV_LINKS[role];
    if (!links) return;

    const prefix = _navPrefix();
    const folder = _roleFolder();

    const nav = document.createElement('nav');
    nav.className = 'bottom-nav';
    nav.innerHTML = `
        <div class="nav-brand"><span class="nav-brand-icon">📍</span> Incidencias</div>
        ${links.map(l =>
            `<a href="${prefix}${folder}/${l.href}"><i class="bi ${l.icon}"></i><span>${l.label}</span></a>`
        ).join('')}
        <div class="nav-divider"></div>
        <a href="#" class="nav-logout-link" id="_sidebarLogout">
            <i class="bi bi-box-arrow-right"></i><span>Cerrar sesión</span>
        </a>`;

    document.body.appendChild(nav);
    nav.querySelector('#_sidebarLogout').addEventListener('click', e => {
        e.preventDefault(); logout();
    });

    document.body.style.paddingLeft  = '240px';
    document.body.style.paddingBottom = '0';
}

document.addEventListener('DOMContentLoaded', () => {
    const existingNav = document.querySelector('.bottom-nav');
    if (existingNav) {
        _decorateExistingNav(existingNav);
    } else if (window.innerWidth >= 768 && getToken()) {
        _injectSidebarForPage();
    }
});
