/**
 * detalle-shared.js
 * Funciones comunes a todas las páginas de detalle de incidencia.
 * Elimina la duplicación entre admin/detalle.html, supervisor/detalle.html, tecnico/detalle.html.
 *
 * Requiere: app.js, auth.js, catalogos.js, modal.js, Leaflet
 */

// ── Builders de HTML ───────────────────────────────────────────────────────

/**
 * Genera el HTML para las evidencias adjuntas.
 */
function buildEvidencias(evidencias) {
    if (!evidencias?.length) return '';
    return `
    <div class="divider"></div>
    <div class="section">
        <div class="section-title">Evidencia adjunta</div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:8px">
            ${evidencias.map(e => `
                <a href="${e.ruta}" target="_blank"
                   style="border:1.5px solid var(--gray-200);border-radius:10px;overflow:hidden;
                          width:100px;height:100px;display:inline-block">
                    <img src="${e.ruta}" alt="${e.nombre_archivo}"
                         style="width:100%;height:100%;object-fit:cover">
                </a>
            `).join('')}
        </div>
    </div>`;
}

/**
 * Genera el HTML del historial de cambios de estado.
 */
function buildHistorial(historial) {
    if (!historial?.length) {
        return '<div class="text-muted-sm">Sin cambios de estado aún.</div>';
    }
    return historial.map(h => `
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;
                    padding:10px 12px;background:var(--gray-50);border-radius:10px">
            <i class="bi bi-arrow-right-circle" style="color:var(--orange);font-size:16px"></i>
            <div>
                <div style="font-size:13px;font-weight:600">
                    ${h.estado_anterior || '—'} → ${h.estado_nuevo}
                </div>
                <div class="text-muted-sm">
                    ${formatDate(h.fecha_cambio || h.fecha_hora)} · 
                    ${h.tecnico_id ? `Por: ${h.tecnico_nombre || h.tecnico_id}` : 'Por: Supervisor/Admin'}
                </div>
                ${h.observacion && h.observacion !== 'Sin observaciones.'
                    ? `<div style="font-size:12px;color:#4B5563;margin-top:2px">${h.observacion}</div>`
                    : ''}
            </div>
        </div>`).join('');
}

/**
 * Genera el HTML de la lista de comentarios/notas.
 */
function buildComentarios(lista) {
    if (!lista?.length) return '<div class="text-muted-sm">Sin notas aún.</div>';
    return lista.map(c => `
        <div style="margin-bottom:8px;padding:10px 12px;background:var(--gray-50);border-radius:10px">
            <div class="text-muted-sm" style="margin-bottom:2px">
                ${timeAgo(c.fecha_creacion || c.created_at)}
            </div>
            <div style="font-size:13px;color:#374151">${c.texto}</div>
        </div>`).join('');
}

// ── Mapa mini ──────────────────────────────────────────────────────────────

/**
 * Inicializa el mapa Leaflet mini estático.
 * @param {[number, number]} coords - [lng, lat] en formato GeoJSON
 * @param {string} mapId - ID del contenedor del mapa (default: 'detalleMap')
 */
function initDetalleMap([lng, lat], mapId = 'detalleMap') {
    const m = L.map(mapId, { zoomControl: false, dragging: false, scrollWheelZoom: false });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OSM',
    }).addTo(m);
    m.setView([lat, lng], 15);
    L.marker([lat, lng]).addTo(m);
}

// ── Acciones comunes ───────────────────────────────────────────────────────

/**
 * Agrega una nota/comentario a la incidencia.
 * @param {string|number} incidenciaId
 * @param {Function}      onSuccess - callback() tras éxito (normalmente recargar)
 */
async function agregarNota(incidenciaId, onSuccess) {
    const texto = document.getElementById('notaInput')?.value.trim();
    if (!texto) return;
    try {
        await apiFetch(`/incidencias/${incidenciaId}/comentarios`, {
            method: 'POST',
            body: JSON.stringify({ texto }),
        });
        if (typeof onSuccess === 'function') onSuccess();
    } catch (e) {
        alert(e.message || 'Error al agregar nota.');
    }
}

/**
 * Elimina una incidencia con confirmación modal.
 * @param {string|number} incidenciaId
 * @param {string}        redirectUrl - URL a la que redirigir tras eliminar
 */
async function eliminarIncidencia(incidenciaId, redirectUrl = 'panel.html') {
    const confirmado = await showConfirmModal(
        'Eliminar incidencia',
        '¿Estás seguro de que deseas eliminar esta incidencia permanentemente? Esta acción no se puede deshacer.',
        'Eliminar',
        'btn-danger'
    );
    if (!confirmado) return;

    try {
        await apiFetch(`/incidencias/${incidenciaId}`, { method: 'DELETE' });
        window.location.href = redirectUrl;
    } catch (e) {
        alert(e.message || 'Error al eliminar.');
    }
}

/**
 * Rechaza la resolución de un técnico y la devuelve a "En Proceso".
 * @param {string|number} incidenciaId
 * @param {string}        rol - 'supervisor' | 'admin' (para el mensaje de auditoría)
 * @param {Function}      onSuccess
 */
async function rechazarResolucion(incidenciaId, rol, onSuccess) {
    const confirmado = await showConfirmModal(
        'Rechazar resolución',
        '¿Rechazar la resolución del técnico y devolver a "En proceso"?',
        'Rechazar',
        'btn-warning'
    );
    if (!confirmado) return;

    const motivo = await showPromptModal(
        'Motivo del rechazo',
        'Explica por qué rechazas la resolución (opcional)…'
    );
    const motivoFinal = motivo || `Resolución rechazada por ${rol}.`;

    try {
        await apiFetch(`/incidencias/${incidenciaId}/estado`, {
            method: 'POST',
            body: JSON.stringify({ estado_nuevo: 'En Proceso', observacion: motivoFinal }),
        });
        await apiFetch(`/incidencias/${incidenciaId}/comentarios`, {
            method: 'POST',
            body: JSON.stringify({ texto: `⚠️ Resolución rechazada: ${motivoFinal}` }),
        });
        if (typeof onSuccess === 'function') onSuccess();
    } catch (e) {
        alert(e.message || 'Error al rechazar.');
    }
}
