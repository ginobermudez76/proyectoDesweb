const TOKEN_KEY = 'auth_token';
const USER_KEY = 'auth_user';

function joinUrl(basePath, fileName) {
  const normalizedBase = basePath || '';
  const separator = normalizedBase.endsWith('/') ? '' : '/';
  return `${normalizedBase}${separator}${fileName}`;
}

function getFirstRoleCode(user) {
  return user?.roles?.[0]?.codigo ?? null;
}

function getToken() {
  return localStorage.getItem(TOKEN_KEY);
}
function getUser() {
  try {
    return JSON.parse(localStorage.getItem(USER_KEY));
  } catch {
    return null;
  }
}
function getRole() {
  return getFirstRoleCode(getUser());
}

function isCiudadano() {
  return getRole() === 'CIUDADANO';
}
function isTecnico() {
  return getRole() === 'TECNICO';
}
function isSupervisor() {
  return getRole() === 'SUPERVISOR';
}
function isAdmin() {
  return getRole() === 'ADMIN';
}

// Ruta de dashboard según rol → nueva estructura por opción
function dashboardUrl(basePath = '') {
  const role = getRole();
  if (!role) return joinUrl(basePath, 'login.html');

  // Administrador y Ciudadano inician en su Panel de control (dashboard)
  if (role === 'ADMIN' || role === 'CIUDADANO') {
    return joinUrl(basePath, 'pages/dashboard/dashboard.html');
  }

  // Técnico y Supervisor van al panel de incidencias
  return joinUrl(basePath, 'pages/incidencias/panel.html');
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
  try {
    await apiFetch('/logout', { method: 'POST' });
  } catch {}
  clearAuth();
  window.location.href = joinUrl(basePath, 'login.html');
}

async function confirmLogout(basePath = '..') {
  // Si no está bootstrap ni modal, los cargamos dinámicamente
  if (typeof bootstrap === 'undefined') {
    await new Promise((resolve) => {
      const script = document.createElement('script');
      script.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js';
      script.integrity = 'sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz';
      script.crossOrigin = 'anonymous';
      script.onload = resolve;
      document.head.appendChild(script);
    });
  }
  if (typeof showConfirmModal !== 'function') {
    await new Promise((resolve) => {
      const script = document.createElement('script');
      script.src = '/js/modal.js';
      script.onload = resolve;
      document.head.appendChild(script);
    });
  }

  const ok = await showConfirmModal(
    'Cerrar sesión',
    '¿Estás seguro de que deseas cerrar sesión?',
    'Confirmar',
    'btn-danger'
  );
  if (ok) logout(basePath);
}

async function sendClientUnauthorizedLog(tipoViolacion, url, detalle) {
  try {
    await apiFetch('/logs/unauthorized', {
      method: 'POST',
      body: JSON.stringify({
        tipo_violacion: tipoViolacion,
        url: url,
        detalle: detalle,
        metodo: 'GET',
      }),
    });
  } catch (e) {
    console.error('Error al registrar acceso no autorizado en MongoDB:', e);
  }
}

function requireAuth(basePath = null) {
  const prefix = basePath !== null ? basePath : _navPrefix();
  if (!getToken()) {
    sendClientUnauthorizedLog(
      'TOKEN_AUSENTE_CLIENT',
      window.location.pathname,
      'Intento de acceso a página protegida sin sesión activa (401 Client).'
    ).then(() => {
      window.location.href = joinUrl(prefix, 'login.html');
    });
    return false;
  }

  // Comprobar si hay aviso de 2FA pendiente al entrar a cualquier pantalla del sistema
  if (sessionStorage.getItem('show_2fa_reminder') === '1') {
    sessionStorage.removeItem('show_2fa_reminder');
    setTimeout(() => {
      _mostrarModalGlobal2FAReminder(prefix);
    }, 400);
  }

  return true;
}

