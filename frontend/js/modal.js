/**
 * modal.js
 * Utilidades de modales reutilizables para toda la aplicación.
 * Requiere Bootstrap 5 ya cargado en la página.
 */

// ── Modal genérico ─────────────────────────────────────────────────────────

/**
 * Crea y muestra un modal Bootstrap dinámicamente.
 * @param {string} title      - Título del modal
 * @param {string} bodyHTML   - Contenido HTML del cuerpo
 * @param {string} footerHTML - HTML del footer (botones)
 * @returns {bootstrap.Modal} - Instancia del modal
 */
function showModal(title, bodyHTML, footerHTML = '') {
    // Eliminar modal anterior si existe
    document.getElementById('_dynModal')?.remove();

    const el = document.createElement('div');
    el.className = 'modal fade';
    el.id = '_dynModal';
    el.tabIndex = -1;
    el.setAttribute('aria-modal', 'true');
    el.setAttribute('role', 'dialog');
    el.innerHTML = `
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="border-radius:16px;border:none;box-shadow:0 20px 60px rgba(0,0,0,.18)">
                <div class="modal-header" style="border-bottom:1px solid var(--gray-200);padding:18px 20px">
                    <h6 class="modal-title fw-700" style="font-size:15px;color:var(--gray-900)">${title}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:20px">${bodyHTML}</div>
                ${footerHTML ? `<div class="modal-footer" style="border-top:1px solid var(--gray-200);padding:14px 20px">${footerHTML}</div>` : ''}
            </div>
        </div>`;

    document.body.appendChild(el);
    const modal = new bootstrap.Modal(el);
    modal.show();

    // Limpiar del DOM al cerrar
    el.addEventListener('hidden.bs.modal', () => el.remove());

    return modal;
}

// ── Modal de confirmación ──────────────────────────────────────────────────

/**
 * Modal de confirmación que retorna una Promise<boolean>.
 * @param {string} title    - Título
 * @param {string} message  - Mensaje de confirmación
 * @param {string} confirmLabel - Texto del botón confirmar (default: "Confirmar")
 * @param {string} confirmClass - Clase CSS del botón (default: "btn-danger")
 */
function showConfirmModal(title, message, confirmLabel = 'Confirmar', confirmClass = 'btn-danger') {
    return new Promise((resolve) => {
        const bodyHTML = `<p style="font-size:14px;color:var(--gray-700);margin:0">${message}</p>`;
        const footerHTML = `
            <button type="button" class="btn btn-light" data-bs-dismiss="modal"
                    style="font-size:13px;border-radius:10px">Cancelar</button>
            <button type="button" id="_confirmBtn" class="btn ${confirmClass}"
                    style="font-size:13px;border-radius:10px">${confirmLabel}</button>`;

        const modal = showModal(title, bodyHTML, footerHTML);

        document.getElementById('_confirmBtn').addEventListener('click', () => {
            modal.hide();
            resolve(true);
        });

        document.getElementById('_dynModal').addEventListener('hidden.bs.modal', () => {
            resolve(false);
        }, { once: true });
    });
}

// ── Lightbox de imagen (evidencia) ─────────────────────────────────────────

/**
 * Muestra solo la imagen a pantalla completa sobre un fondo oscuro, con una
 * X para cerrar. Sin título ni texto adicional (no reutiliza showModal()).
 * Se cierra con la X, clic fuera de la imagen, o la tecla Escape.
 * @param {string} ruta - URL de la imagen
 */
function showImageModal(ruta) {
    document.getElementById('_imgLightbox')?.remove();

    const overlay = document.createElement('div');
    overlay.id = '_imgLightbox';
    overlay.style.cssText = `
        position:fixed; inset:0; z-index:2000;
        background:rgba(0,0,0,.85);
        display:flex; align-items:center; justify-content:center;
        padding:24px;
    `;
    overlay.innerHTML = `
        <button type="button" aria-label="Cerrar" style="
            position:absolute; top:16px; right:16px; width:40px; height:40px;
            border-radius:50%; border:none; background:rgba(255,255,255,.15);
            color:#fff; font-size:22px; line-height:1; cursor:pointer;">
            &times;
        </button>
        <img src="${ruta}" style="max-width:100%;max-height:100%;border-radius:8px;
             box-shadow:0 20px 60px rgba(0,0,0,.4)">
    `;
    document.body.appendChild(overlay);

    const close = () => overlay.remove();
    overlay.querySelector('button').addEventListener('click', close);
    overlay.addEventListener('click', (e) => { if (e.target === overlay) close(); });
    document.addEventListener('keydown', function onEsc(e) {
        if (e.key === 'Escape') { close(); document.removeEventListener('keydown', onEsc); }
    });
}

// ── Modal de prompt de texto ───────────────────────────────────────────────

/**
 * Modal con un campo de texto. Retorna Promise<string|null>.
 * @param {string} title        - Título
 * @param {string} placeholder  - Placeholder del input
 * @param {string} confirmLabel - Texto del botón confirmar
 */
