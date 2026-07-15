/**
 * colors.js
 * Centralización de la paleta de colores de la aplicación.
 * Proporciona el objeto global APP_COLORS para JavaScript e inyecta
 * dinámicamente las variables de CSS en el documento.
 */

const APP_COLORS = {
    // Paleta Base
    orange:   '#F97316',
    orangeD:  '#EA6C0A',
    blue:     '#3B82F6',
    green:    '#22C55E',
    red:      '#EF4444',
    
    // Tonos Neutros / Grises
    gray50:   '#F9FAFB',
    gray100:  '#F3F4F6', // Fondo principal
    gray200:  '#E5E7EB', // Bordes
    gray400:  '#9CA3AF',
    gray700:  '#374151',
    gray900:  '#111827',
    white:    '#FFFFFF',
    
    // Fondos de estado/interacción (alertas, hovers, botones)
    bgOrangeLight: '#FFF7ED', // Hover / seleccionado naranja
    bgRedLight:    '#FEF2F2', // Fondo alerta / rechazo rojo
    bgGreenLight:  '#F0FDF4', // Fondo verde resuelto
    bgBlueLight:   '#EFF6FF', // Fondo azul pendiente/recibido
    
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

// Inyectar automáticamente las variables CSS
(function() {
    const root = document.documentElement;
    const colors = {
        '--orange': APP_COLORS.orange,
        '--orange-d': APP_COLORS.orangeD,
        '--blue': APP_COLORS.blue,
        '--green': APP_COLORS.green,
        '--red': APP_COLORS.red,
        '--gray-50': APP_COLORS.gray50,
        '--gray-100': APP_COLORS.gray100,
        '--gray-200': APP_COLORS.gray200,
        '--gray-400': APP_COLORS.gray400,
        '--gray-700': APP_COLORS.gray700,
        '--gray-900': APP_COLORS.gray900,
        '--white': APP_COLORS.white,
        
        // Fondos claros / Variables de estado
        '--bg-orange-light': APP_COLORS.bgOrangeLight,
        '--bg-red-light': APP_COLORS.bgRedLight,
        '--bg-green-light': APP_COLORS.bgGreenLight,
        '--bg-blue-light': APP_COLORS.bgBlueLight,
        
        // Variables de Rol
        '--role-admin': APP_COLORS.roles.ADMIN,
        '--role-supervisor': APP_COLORS.roles.SUPERVISOR,
        '--role-tecnico': APP_COLORS.roles.TECNICO,
        '--role-ciudadano': APP_COLORS.roles.CIUDADANO
    };
    for (const [key, val] of Object.entries(colors)) {
        root.style.setProperty(key, val);
    }
})();