function _mostrarModalGlobal2FAReminder(prefix) {
  if (document.getElementById('_global2faReminderOverlay')) return;

  const overlay = document.createElement('div');
  overlay.id = '_global2faReminderOverlay';
  overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px';
  overlay.innerHTML = `
    <div style="background:var(--bg-card, #ffffff);border-radius:20px;padding:24px;width:100%;max-width:400px;text-align:center;box-shadow:0 10px 25px rgba(0,0,0,0.2)">
        <div class="text-warning mb-2" style="font-size:42px"><i class="bi bi-shield-exclamation"></i></div>
        <div style="font-size:18px;font-weight:700;margin-bottom:8px;color:var(--gray-900, #1e293b)">Autenticación de 2 Pasos Requerida</div>
        <div class="text-muted-sm mb-4" style="font-size:13px;color:var(--gray-600, #64748b)">Tu rol de usuario requiere que configures al menos un método de Autenticación de Doble Factor (App Autenticadora o Correo Electrónico) para mayor seguridad.</div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
            <button class="btn btn-outline-secondary" style="font-size:13px;border-radius:12px" onclick="document.getElementById('_global2faReminderOverlay').remove()">Luego</button>
            <button class="btn btn-warning text-dark font-weight-bold" style="font-size:13px;border-radius:12px;font-weight:700" onclick="window.location.href='${joinUrl(prefix, 'pages/perfil/perfil.html')}'">Configurar Ahora</button>
        </div>
    </div>
  `;
  document.body.appendChild(overlay);
}

function requireRole(allowed) {
  const prefix = _navPrefix();
  if (!requireAuth(prefix)) return false;
  if (!allowed.includes(getRole())) {
    sendClientUnauthorizedLog(
      'RBAC_CLIENT',
      window.location.pathname,
      `Intento de acceso a página restringida para roles permitidos. Rol actual: ${getRole()}`
    ).then(() => {
      window.location.href = `${joinUrl(prefix, 'error.html')}?code=403&message=${encodeURIComponent('Acceso denegado (403)')}`;
    });
    return false;
  }
  return true;
}

function requireCiudadano() {
  return requireRole(['CIUDADANO']);
}
function requireTecnico() {
  return requireRole(['TECNICO']);
}
function requireSupervisor() {
  return requireRole(['SUPERVISOR']);
}
function requireAdmin() {
  return requireRole(['ADMIN']);
}
function requireOperador() {
  return requireRole(['TECNICO', 'SUPERVISOR', 'ADMIN']);
}

function getUserName() {
  const u = getUser();
  if (!u) return '';
  return `${u.nombres ?? ''} ${u.apellidos ?? ''}`.trim() || u.nombre_usuario || '';
}
function getUserInitials() {
  const u = getUser();
  if (!u) return '?';
  const n = (u.nombres ?? u.nombre_usuario ?? '?')[0].toUpperCase();
  const a = u.apellidos?.[0]?.toUpperCase() ?? '';
  return n + a;
}

/** Timestamp (ms) de la publicación más reciente de una lista, o 0 si está vacía. */
function fechaMasReciente(publicaciones) {
  return (publicaciones || []).reduce((max, p) => {
    const t = p.fecha_publicacion ? new Date(p.fecha_publicacion).getTime() : 0;
    return t > max ? t : max;
  }, 0);
}

/** Clave de localStorage donde se guarda la última visita del usuario actual a Comunicados. */
function _comunicadosVistoKey() {
  const uuid = getUser()?.uuid;
  return uuid ? `comunicados_visto_${uuid}` : null;
}

/** Marca todos los comunicados actualmente cargados como vistos por el usuario. */
function marcarComunicadosVistos(publicaciones) {
  const key = _comunicadosVistoKey();
  if (!key) return;
  localStorage.setItem(key, String(fechaMasReciente(publicaciones)));
}

/**
 * Consulta si hay comunicados más nuevos que la última visita del usuario y,
 * de ser así, agrega un punto distintivo al link "Comunicados" del menú.
 */
async function revisarComunicadosNuevos() {
  const key = _comunicadosVistoKey();
  if (!key) return;

  try {
    const publicaciones = await apiFetch('/publicaciones');
    const masReciente = fechaMasReciente(publicaciones);
    const ultimaVisita = Number(localStorage.getItem(key) || 0);

    if (masReciente > ultimaVisita) {
      const link = document.querySelector('.bottom-nav a[href*="publicaciones.html"]');
      if (link && !link.querySelector('.nav-new-dot')) {
        link.style.position = 'relative';
        const dot = document.createElement('span');
        dot.className = 'nav-new-dot';
        dot.style.cssText = 'position:absolute;top:6px;right:10px;width:8px;height:8px;border-radius:50%;background:var(--red);border:1.5px solid var(--surface-2)';
        link.appendChild(dot);
      }
    }
  } catch {
    // Silencioso: el indicador es informativo, no debe interrumpir la carga de la página.
  }
}

