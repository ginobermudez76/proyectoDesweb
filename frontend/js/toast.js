/**
 * toast.js — Sistema de notificaciones toast para la aplicación.
 * Requiere que colors.js haya sido cargado primero (usa las variables CSS).
 *
 * Uso:
 *   showToast('Mensaje aquí')                    // info (azul)
 *   showToast('¡Guardado!', 'success')            // verde
 *   showToast('Algo salió mal', 'error')          // rojo
 *   showToast('Atención', 'warning')              // naranja
 */

(function () {
    // ── Inyectar estilos una sola vez ──────────────────────────────────────
    const STYLE_ID = '_toast_styles';
    if (!document.getElementById(STYLE_ID)) {
        const s = document.createElement('style');
        s.id = STYLE_ID;
        s.textContent = `
            #_toast_container {
                position: fixed;
                bottom: 24px;
                left: 50%;
                transform: translateX(-50%);
                z-index: 99999;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 10px;
                pointer-events: none;
                width: min(420px, 92vw);
            }
            ._toast {
                display: flex;
                align-items: flex-start;
                gap: 10px;
                padding: 13px 16px;
                border-radius: 14px;
                font-family: 'Inter', 'Segoe UI', sans-serif;
                font-size: 14px;
                font-weight: 500;
                line-height: 1.4;
                color: #fff;
                box-shadow: 0 8px 28px rgba(0,0,0,.18);
                pointer-events: all;
                width: 100%;
                cursor: pointer;
                animation: _toastIn .28s cubic-bezier(.34,1.56,.64,1) forwards;
                will-change: transform, opacity;
            }
            ._toast._toast-out {
                animation: _toastOut .22s ease-in forwards;
            }
            ._toast-icon {
                font-size: 18px;
                flex-shrink: 0;
                margin-top: 1px;
            }
            ._toast-text { flex: 1; }
            ._toast-close {
                font-size: 16px;
                opacity: .7;
                flex-shrink: 0;
                line-height: 1;
                margin-top: 1px;
            }
            ._toast-close:hover { opacity: 1; }

            /* Colores por tipo */
            ._toast-success { background: linear-gradient(135deg, #16a34a, #15803d); }
            ._toast-error   { background: linear-gradient(135deg, #dc2626, #b91c1c); }
            ._toast-warning { background: linear-gradient(135deg, #ea580c, #c2410c); }
            ._toast-info    { background: linear-gradient(135deg, #2563eb, #1d4ed8); }

            @keyframes _toastIn {
                from { opacity: 0; transform: translateY(24px) scale(.94); }
                to   { opacity: 1; transform: translateY(0)    scale(1);   }
            }
            @keyframes _toastOut {
                from { opacity: 1; transform: translateY(0)    scale(1);   }
                to   { opacity: 0; transform: translateY(10px) scale(.96); }
            }
        `;
        document.head.appendChild(s);
    }

    // ── Contenedor persistente ─────────────────────────────────────────────
    function getContainer() {
        let c = document.getElementById('_toast_container');
        if (!c) {
            c = document.createElement('div');
            c.id = '_toast_container';
            document.body.appendChild(c);
        }
        return c;
    }

    // ── Iconos por tipo ────────────────────────────────────────────────────
    const ICONS = {
        success: '<i class="bi bi-check-circle-fill _toast-icon"></i>',
        error:   '<i class="bi bi-x-circle-fill _toast-icon"></i>',
        warning: '<i class="bi bi-exclamation-triangle-fill _toast-icon"></i>',
        info:    '<i class="bi bi-info-circle-fill _toast-icon"></i>',
    };

    // ── API pública ────────────────────────────────────────────────────────
    /**
     * Muestra un toast.
     * @param {string} message  - Mensaje a mostrar
     * @param {'success'|'error'|'warning'|'info'} [type='info']
     * @param {number} [duration=3500] - ms antes de auto-cerrar
     */
    window.showToast = function (message, type = 'info', duration = 3500) {
        const container = getContainer();
        const el = document.createElement('div');
        el.className = `_toast _toast-${type}`;
        el.innerHTML = `
            ${ICONS[type] || ICONS.info}
            <span class="_toast-text">${message}</span>
            <span class="_toast-close">&#x2715;</span>
        `;

        const dismiss = () => {
            el.classList.add('_toast-out');
            el.addEventListener('animationend', () => el.remove(), { once: true });
        };

        el.addEventListener('click', dismiss);

        container.appendChild(el);

        const timer = setTimeout(dismiss, duration);

        // Cancelar auto-dismiss si el usuario hace hover
        el.addEventListener('mouseenter', () => clearTimeout(timer));
        el.addEventListener('mouseleave', () => setTimeout(dismiss, 1500));
    };
})();