function showPromptModal(title, placeholder = '', confirmLabel = 'Aceptar') {
    return new Promise((resolve) => {
        const bodyHTML = `
            <textarea id="_promptInput" class="form-input" rows="3"
                placeholder="${placeholder}"
                style="width:100%;resize:vertical;font-size:14px"></textarea>`;
        const footerHTML = `
            <button type="button" class="btn btn-light" data-bs-dismiss="modal"
                    style="font-size:13px;border-radius:10px">Cancelar</button>
            <button type="button" id="_promptBtn" class="btn btn-warning"
                    style="font-size:13px;border-radius:10px;color:#fff">${confirmLabel}</button>`;

        const modal = showModal(title, bodyHTML, footerHTML);

        document.getElementById('_promptBtn').addEventListener('click', () => {
            const val = document.getElementById('_promptInput').value.trim();
            modal.hide();
            resolve(val || null);
        });

        document.getElementById('_dynModal').addEventListener('hidden.bs.modal', () => {
            resolve(null);
        }, { once: true });
    });
}

// ── Modal de asignación de técnico ────────────────────────────────────────

/**
 * Muestra un modal con la lista de técnicos activos y permite seleccionar uno.
 * Reemplaza el `prompt('UUID del técnico')` anterior.
 *
 * @param {string|number} incidenciaId - ID de la incidencia a asignar
 * @param {Function}      onSuccess    - callback() tras asignar correctamente
 */
async function showAsignarTecnicoModal(incidenciaId, onSuccess) {
    // Cargar técnicos
    let tecnicos = [];
    try {
        tecnicos = await apiFetch('/usuarios/tecnicos');
    } catch (e) {
        showToast('No se pudo cargar la lista de técnicos: ' + (e.message || 'Error de red'), 'error');
        return;
    }

    const bodyHTML = tecnicos.length === 0
        ? `<div class="text-muted-sm text-center py-3">No hay técnicos disponibles.</div>`
        : `
        <div style="font-size:13px;color:var(--gray-400);margin-bottom:12px">
            Selecciona el técnico al que deseas asignar esta incidencia:
        </div>
        <div id="_tecnicoLista" style="display:flex;flex-direction:column;gap:8px">
            ${tecnicos.map(t => `
                <div class="tecnico-card" data-uuid="${t.uuid}"
                     onclick="_seleccionarTecnico(this)"
                     style="display:flex;align-items:center;gap:12px;padding:12px 14px;
                            border:1.5px solid var(--gray-200);border-radius:12px;cursor:pointer;
                            transition:all .15s ease;background:var(--white)">
                    <div style="width:38px;height:38px;border-radius:50%;background:var(--orange);
                                display:flex;align-items:center;justify-content:center;
                                color:var(--white);font-weight:700;font-size:14px;flex-shrink:0">
                        ${(t.nombres || '?')[0].toUpperCase()}${(t.apellidos || '')[0]?.toUpperCase() || ''}
                    </div>
                    <div>
                        <div style="font-size:14px;font-weight:600;color:var(--gray-900)">
                            ${t.nombres} ${t.apellidos}
                        </div>
                        <div style="font-size:12px;color:var(--gray-400)">${t.nombre_usuario || t.correo_electronico}</div>
                    </div>
                    <div style="margin-left:auto;display:none" class="check-icon">
                        <i class="bi bi-check-circle-fill" style="color:var(--orange);font-size:18px"></i>
                    </div>
                </div>`).join('')}
        </div>`;

    const footerHTML = `
        <button type="button" class="btn btn-light" data-bs-dismiss="modal"
                style="font-size:13px;border-radius:10px">Cancelar</button>
        <button type="button" id="_asignarBtn" class="btn-orange"
                style="padding:9px 20px;font-size:13px;border-radius:10px;opacity:.5;pointer-events:none"
                disabled>
            <i class="bi bi-person-check"></i> Asignar
        </button>`;

    const modal = showModal('Asignar técnico', bodyHTML, footerHTML);

    let selectedUuid = null;

    window._seleccionarTecnico = function(el) {
        document.querySelectorAll('#_tecnicoLista .tecnico-card').forEach(c => {
            c.style.borderColor = 'var(--gray-200)';
            c.style.background  = 'var(--white)';
            c.querySelector('.check-icon').style.display = 'none';
        });
        el.style.borderColor = 'var(--orange)';
        el.style.background  = 'var(--bg-orange-light)';
        el.querySelector('.check-icon').style.display = 'block';
        selectedUuid = el.dataset.uuid;

        const btn = document.getElementById('_asignarBtn');
        if (btn) {
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.pointerEvents = 'auto';
        }
    };

    document.getElementById('_asignarBtn')?.addEventListener('click', async () => {
        if (!selectedUuid) return;
        try {
            await apiFetch(`/incidencias/${incidenciaId}`, {
                method: 'PUT',
                body: JSON.stringify({ asignado_a: selectedUuid }),
            });
            modal.hide();
            showToast('Técnico asignado correctamente', 'success');
            if (typeof onSuccess === 'function') onSuccess();
        } catch (e) {
            showToast(e.message || 'No se pudo asignar el técnico. Inténtalo de nuevo.', 'error');
        }
    });
}