function timeAgo(dateStr) {
  if (!dateStr) return '';
  const diff = Date.now() - new Date(dateStr).getTime();
  const m = Math.floor(diff / 60000);
  if (m < 1) return 'Ahora';
  if (m < 60) return `Hace ${m} min`;
  const h = Math.floor(m / 60);
  if (h < 24) return `Hace ${h} h`;
  return `Hace ${Math.floor(h / 24)} días`;
}

function formatDate(d) {
  if (!d) return '—';
  return new Date(d).toLocaleString('es-MX', {
    day: '2-digit',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  });
}

// Backend states: Pendiente | En Proceso | Resuelta | Rechazada
const ESTADO_MAP = {
  Pendiente: { label: 'Recibido', cls: 'badge-recibido', step: 0 },
  'En Proceso': { label: 'En proceso', cls: 'badge-proceso', step: 1 },
  Resuelta: { label: 'Resuelto', cls: 'badge-resuelto', step: 2 },
  Rechazada: { label: 'Rechazado', cls: 'badge-urgente', step: -1 },
};
function estadoInfo(e) {
  return ESTADO_MAP[e] || ESTADO_MAP['Pendiente'];
}

const PRIORIDAD_MAP = {
  Urgente: { cls: 'text-urgente', badgeCls: 'badge-urgente', borderCls: 'border-urgente' },
  Alta: { cls: 'text-urgente', badgeCls: 'badge-urgente', borderCls: 'border-urgente' },
  Media: { cls: 'text-media', badgeCls: 'badge-media', borderCls: 'border-media' },
  Normal: { cls: 'text-media', badgeCls: 'badge-media', borderCls: 'border-media' },
  Baja: { cls: 'text-baja', badgeCls: 'badge-baja', borderCls: 'border-baja' },
};
function prioridadInfo(p) {
  return PRIORIDAD_MAP[p] || PRIORIDAD_MAP['Media'];
}

function progressBars(estado) {
  const step = estadoInfo(estado).step;
  return ['Recibido', 'En proceso', 'Resuelto']
    .map((s, i) => {
      let cls = 'step-bar' + (i < step ? ' done' : i === step ? ' active' : '');
      return `<div class="step-bar-wrap"><div class="${cls}"></div><div class="step-label">${s}</div></div>`;
    })
    .join('');
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
  const fin = historial?.find((h) => h.estado_nuevo === 'Resuelta')?.fecha_cambio;
  if (!fin || !fechaCreacion) return null;
  const mins = Math.round((new Date(fin) - new Date(fechaCreacion)) / 60000);
  if (mins < 60) return `${mins} min`;
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
  badge.style.cssText =
    'font-size:10px;top:-6px;right:-8px;min-width:18px;padding:2px 5px;line-height:1.2';
  badge.textContent = count > 9 ? '9+' : String(count);
  wrap.appendChild(badge);
}

/* ===== DYNAMIC DATABASE-DRIVEN RBAC NAVEGACION ===== */

function getOpciones() {
  const u = getUser();
  return (
    u?.roles?.flatMap((r) =>
      (r.opciones ?? []).filter((o) => !o.deleted).map((o) => o.nombre_opcion)
    ) ?? []
  );
}

