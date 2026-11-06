# Resultados de QA — SKATENEA V1.0.0

**Fecha:** 2026-11-06  
**Entorno:** local (PHP 8.2.12, Vite 8.3.6, Chrome headless, SQLite)  
**Estado:** QA funcional, E2E, accesibilidad y performance ejecutados.

---

## 1. Resumen

| Área | Resultado |
|---|---|
| Pruebas unitarias PHP | **20/20 OK**, 62 aserciones |
| Pruebas unitarias frontend | **8/8 OK** |
| PHPStan nivel 6 | **0 errores** |
| PHPCS PSR-12 | **0 errores** |
| Smoke UI | **47/47 checks OK** |
| E2E funcional por roles | **23/23 checks OK** |
| Accesibilidad axe-core (WCAG 2.2 AA) | **0 violaciones graves/críticas** |
| Performance API (ApacheBench) | 15–16 req/s con PHP built-in, 0 errores |
| Capturas generadas | **46** + capturas E2E |
| OpenAPI | **56 rutas** parseadas |
| Errores de consola en smoke | **0** |
| Errores de página en smoke | **0** |
| `composer audit` | Sin avisos |
| `npm audit --omit=dev` | 0 vulnerabilidades |

## 2. Comandos ejecutados

```powershell
cd servidor
composer lint
composer stan
composer test
composer audit

cd ..\cliente
npm run lint
npm run test
npm run build
npm run qa:smoke
npm run qa:e2e
npm run qa:axe
npm run qa:perf
```

## 3. Smoke UI

```
QA smoke: 47/47 checks OK
Errores de consola: 0
Errores de página: 0
```

Cubre login, dashboard, panel de control, rifas, detalle, boletas, usuarios, auditoría y 36 combinaciones tema × modo.

## 4. E2E por roles

Ver [`E2E_REPORT.md`](E2E_REPORT.md).

```
E2E: 23/23 checks OK
```

Flujo probado:

1. Login API de los cuatro roles.
2. Líder crea rifa y la envía a aprobación.
3. Super administrador aprueba desde la UI.
4. Líder activa, genera 20 boletas y asigna 10 a un vendedor.
5. Vendedor no accede a usuarios y no puede reservar boletas no asignadas.
6. Vendedor separa y paga una boleta desde la UI.
7. Worker de impresión procesa la boleta.
8. PDF con QR generado correctamente.
9. CSV de rifa exportado.
10. Líder recibe notificación de aprobación.
11. Comprador ve su boleta y la verifica por QR público.
12. Recuperación de contraseña completa y restauración.

## 5. Accesibilidad

Ver [`ACCESSIBILITY_REPORT.md`](ACCESSIBILITY_REPORT.md).

```
Axe: 0 hallazgos graves/críticos
```

Se corrigieron:

- Contraste insuficiente en modo claro (`--primary` más oscuro).
- Nombre accesible del logo público incluía texto no visible.

## 6. Performance

Ver [`PERFORMANCE_REPORT.md`](PERFORMANCE_REPORT.md).

| Endpoint | req/s | Tiempo medio | Errores |
|---|---:|---:|---:|
| `/health` | 15.98 | 625.88 ms | 0 |
| `/public/raffles` | 15.29 | 653.90 ms | 0 |

> Valores medidos con el servidor embebido de PHP (single-thread). No representan producción; sirven como línea base funcional.

## 7. Evidencia

- `docs/qa/smoke-result.json`
- `docs/qa/e2e-result.json`
- `docs/qa/axe-result.json`
- `docs/qa/perf-result.json`
- `docs/assets/screenshots/` (46 capturas + E2E)
- `docs/qa/RELEASE_CHECKLIST.md`

## 8. Defectos encontrados y corregidos

| ID | Defecto | Corrección |
|---|---|---|
| QA-001 | Pérdida de sesión al recargar | Refresh proactivo + deduplicación |
| QA-002 | Carrera de refresh en StrictMode | Bootstrap único |
| QA-003 | Cookies con `expires` incorrecto | `time() + ttl` |
| QA-004 | `max` numérico aplicado a teléfonos | Validación por tipo |
| QA-005 | 429 en QA visual | Límite de desarrollo ajustado |
| QA-006 | 404 favicon | `favicon.svg` |
| QA-007 | 401 inicial innecesario | Evitar `/me` sin cookie |
| QA-008 | SCRIPT_NAME rompía `.pdf`/`.csv` | Base path solo si termina en `.php` |
| QA-009 | Contraste insuficiente en modo claro | Tokens de color ajustados |
| QA-010 | Nombre accesible del logo | `aria-label` corregido |
| QA-011 | PDF sin QR | `mpdf/qrcode` instalado |
| QA-012 | Webhook SSRF | Validación de IP privada/reservada |

## 9. Pendiente para producción

- [ ] Despliegue con PHP-FPM/MySQL y prueba de carga real.
- [ ] Pentest externo ASVS L2.
- [ ] MFA para super administradores.
- [ ] Revisión legal Ley 1581 / Ley 643 / Coljuegos.
- [ ] Verificación de edad/identidad.
- [ ] Pruebas en Firefox/Safari/Edge.
- [ ] Certificación WCAG 2.2 AA por auditor externo.
- [ ] Prueba de restauración de backups.
- [x] `git push` y tag `v1.0.0` publicados.
- [ ] Release page con artefactos.

## 10. Conclusión

La versión 1.0.0 supera el QA funcional, E2E, de accesibilidad y de performance base. Está lista para **staging** y revisión de seguridad/legal, no para producción con dinero real hasta cerrar los pendientes anteriores.
