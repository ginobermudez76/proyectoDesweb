import http from 'k6/http';
import { check, sleep } from 'k6';

// Configuración de etapas de carga y umbrales SLA (Hito 7)
export const options = {
  stages: [
    { duration: '30s', target: 20 }, // Rampa de subida a 20 usuarios virtuales (VUs)
    { duration: '1m',  target: 50 }, // Pico de prueba de estrés a 50 VUs
    { duration: '30s', target: 0  }, // Enfriamiento y rampa de bajada
  ],
  thresholds: {
    http_req_duration: ['p(95)<800'], // 95% de las peticiones deben responder en menos de 800ms
    http_req_failed: ['rate<0.05'],   // Menos del 5% de tasa de fallo HTTP
  },
};

const BASE_URL = 'https://eldomoniodedesarrollo.dev/api';
// Token Bearer temporal para pruebas de rendimiento autenticadas
const TOKEN = 'qiAdFWEgMtxpUvmLzC1iEcXB6s5GLqXjFS7R5lcOwb3Gmc28FE1NPNrR3VV9';

export default function () {
  const authParams = {
    headers: {
      'Authorization': `Bearer ${TOKEN}`,
      'Content-Type': 'application/json',
    },
  };

  // Escenario 1: Consulta de Incidencias en el Mapa Georeferenciado
  let resIncidencias = http.get(`${BASE_URL}/incidencias`, authParams);
  check(resIncidencias, {
    'GET /api/incidencias es 200': (r) => r.status === 200,
    'Latencia mapa < 500ms': (r) => r.timings.duration < 500,
  });

  // Escenario 2: Consulta de Catálogos Públicos
  let resCatalogos = http.get(`${BASE_URL}/documentos/tipos`);
  check(resCatalogos, {
    'GET /api/documentos/tipos es 200': (r) => r.status === 200,
  });

  // Escenario 3: Perfil de Usuario Autenticado
  let resUser = http.get(`${BASE_URL}/user`, authParams);
  check(resUser, {
    'GET /api/user es 200': (r) => r.status === 200,
  });

  sleep(1);
}
