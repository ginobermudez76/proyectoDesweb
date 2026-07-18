/**
 * catalogos.js
 * Carga, cachea y expone los catálogos del sistema desde GET /api/catalogos.
 * Reemplaza todos los objetos hardcodeados: ESTADO_MAP, PRIORIDAD_MAP, TIPOS.
 */

const CATALOGOS_CACHE_KEY = 'sys_catalogos';
let _catalogos = null;

/** Devuelve los catálogos cacheados o los carga desde la API */
async function getCatalogos() {
    if (_catalogos) return _catalogos;

    // Intentar sessionStorage primero
    try {
        const cached = sessionStorage.getItem(CATALOGOS_CACHE_KEY);
        if (cached) {
            _catalogos = JSON.parse(cached);
            return _catalogos;
        }
    } catch {}

    // Cargar desde API
    _catalogos = await apiFetch('/catalogos');
    try {
        sessionStorage.setItem(CATALOGOS_CACHE_KEY, JSON.stringify(_catalogos));
    } catch {}
    return _catalogos;
}

/** Invalida el caché (útil si el admin cambia catálogos) */
function invalidarCatalogos() {
    _catalogos = null;
    try { sessionStorage.removeItem(CATALOGOS_CACHE_KEY); } catch {}
}

// ── Helpers de estado ────────────────────────────────────────────────────────

/**
 * Retorna la info de un estado dado su código.
 * @param {string} codigo - 'Pendiente' | 'En Proceso' | 'Resuelta' | 'Rechazada'
 * @param {Array}  estados - array del catálogo (opcional, si ya lo tienes)
 */
function getEstadoInfo(codigo, estados = null) {
    const lista = estados || (_catalogos?.estados ?? []);
    const found = lista.find(e => e.codigo === codigo);
    return found ?? { codigo, label: codigo, css_class: 'badge-recibido', color_hex: '#6B7280' };
}

/**
 * Retorna la info de una prioridad dado su código.
 * @param {string} codigo - 'Urgente' | 'Alta' | 'Media' | 'Normal' | 'Baja'
 */
function getPrioridadInfo(codigo, prioridades = null) {
    const lista = prioridades || (_catalogos?.prioridades ?? []);
    const found = lista.find(p => p.codigo === codigo);
    return found ?? { codigo, label: codigo || 'Media', css_class: 'badge-media', color_hex: '#F97316' };
}

// ── Renderizadores de UI ─────────────────────────────────────────────────────

/**
 * Renderiza la grilla de tipos de incidencia en un contenedor.
 * @param {string}   containerId  - ID del elemento contenedor
 * @param {Function} onSelect     - callback(tipo) cuando se selecciona
 */
async function renderTiposGrid(containerId, onSelect) {
    const cats = await getCatalogos();
    const container = document.getElementById(containerId);
    if (!container) return;

    container.innerHTML = cats.tipos.map(t => `
        <div class="category-item" data-tipo-id="${t.id}" data-val="${t.nombre}"
             onclick="(window._onSelectTipo||function(){})(this)">
            <i class="bi ${t.icono_clase || 'bi-tag'}"></i>${t.nombre}
        </div>
    `).join('');

    window._onSelectTipo = function(el) {
        container.querySelectorAll('.category-item').forEach(i => i.classList.remove('selected'));
        el.classList.add('selected');
        if (typeof onSelect === 'function') onSelect(el.dataset.val, Number.parseInt(el.dataset.tipoId, 10));
    };
}

/**
 * Renderiza la grilla de subtipos filtrada por tipo.
 * @param {string}   containerId  - ID del elemento contenedor
 * @param {number}   tipoId       - ID del tipo seleccionado
 * @param {Function} onSelect     - callback(subtipo) cuando se selecciona
 */
async function renderSubtiposGrid(containerId, tipoId, onSelect) {
    const cats  = await getCatalogos();
    const container = document.getElementById(containerId);
    if (!container) return;

    const tipo = cats.tipos.find(t => t.id === tipoId);
    const subs = tipo?.subtipos ?? [];

    container.innerHTML = subs.map(s => `
        <div class="category-item" data-val="${s.nombre}"
             onclick="(window._onSelectSub||function(){})(this)">
            <i class="bi bi-tag"></i>${s.nombre}
        </div>
    `).join('');

    window._onSelectSub = function(el) {
        container.querySelectorAll('.category-item').forEach(i => i.classList.remove('selected'));
        el.classList.add('selected');
        if (typeof onSelect === 'function') onSelect(el.dataset.val);
    };
}

/**
 * Renderiza botones de selección de estado.
 * @param {string}   containerId   - ID del elemento contenedor
 * @param {string}   estadoActual  - código del estado actual
 * @param {boolean}  editable      - si false, los botones son solo visuales
 * @param {Function} onSelect      - callback(codigo) al seleccionar
 */
async function renderEstadosBtns(containerId, estadoActual, editable, onSelect) {
    const cats      = await getCatalogos();
    const container = document.getElementById(containerId);
    if (!container) return;

    const MAP_ACTIVE = {
        'Pendiente':  'active-recibido',
        'En Proceso': 'active-proceso',
        'Resuelta':   'active-resuelto',
        'Rechazada':  'active-urgente',
    };

    container.innerHTML = cats.estados.map(e => {
        const activeCls = estadoActual === e.codigo ? (MAP_ACTIVE[e.codigo] || 'active') : '';
        if (editable) {
            return `<button class="status-btn ${activeCls}"
                        onclick="(window._onSelEstado||function(){})(this,'${e.codigo}')"
                        data-val="${e.codigo}">${e.label}</button>`;
        } else {
            return `<div class="status-btn ${activeCls}" data-val="${e.codigo}">${e.label}</div>`;
        }
    }).join('');

    if (editable) {
        window._onSelEstado = function(el, codigo) {
            container.querySelectorAll('.status-btn').forEach(b => {
                b.className = b.className.replace(/active-\S+/, '').trim();
            });
            const activeCls = MAP_ACTIVE[codigo] || 'active';
            el.classList.add(activeCls);
            if (typeof onSelect === 'function') onSelect(codigo);
        };
    }
}

/**
 * Construye el HTML de un badge de prioridad a partir del catálogo.
 * Compatible con incidentCardHTML() de auth.js.
 */
function prioridadBadgeHTML(codigo) {
    const info = getPrioridadInfo(codigo);
    return `<span class="badge-status ${info.css_class}">${info.label || codigo || 'Media'}</span>`;
}

/**
 * Construye el HTML de un badge de estado.
 */
function estadoBadgeHTML(codigo) {
    const info = getEstadoInfo(codigo);
    return `<span class="badge-status ${info.css_class}">${info.label || codigo}</span>`;
}
