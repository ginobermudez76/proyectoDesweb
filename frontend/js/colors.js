/**
 * colors.js
 * Centralización de la paleta de colores + sistema de tema claro/oscuro.
 * - Inyecta variables CSS según el tema guardado en localStorage ('ui_theme').
 * - Expone APP_COLORS, getTheme(), setTheme(), toggleTheme().
 * - Inserta el switch de tema y el branding "Urbi." en la navegación.
 */

const APP_COLORS = {
    // Paleta Base (constante en ambos temas)
    orange:   '#F97316',
    orangeD:  '#EA6C0A',
    blue:     '#3B82F6',
    green:    '#22C55E',
    red:      '#EF4444',

    // Colores por Rol
    roles: {
        ADMIN:      '#5B21B6',
        SUPERVISOR: '#92400E',
        TECNICO:    '#1D4ED8',
        CIUDADANO:  '#065F46',
    },

    // Colores por Prioridad
    prioridades: {
        Urgente: '#EF4444',
        Alta:    '#EF4444',
        Media:   '#F97316',
        Normal:  '#F97316',
        Baja:    '#3B82F6',
    }
};

/* ===== TEMAS =====
   Los nombres --gray-* se mantienen por compatibilidad con todo el
   código existente: en oscuro se invierten (gray-900 = texto claro). */
const THEME_VARS = {
    light: {
        '--gray-50':  '#F9FAFB',
        '--gray-100': '#F3F4F6',
        '--gray-200': '#E5E7EB',
        '--gray-400': '#6B7280',
        '--gray-700': '#374151',
        '--gray-900': '#111827',
        '--white':    '#FFFFFF',
        '--bg':       '#F3F4F6',
        '--surface':  '#FFFFFF',
        '--surface-2':'#FFFFFF',
        '--hover':    '#F9FAFB',
        '--shadow':   '0 1px 3px rgba(0,0,0,.06)',
        '--accent-text': '#EA6C0A',
        '--accent-soft': '#FFF7ED',
        '--bg-orange-light': '#FFF7ED',
        '--bg-red-light':    '#FEF2F2',
        '--bg-green-light':  '#F0FDF4',
        '--bg-blue-light':   '#EFF6FF',
    },
    dark: {
        '--gray-50':  'rgba(255,255,255,.04)',
        '--gray-100': '#141A29',
        '--gray-200': 'rgba(255,255,255,.1)',
        '--gray-400': '#7C8598',
        '--gray-700': '#B8BFCC',
        '--gray-900': '#F3F5F9',
        '--white':    '#1B2233',
        '--bg':       '#0F1420',
        '--surface':  '#1B2233',
        '--surface-2':'#141A29',
        '--hover':    'rgba(255,255,255,.05)',
        '--shadow':   '0 1px 3px rgba(0,0,0,.35)',
        '--accent-text': '#FB923C',
        '--accent-soft': 'rgba(249,115,22,.15)',
        '--bg-orange-light': 'rgba(249,115,22,.15)',
        '--bg-red-light':    'rgba(248,113,113,.14)',
        '--bg-green-light':  'rgba(74,222,128,.14)',
        '--bg-blue-light':   'rgba(96,165,250,.14)',
    }
};

const THEME_KEY = 'ui_theme';

function getTheme() {
    return localStorage.getItem(THEME_KEY) || 'dark';
}

function applyTheme(theme) {
    const root = document.documentElement;
    root.dataset.theme = theme;
    const base = {
        '--orange': APP_COLORS.orange,
        '--orange-d': APP_COLORS.orangeD,
        '--blue': APP_COLORS.blue,
        '--green': APP_COLORS.green,
        '--red': APP_COLORS.red,
        '--role-admin': APP_COLORS.roles.ADMIN,
        '--role-supervisor': APP_COLORS.roles.SUPERVISOR,
        '--role-tecnico': APP_COLORS.roles.TECNICO,
        '--role-ciudadano': APP_COLORS.roles.CIUDADANO,
    };
    const vars = { ...base, ...THEME_VARS[theme] };
    for (const [key, val] of Object.entries(vars)) {
        root.style.setProperty(key, val);
    }
}

function setTheme(theme) {
    localStorage.setItem(THEME_KEY, theme);
    applyTheme(theme);
    document.querySelectorAll('.theme-toggle').forEach(_syncToggleUI);
}

function toggleTheme() {
    setTheme(getTheme() === 'dark' ? 'light' : 'dark');
}

function _syncToggleUI(el) {
    const dark = getTheme() === 'dark';
    el.classList.toggle('is-dark', dark);
}

/** HTML del switch de tema (usable en cualquier página). */
function themeToggleHTML(withLabel = true) {
    return `
    <button type="button" class="theme-toggle ${getTheme() === 'dark' ? 'is-dark' : ''}" onclick="toggleTheme()" aria-label="Cambiar tema">
        <i class="bi bi-sun theme-ico-sun"></i>
        <span class="theme-track"><span class="theme-knob"></span></span>
        <i class="bi bi-moon-stars-fill theme-ico-moon"></i>
        ${withLabel ? '<span class="theme-label">Modo oscuro</span>' : ''}
    </button>`;
}

// Aplicar tema inmediatamente (antes del primer paint)
applyTheme(getTheme());

/* ===== BRANDING + TOGGLE EN LA NAVEGACIÓN =====
   auth.js crea la .bottom-nav en DOMContentLoaded; reintenta hasta encontrarla. */
document.addEventListener('DOMContentLoaded', () => {
    let tries = 0;
    const iv = setInterval(() => {
        tries++;
        const nav = document.querySelector('.bottom-nav');
        if (nav) {
            clearInterval(iv);
            // Branding Urbi. en el sidebar de escritorio
            const brand = nav.querySelector('.nav-brand');
            if (brand && !brand.querySelector('.brand-mark')) {
                brand.innerHTML = `
                    <span class="brand-mark"><i class="bi bi-geo-alt-fill"></i></span>
                    <span>Urbi<span style="color:var(--orange)">.</span></span>`;
            }
            // Switch de tema antes del logout (visible solo en desktop vía CSS)
            if (!nav.querySelector('.theme-toggle')) {
                const divider = nav.querySelector('.nav-divider');
                const wrap = document.createElement('div');
                wrap.className = 'nav-theme-wrap';
                wrap.innerHTML = themeToggleHTML(true);
                if (divider && divider.nextSibling) nav.insertBefore(wrap, divider.nextSibling);
                else nav.appendChild(wrap);
            }
        } else if (tries > 40) {
            clearInterval(iv);
        }
    }, 50);
});