function getNavLinks() {
  const ops = getOpciones();
  const role = getRole();
  const links = [];

    // Dashboard - ADMIN y SUPERVISOR (global) y CIUDADANO (sus propias incidencias)
    if (role === 'ADMIN' || role === 'SUPERVISOR' || role === 'CIUDADANO') {
        links.push({ href: 'pages/dashboard/dashboard.html', icon: 'bi-speedometer2', label: 'Panel', group: 'general' });
    }

    // Incidencias — visible para roles autenticados (excepto ADMIN que no ve incidencias ni mapa)
    // Para CIUDADANO, "Panel de control" ya incluye la lista completa + reportar, así que no
    // duplicamos "Inicio" en su menú.
    const hasIncidencias = ops.some(o => o.includes('Incidencias'));
    if (hasIncidencias && role !== 'ADMIN') {
        if (role !== 'CIUDADANO') {
            links.push({ href: 'pages/incidencias/panel.html', icon: 'bi-house-fill', label: 'Inicio', group: 'general' });
        } else {
            links.push({ href: 'pages/incidencias/reportar.html', icon: 'bi-plus-circle', label: 'Reportar', group: 'general' });
        }
        links.push({ href: 'pages/incidencias/mapa.html',     icon: 'bi-map',        label: 'Mapa', group: 'general' });
    }

    // Comunicados — visibles para todos los roles autenticados
    links.push({ href: 'pages/publicaciones/publicaciones.html', icon: 'bi-megaphone', label: 'Comunicados', group: 'general' });

    // Gestión de usuarios — Admin y Supervisor
    const hasUsuarios = ops.some(o => o.includes('Gestión de Usuarios'));
    if (hasUsuarios) {
        links.push({ href: 'pages/usuarios/usuarios.html', icon: 'bi-people', label: 'Usuarios y roles', group: 'administracion' });
    }

    // Gestión de Roles — Admin
    const hasRoles = ops.some(o => o.includes('Gestión de Roles'));
    if (hasRoles) {
        links.push({ href: 'pages/roles/roles.html', icon: 'bi-shield-lock', label: 'Roles', group: 'administracion' });
    }

    // Catálogos (gestión) — solo ADMIN tiene permiso de escritura sobre "Catálogos"
    if (role === 'ADMIN') {
        links.push({ href: 'pages/catalogos/catalogos.html', icon: 'bi-tags', label: 'Catálogos', group: 'administracion' });
    }

    // Auditoría — solo ADMIN
    const hasAuditoria = ops.some(o => o.includes('Auditoría'));
    if (hasAuditoria) {
        links.push({ href: 'pages/auditoria/auditoria.html', icon: 'bi-clock-history', label: 'Auditoría', group: 'administracion' });
    }

    // Perfil — todos los roles. En móvil es el único acceso a "Cerrar sesión"
    // (el sidebar de escritorio la reemplaza por la tarjeta de usuario al pie).
    const hasPerfil = ops.some(o => o.includes('Perfil de Usuario'));
    if (hasPerfil) {
        links.push({ href: 'pages/perfil/perfil.html', icon: 'bi-person', label: 'Perfil', group: 'general' });
    }

    return links;
}

const NAV_GROUP_LABELS = { general: 'GENERAL', administracion: 'ADMINISTRACIÓN' };
const ROL_LABELS_CORTO = { ADMIN: 'Administrador', SUPERVISOR: 'Supervisor', TECNICO: 'Técnico', CIUDADANO: 'Ciudadano' };

function _navPrefix() {
  const p = window.location.pathname;
  // Páginas en pages/[opcion]/ → necesitan 2 niveles hacia arriba para llegar a la raíz
  if (p.includes('/pages/')) return '../../';
  // Páginas legacy en [rol]/ → 1 nivel
  if (['/ciudadano/', '/tecnico/', '/supervisor/', '/admin/'].some((s) => p.includes(s)))
    return '../';
  return '';
}

function _roleFolder() {
  // En la nueva estructura todas las páginas están en pages/[opcion]/
  // No necesitamos resolver carpeta por rol, los hrefs ya son absolutos desde la raíz
  return '';
}

