# Reporte de performance — SKATENEA V1.0.0

**Herramienta:** ApacheBench (`ab`)  
**Script:** `cliente/scripts/qa-perf.mjs`  
**Entorno:** PHP 8.2 built-in server, SQLite, Windows  
**Resultado:** 0 errores y 0 respuestas non-2xx.

| Endpoint | Solicitudes | Concurrencia | req/s | Tiempo medio | non-2xx |
|---|---:|---:|---:|---:|---:|
| `/api/v1/health` | 100 | 10 | 15.98 | 625.88 ms | 0 |
| `/api/v1/public/raffles` | 100 | 10 | 15.29 | 653.90 ms | 0 |

## Interpretación

El servidor embebido de PHP es **single-thread**; los valores son una línea base funcional, no una medición de producción. En staging se debe repetir con PHP-FPM, OPcache, MySQL y Nginx.

## Recomendaciones

- Desplegar PHP-FPM con workers ajustados a CPU/RAM.
- Activar OPcache y JIT si aplica.
- Índices en `tickets(raffle_id,status)`, `raffles(status)`, `audit_logs(created_at)`.
- Cachear rifas públicas y estadísticas.
- Paginar listados grandes.
- Medir p95/p99 con k6 en staging.
- Monitorear 429 y latencia de workers.
