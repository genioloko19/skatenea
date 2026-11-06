# Resultados de QA — SKATENEA V1.0.0

**Fecha:** 2026-11-06  
**Entorno:** local (PHP 8.2.12, Vite 8.3.6, Chrome headless, SQLite)  
**Estado:** smoke QA aprobado; QA complejo pendiente para fase 9.

---

## 1. Resumen

| Área | Resultado |
|---|---|
| Pruebas unitarias PHP | **20/20 OK**, 59 aserciones |
| PHPStan nivel 6 | **0 errores** |
| PHPCS PSR-12 | **0 errores** |
| Smoke E2E (Playwright) | **47/47 checks OK** |
| Errores de consola | **0** |
| Errores de página | **0** |
| Capturas generadas | **46** (10 pantallas + 36 temas/modos) |
| OpenAPI | 42 rutas parseadas correctamente |
| `composer audit` | Sin avisos |
| `npm audit` | Pendiente de ejecución formal |

## 2. Comandos ejecutados

```powershell
cd servidor
composer lint
composer stan
composer test
composer audit

cd ..\cliente
npx tsc --noEmit
npm run build
npm run qa:smoke
```

## 3. Resultados del smoke E2E

```
✓ Landing pública carga
✓ Login carga
✓ Login y dashboard
✓ Menú de usuario visible
✓ Dropdown con panel de control
✓ Panel de control abre
✓ Página de rifas carga
✓ Detalle de rifa carga
✓ Página de boletas carga
✓ Página de usuarios carga
✓ Página de auditoría carga
✓ Tema aurora / light, dark, contrast
✓ Tema neon / light, dark, contrast
✓ Tema sunset / light, dark, contrast
✓ Tema ocean / light, dark, contrast
✓ Tema forest / light, dark, contrast
✓ Tema candy / light, dark, contrast
✓ Tema cyber / light, dark, contrast
✓ Tema royal / light, dark, contrast
✓ Tema terracotta / light, dark, contrast
✓ Tema mono / light, dark, contrast
✓ Tema tropical / light, dark, contrast
✓ Tema galaxy / light, dark, contrast

QA smoke: 47/47 checks OK
Errores de consola: 0
Errores de página: 0
```

## 4. Pruebas de API realizadas manualmente

| Caso | Resultado |
|---|---|
| `GET /health` | 200, DB ok |
| Login super admin | 200, JWT + cookies |
| `GET /auth/me` | 200 |
| Refresh con cookie + CSRF | 200, token rotado |
| `GET /dashboard` | 200, métricas correctas |
| `GET /raffles` | 200, 1 rifa demo |
| Login vendedor | 200 |
| Reserva de boleta propia | 200, estado `reserved` |
| Pago de boleta propia | 200, estado `paid` |
| Reserva de boleta no asignada | **403** |
| API key con scope `raffles.read` | 200 en `/integrations/raffles` |
| API key sin token en `/users` | 401 |
| Rate limiting de login | 429 tras el límite |
| Logout | 204, cookies limpiadas |

## 5. Evidencia visual

Carpeta: `docs/assets/screenshots/`

- `01-public-landing.png`
- `02-login.png`
- `03-dashboard-admin.png`
- `04-control-panel.png`
- `05-control-panel-appearance.png`
- `06-raffles.png`
- `07-raffle-detail.png`
- `08-tickets.png`
- `09-users.png`
- `10-audit.png`
- `theme-<tema>-<modo>.png` × 36

## 6. Defectos encontrados y corregidos durante la fase

| ID | Defecto | Corrección |
|---|---|---|
| QA-001 | El cliente perdía la sesión al recargar | Refresh proactivo + deduplicación de refresh en `ApiClient` |
| QA-002 | Carrera de refresh en React StrictMode | Bootstrap único en `AuthContext` + promesa compartida |
| QA-003 | Cookies con `expires` calculado como TTL | `CookieService` ahora usa `time() + ttl` |
| QA-004 | Validator aplicaba `max` numérico a teléfonos/documentos | `min/max` solo numérico para `int`/`float` |
| QA-005 | 429 durante las 36 recargas de temas | Límite de desarrollo elevado (`RATE_LIMIT_MAX=3000`) |
| QA-006 | 404 por favicon | `favicon.svg` agregado |
| QA-007 | Refresh proactivo generaba 401 inicial | Se evita `/auth/me` sin cookie CSRF |
| QA-008 | API key fallaba con JSON con BOM en pruebas manuales | Documentado: enviar UTF-8 sin BOM |

## 7. Pendiente para cerrar fase 9

- [ ] Playwright E2E por los cuatro roles y flujos completos.
- [ ] Accesibilidad automatizada (axe-core) en 36 combinaciones.
- [ ] Pruebas de contraste AA/AAA automatizadas.
- [ ] Pruebas de carga y concurrencia (k6/ab) sobre venta de boletas.
- [ ] Pentest interno ASVS L2 y OWASP API Top 10.
- [ ] Pruebas de compatibilidad (Firefox, Safari, Edge).
- [ ] Pruebas de correo, QR, PDF e impresión (fase 7).
- [ ] Revisión legal Ley 1581 / Ley 643 / Coljuegos.
- [ ] Publicación de release y artefactos en GitHub.

## 8. Conclusión

La versión 1.0.0 cuenta con una base funcional sólida: autenticación JWT con refresh rotativo, RBAC, multi-rifa, aprobación, boletas, pagos, auditoría, API keys y una interfaz moderna con 12 temas y 3 modos. El smoke QA y la suite unitaria están en verde.

No obstante, **no se considera lista para producción** hasta completar las pruebas de seguridad, accesibilidad, rendimiento y cumplimiento legal de la fase 8–9.