function _renderNavLinks(navElement) {
  const links = getNavLinks();
  const prefix = _navPrefix();
  const isDesktop = window.innerWidth >= 768;

  let html = '';
  if (isDesktop) {
    html += '<div class="nav-brand"><span class="nav-brand-icon">📍</span> Incidencias</div>';
  }

    // En escritorio, Perfil se muestra como tarjeta de usuario al pie, no como link suelto.
    const linksVisibles = isDesktop ? links.filter(l => !l.href.includes('perfil/perfil.html')) : links;

    let lastGroup = null;
    linksVisibles.forEach(l => {
        if (isDesktop && l.group && l.group !== lastGroup) {
            html += `<div class="nav-group-label">${NAV_GROUP_LABELS[l.group] || ''}</div>`;
            lastGroup = l.group;
        }
        const fullHref    = prefix + l.href; // Los hrefs en getNavLinks() ya son relativos a la raíz del frontend
        const activeClass = window.location.pathname.includes(l.href.split('/').pop()) ? 'active' : '';
        html += `<a href="${fullHref}" class="${activeClass}"><i class="bi ${l.icon}"></i><span>${l.label}</span></a>`;
    });

    html += '<div class="nav-divider"></div>';

    if (isDesktop) {
        const role     = getRole();
        const color    = (typeof APP_COLORS !== 'undefined' && APP_COLORS.roles[role]) || '#9CA3AF';
        html += `
        <div class="nav-user-card">
            <a href="${prefix}pages/perfil/perfil.html" class="nav-user-link">
                <span class="nav-user-avatar" style="background:${color}">${getUserInitials()}</span>
                <span class="nav-user-info">
                    <span class="nav-user-name">${getUserName() || 'Usuario'}</span>
                    <span class="nav-user-role">${ROL_LABELS_CORTO[role] || role || ''}</span>
                </span>
            </a>
            <button type="button" class="nav-user-logout" id="_sidebarLogout" aria-label="Cerrar sesión">
                <i class="bi bi-box-arrow-right"></i>
            </button>
        </div>`;
    } else {
        html += `<a href="#" class="nav-logout-link" id="_sidebarLogout">
            <i class="bi bi-box-arrow-right"></i><span>Cerrar sesión</span>
        </a>`;
    }

  navElement.innerHTML = html;
  navElement.querySelector('#_sidebarLogout')?.addEventListener('click', (e) => {
    e.preventDefault();
    confirmLogout(prefix || '.');
  });
}

// Validar acceso a la página actual según opciones de la base de datos (rol_opcion)
async function checkRoutePermission() {
  const p = window.location.pathname;

    // Si es la página de login, index o página de error, no validar
    if (p.endsWith('login.html') || p.endsWith('error.html') || p === '/' || p.endsWith('/')) {
        return;
    }

    // Si el usuario no tiene token y está en otra página, requireAuth() lo redirigirá a login.html
    if (!getToken()) return;

    const ops  = getOpciones();
    const role = getRole();

    // 1. Gestión de Usuarios
    if (p.includes('/pages/usuarios/') && !ops.includes('Gestión de Usuarios')) {
        await denyAccess();
    }

    // 2. Gestión de Roles
    if (p.includes('/pages/roles/') && !ops.includes('Gestión de Roles')) {
        await denyAccess();
    }

    // 3. Dashboard (ADMIN y SUPERVISOR ven datos globales, CIUDADANO ve solo sus propias incidencias)
    if (p.includes('/pages/dashboard/') && role !== 'ADMIN' && role !== 'SUPERVISOR' && role !== 'CIUDADANO') {
        await denyAccess();
    }

    // 4. Incidencias y Mapa (requiere opciones de Incidencias, ADMIN no tiene permitido verlas ni mapa)
    if (p.includes('/pages/incidencias/')) {
        const tienePermisoIncidencias = ops.some(o => o.includes('Incidencias'));
        if (!tienePermisoIncidencias || role === 'ADMIN') {
            await denyAccess();
        }
    }
}

async function denyAccess() {
  const prefix = _navPrefix();
  const sep = prefix && prefix.endsWith('/') ? '' : '/';

  // Registrar en MongoDB antes de redirigir
  await sendClientUnauthorizedLog(
    'RBAC_CLIENT',
    window.location.pathname,
    `Acceso denegado client-side a la página actual. Rol: ${getRole()}`
  );

  window.location.href = `${prefix}${sep}error.html?code=403&message=${encodeURIComponent('Acceso denegado (RBAC)')}`;
  throw new Error('Acceso no autorizado');
}

/* ===== NOTIFICACIONES EN TIEMPO REAL (POLLING & DROPDOWN) ===== */

