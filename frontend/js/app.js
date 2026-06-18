// Configuración base de la API
const API_URL = '/api';

// Interceptor básico de ejemplo
async function apiFetch(endpoint, options = {}) {
    try {
        const response = await fetch(`${API_URL}${endpoint}`, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...options.headers
            }
        });
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error("Error en la petición a la API:", error);
        throw error;
    }
}

// Inicialización
document.addEventListener('DOMContentLoaded', () => {
    console.log("Frontend cargado y listo para comunicarse con el Backend.");
    
    // Ejemplo de cómo haríamos una llamada de prueba:
    // apiFetch('/test').then(data => console.log(data));
});