const NOTIF_STYLING_ID = '_notif_styles';
if (!document.getElementById(NOTIF_STYLING_ID)) {
  const s = document.createElement('style');
  s.id = NOTIF_STYLING_ID;
  s.textContent = `
        .bell-wrapper {
            position: relative;
            display: inline-flex;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            transition: background .2s;
        }
        .bell-wrapper:hover {
            background: var(--gray-100);
        }
        .bell-badge {
            position: absolute;
            top: 2px;
            right: 2px;
            background: #EF4444;
            color: #fff;
            font-size: 9px;
            font-weight: 700;
            min-width: 15px;
            height: 15px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1.5px solid var(--white);
            padding: 0 2px;
        }
        .notif-panel {
            position: fixed;
            width: min(360px, 92vw);
            max-height: 440px;
            background: var(--white);
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.18);
            border: 1px solid var(--gray-200);
            z-index: 99999;
            display: none;
            flex-direction: column;
            overflow: hidden;
            animation: fadeInNotif 0.18s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes fadeInNotif {
            from { opacity: 0; transform: translateY(-6px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .notif-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            border-bottom: 1px solid var(--gray-200);
            background: var(--gray-50);
        }
        .notif-title {
            font-size: 13.5px;
            font-weight: 700;
            color: var(--gray-900);
        }
        .notif-clear-btn {
            font-size: 11.5px;
            color: var(--orange);
            background: none;
            border: none;
            font-weight: 600;
            cursor: pointer;
            padding: 2px 6px;
            border-radius: 6px;
            transition: background .15s;
        }
        .notif-clear-btn:hover {
            background: var(--bg-orange-light);
        }
        .notif-list {
            overflow-y: auto;
            flex: 1;
        }
        .notif-item {
            display: flex;
            flex-direction: column;
            padding: 12px 16px;
            border-bottom: 1px solid var(--gray-100);
            cursor: pointer;
            transition: background 0.15s ease;
            text-decoration: none;
        }
        .notif-item:hover {
            background: var(--gray-50);
        }
        .notif-item.unread {
            background: var(--bg-orange-light);
            border-left: 3px solid var(--orange);
        }
        .notif-item-title {
            font-size: 12.5px;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 3px;
        }
        .notif-item-body {
            font-size: 12px;
            color: var(--gray-700);
            line-height: 1.4;
            margin-bottom: 4px;
        }
        .notif-item-time {
            font-size: 10px;
            color: var(--gray-400);
        }
        .notif-empty {
            padding: 32px 16px;
            text-align: center;
            color: var(--gray-400);
            font-size: 13px;
        }
    `;
  document.head.appendChild(s);
}

let lastNotifIds = new Set();
let isFirstLoad = true;

function setupNotificationsUI() {
  const bell = document.querySelector('.bi-bell');
  if (!bell) return;

  // Si ya fue configurado el wrapper, no repetir
  if (bell.parentNode.classList.contains('bell-wrapper')) return;

  // Solicitar permiso de notificaciones de escritorio al navegador
  if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
  }

  const wrapper = document.createElement('div');
  wrapper.className = 'bell-wrapper';
  bell.parentNode.replaceChild(wrapper, bell);
  wrapper.appendChild(bell);

  const badge = document.createElement('span');
  badge.className = 'bell-badge';
  badge.style.display = 'none';
  wrapper.appendChild(badge);

  // Crear el panel de notificaciones e insertarlo en el body
  const panel = document.createElement('div');
  panel.id = '_notifPanel';
  panel.className = 'notif-panel';
  panel.innerHTML = `
        <div class="notif-header">
            <span class="notif-title">Notificaciones</span>
            <button class="notif-clear-btn" onclick="marcarTodasLasNotificacionesLeidas()">Marcar todo como leído</button>
        </div>
        <div class="notif-list" id="_notifList">
            <div class="notif-empty">Cargando notificaciones…</div>
        </div>
    `;
  document.body.appendChild(panel);

  // Manejar el toggle al hacer click en la campana
  wrapper.addEventListener('click', (e) => {
    e.stopPropagation();
    const isVisible = panel.style.display === 'flex';

    // Cerrar otros paneles si los hay
    document.querySelectorAll('.notif-panel').forEach((p) => (p.style.display = 'none'));

    if (!isVisible) {
      // Posicionar el panel debajo de la campana
      const rect = wrapper.getBoundingClientRect();
      panel.style.top = `${rect.bottom + window.scrollY + 8}px`;

      // Alinear al borde derecho en pantallas grandes
      if (window.innerWidth >= 768) {
        panel.style.left = 'auto';
        panel.style.right = `${window.innerWidth - rect.right - window.scrollX}px`;
      } else {
        panel.style.right = '16px';
        panel.style.left = 'auto';
      }

      panel.style.display = 'flex';
      renderNotificacionesLista();
    } else {
      panel.style.display = 'none';
    }
  });

  // Cerrar al hacer click fuera
  document.addEventListener('click', (e) => {
    if (!panel.contains(e.target) && !wrapper.contains(e.target)) {
      panel.style.display = 'none';
    }
  });
}

async function fetchNotificaciones() {
  if (!getToken()) return;
  try {
    const notifs = await apiFetch('/notificaciones');

    // Contar no leídas
    const unreadCount = notifs.filter((n) => !n.leida).length;
    const badge = document.querySelector('.bell-badge');
    if (badge) {
      if (unreadCount > 0) {
        badge.textContent = unreadCount > 9 ? '9+' : unreadCount;
        badge.style.display = 'flex';
      } else {
        badge.style.display = 'none';
      }
    }

    // Mostrar toasts y notificaciones de escritorio para nuevas no leídas
    const newIds = new Set();
    notifs.forEach((n) => {
      newIds.add(n.id);
      if (!n.leida && !lastNotifIds.has(n.id)) {
        // Si no es la primera carga de la página, mostrar toast y desktop notification
        if (!isFirstLoad) {
          if (typeof showToast === 'function') {
            showToast(n.mensaje, 'info');
          }
          // Si la pestaña está en segundo plano o minimizada, lanzar notificación del OS
          if (
            document.hidden &&
            'Notification' in window &&
            Notification.permission === 'granted'
          ) {
            new Notification(n.titulo, {
              body: n.mensaje,
              tag: n.id,
            });
          }
        }
      }
    });

    lastNotifIds = newIds;
    isFirstLoad = false;
  } catch (e) {
    console.error('Error al consultar notificaciones:', e);
  }
}

async function renderNotificacionesLista() {
  const listContainer = document.getElementById('_notifList');
  if (!listContainer) return;

  try {
    const notifs = await apiFetch('/notificaciones');
    if (notifs.length === 0) {
      listContainer.innerHTML = '<div class="notif-empty">No tienes notificaciones.</div>';
      return;
    }

    listContainer.innerHTML = notifs
      .map((n) => {
        const relativeTime =
          typeof timeAgo === 'function' ? timeAgo(n.created_at) : 'Hace un momento';
        return `
                <div class="notif-item ${n.leida ? '' : 'unread'}" onclick="abrirNotificacion('${n.id}', '${n.incidencia_id}')">
                    <div class="notif-item-title">${n.titulo}</div>
                    <div class="notif-item-body">${n.mensaje}</div>
                    <div class="notif-item-time">${relativeTime}</div>
                </div>
            `;
      })
      .join('');
  } catch (e) {
    listContainer.innerHTML = '<div class="notif-empty">Error al cargar notificaciones.</div>';
  }
}

window.abrirNotificacion = async function (id, incidenciaId) {
  try {
    await apiFetch(`/notificaciones/${id}/leer`, { method: 'PATCH' });
  } catch (e) {
    console.error('Error al marcar notificación como leída:', e);
  }

  const panel = document.getElementById('_notifPanel');
  if (panel) panel.style.display = 'none';

  // Determinar prefijo de página
  const prefix = _navPrefix();
  window.location.href = `${prefix}pages/incidencias/detalle.html?id=${incidenciaId}`;
};

window.marcarTodasLasNotificacionesLeidas = async function () {
  try {
    await apiFetch('/notificaciones/leer-todas', { method: 'POST' });
    showToast('Todas las notificaciones marcadas como leídas', 'success');

    // Ocultar badge
    const badge = document.querySelector('.bell-badge');
    if (badge) badge.style.display = 'none';

    // Re-render
    renderNotificacionesLista();
  } catch (e) {
    showToast('No se pudieron marcar las notificaciones como leídas', 'error');
  }
};

document.addEventListener('DOMContentLoaded', async () => {
  await checkRoutePermission();

  let nav = document.querySelector('.bottom-nav');

  if (getToken()) {
    if (!nav) {
      nav = document.createElement('nav');
      nav.className = 'bottom-nav';
      document.body.appendChild(nav);
    }

    _renderNavLinks(nav);
    revisarComunicadosNuevos();

    if (window.innerWidth >= 768) {
      document.body.style.paddingLeft = '240px';
      document.body.style.paddingBottom = '0';
    }

    // Configurar UI de notificaciones e iniciar sondeo/polling
    setupNotificationsUI();
    await fetchNotificaciones();

    // Sondeo inteligente cada 5 segundos (casi instantáneo)
    setInterval(fetchNotificaciones, 5000);
  }
});
