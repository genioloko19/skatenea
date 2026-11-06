# SKATENEA V1.0.0 — Plan maestro de refactorización

**Estado:** en ejecución  
**Modalidad:** fases autónomas con puntos de control humanos  
**Arquitectura objetivo:** cliente/servidor completamente separados  
**Backend:** PHP 8.2+ · PDO · POO · SOLID · API REST versionada  
**Frontend:** React 19 + TypeScript + Vite · Glassmorphism · 12 temas × 3 modos  
**Base de datos:** MySQL 8+/MariaDB 10.6+ (desarrollo también con SQLite)  
**Repositorio:** https://github.com/genioloko19/skatenea.git

---

## 0. Principios rectores

1. **Seguridad por diseño:** ningún secreto en el código; mínimo privilegio; *fail closed*.
2. **SOLID y capas:** Dominio → Aplicación → Infraestructura → HTTP. El dominio no conoce PDO, HTTP ni React.
3. **Contratos primero:** OpenAPI antes de implementar; tipos compartidos y errores consistentes.
4. **Trazabilidad:** toda operación crítica genera auditoría inmutable.
5. **Accesibilidad:** WCAG 2.2 AA como mínimo; modo de alto contraste nativo.
6. **Privacidad:** minimización de datos, consentimiento, retención y derechos del titular (Ley 1581 de 2012).
7. **Transparencia de rifas:** sorteo verificable, estados auditables y aprobación de rifas por el super administrador.
8. **Calidad:** pruebas unitarias, integración, contrato, E2E y revisión de seguridad antes de cada release.
9. **Entrega continua:** cada fase termina con build reproducible, documentación actualizada y commit convencional.
10. **Compatibilidad:** no se toca `V0.0.1`; la nueva versión vive aislada en `V1.0.0`.

## 1. Estándares de referencia

| Área | Estándar / norma |
|---|---|
| Seguridad de aplicaciones | OWASP ASVS 4.0 nivel 2, OWASP Top 10 (2021), OWASP API Security Top 10 (2023) |
| Calidad de producto | ISO/IEC 25010 |
| Seguridad de la información | ISO/IEC 27001 (controles aplicables) |
| Accesibilidad | WCAG 2.2 AA, WAI-ARIA Authoring Practices |
| Datos personales | Ley 1581 de 2012 (Colombia), Decreto 1377 de 2013 |
| Juegos de suerte y azar | Ley 643 de 2001, resoluciones y lineamientos de Coljuegos |
| Autenticación | OAuth 2.0 / JWT (RFC 7519), buenas prácticas OWASP JWT |
| API | OpenAPI 3.1, JSON:API-like envelopes |
| Fechas y dinero | ISO 8601 UTC, decimales con escala 2 y moneda explícita |
| Git | Conventional Commits, SemVer, ramas `feat/*`, `fix/*`, `chore/*` |
| Documentación | Markdown + Diagramas Mermaid + capturas en `docs/assets/screenshots` |

## 2. Fases

### Fase 0 — Gobierno, repositorio y línea base
**Objetivo:** preparar el proyecto para ejecución autónoma y auditable.

Entregables:
- Estructura `V1.0.0/{servidor,cliente,bd,docs}`.
- ADR de stack, autenticación, multi-rifa y theming.
- `.gitignore`, `.editorconfig`, `.env.example`, plantillas de issues/PR.
- Matriz de riesgos y modelo de amenazas inicial.
- Convenciones de API, errores, commits y versionado.
- Pipeline local reproducible (`make`/scripts PowerShell).

Criterio de salida:
- `git status` limpio tras el commit inicial.
- `docs/adr` con las 5 decisiones fundacionales.
- No hay secretos reales en el árbol.

### Fase 1 — Base de datos y dominio
**Objetivo:** modelo multi-rifa, multi-usuario y trazable.

Entregables:
- Esquema portable (`bd/schema.sql`) y migraciones.
- Tablas: usuarios, refresh tokens, API keys, rifas, aprobaciones, vendedores por rifa, compradores, boletas, pagos, auditoría, preferencias, notificaciones, reseteos.
- Repositorios PDO con consultas preparadas y transacciones.
- Entidades de dominio inmutables y validaciones.
- Seeds de desarrollo y script de migración/seed.
- Pruebas de integridad referencial y estados.

Criterio de salida:
- `php bin/migrate.php` y `php bin/seed.php` funcionan en SQLite y MySQL.
- Cobertura de pruebas del dominio ≥ 80 % en las reglas críticas.

### Fase 2 — Núcleo de API
**Objetivo:** servidor HTTP desacoplado, consistente y observable.

Entregables:
- Front controller, router, request/response, contenedor, configuración, logs.
- Manejo global de errores con `request_id`, validación y envelopes JSON.
- CORS por lista blanca, cabeceras de seguridad, rate limiting.
- Healthcheck y endpoint de versión.
- OpenAPI 3.1 base.

Criterio de salida:
- `GET /api/v1/health` responde 200.
- Errores 4xx/5xx con estructura uniforme.
- Pruebas de contrato y de cabeceras.

### Fase 3 — Identidad, acceso y auditoría
**Objetivo:** autenticación y autorización de nivel productivo.

Entregables:
- Login con Argon2id/bcrypt, JWT de acceso corto y refresh rotativo en cookie HttpOnly.
- API keys con prefijo, hash, scopes, expiración y revocación.
- RBAC: `super_admin`, `leader`, `seller`, `buyer`.
- Middlewares de autenticación, permisos, API key y rate limit.
- Auditoría de login, cambios de rol, accesos y operaciones críticas.
- Recuperación de contraseña con token de un solo uso.

Criterio de salida:
- Pruebas de autorización por rol y por propietario.
- Token expirado/alterado/revocado rechazado.
- Sin secretos en logs.

### Fase 4 — Dominio de rifas, boletas y pagos
**Objetivo:** operación multi-rifa con aprobación y estados.

Entregables:
- Flujo: `draft → pending_approval → approved/rejected → active → closed → finished/cancelled`.
- Creación por líder, aprobación/rechazo por super administrador.
- Asignación de vendedores y generación de boletas.
- Estados de boleta: `available`, `reserved`, `paid`, `cancelled`, `winner`.
- Reserva con vencimiento, pago, anulación y recibo.
- Sorteo con semilla criptográfica, hash de compromiso y acta.
- Verificación pública por token aleatorio (sin PII).
- Reportes y dashboard por rol.

Criterio de salida:
- Pruebas de concurrencia: una boleta no se vende dos veces.
- Una rifa no se activa sin aprobación.
- Todo cambio de estado queda auditado.

### Fase 5 — Shell de cliente y sistema de diseño
**Objetivo:** interfaz moderna, semántica y accesible.

Entregables:
- Vite + React + TypeScript, router, cliente API tipado, auth context.
- Layout semántico: `<header>`, `<aside>`, `<main>`, `<footer>`, skip link, landmarks.
- Glassmorphism y fondos dinámicos.
- 12 temas × 3 modos (claro, oscuro, alto contraste).
- Geometrías variables y fondos conmutables.
- Header con dropdown: Panel de control, Perfil, Cerrar sesión.
- Modal de panel de control con pestañas clasificadas.
- Preferencias persistidas por usuario y en `localStorage` como fallback.
- Estados de carga, error, vacío y toasts.

Criterio de salida:
- Navegación por teclado completa.
- Contraste AA en los 36 modos/temas (alto contraste AAA en texto principal).
- `prefers-reduced-motion` respetado.
- Sin dependencias de UI que rompan la CSP.

### Fase 6 — Portales por rol
**Objetivo:** cada perfil opera lo que le corresponde.

Entregables:
- **Super administrador:** aprobación de rifas, usuarios, API keys, auditoría, configuración global, seguridad.
- **Líder:** crear/editar rifas, asignar vendedores y boletas, reportes.
- **Vendedor:** boletas asignadas, reserva, venta, pago, compradores.
- **Comprador:** rifas públicas, boletas propias, verificación QR.
- Portal público con rifas activas y transparencia.

Criterio de salida:
- Matriz de permisos cubierta por pruebas E2E.
- Ningún rol puede acceder a rutas de otro rol.

### Fase 7 — Integraciones y operación
**Objetivo:** llevar el sistema a operación real.

Entregables:
- Correo transaccional con plantillas.
- QR verificable y generación de PDF/recibo.
- Impresión térmica desacoplada (servicio/cola).
- Exportaciones CSV/XLSX y reportes.
- Webhooks firmados y notificaciones.
- Copias de seguridad, restauración y retención.

Criterio de salida:
- Simulación de fallo de correo/impresora sin bloquear la venta.
- Webhooks con firma HMAC y reintentos.

### Fase 8 — Endurecimiento y cumplimiento
**Objetivo:** cumplir ASVS L2, WCAG 2.2 AA y normativa local.

Entregables:
- Revisión ASVS y OWASP API Top 10 con evidencias.
- Pruebas de autorización, IDOR, CSRF, XSS, SSRF, inyección.
- Cifrado/TLS, gestión de secretos, rotación.
- Política de privacidad, términos, consentimiento y retención.
- Matriz de cumplimiento Ley 1581 / Ley 643 / Coljuegos.
- Plan de respuesta a incidentes y contactos.

Criterio de salida:
- Cero hallazgos críticos/altos abiertos.
- Informe de pentest interno y plan de remediación cerrado.

### Fase 9 — QA complejo, documentación y release
**Objetivo:** publicar V1.0.0 con evidencia.

Entregables:
- Plan y resultados de QA: unitarias, integración, contrato, E2E, accesibilidad, rendimiento, seguridad, compatibilidad.
- Cobertura y reportes (HTML).
- Capturas de pantalla de los 12 temas y modos.
- Manual de usuario y manual técnico.
- OpenAPI publicado y colección Postman/Insomnia.
- `CHANGELOG.md`, `SECURITY.md`, `CONTRIBUTING.md`, `LICENSE`.
- Release en GitHub con artefactos y notas.
- Repositorio remoto actualizado.

Criterio de salida:
- Pipeline verde.
- Documentación revisada.
- Tag `v1.0.0` y release publicado.

## 3. Protocolo de ejecución autónoma

Cada fase se ejecuta en este orden:

1. Leer el estado del repositorio y los ADR.
2. Implementar tareas de la fase en ramas `feat/fase-N-*`.
3. Ejecutar: lint, análisis estático, pruebas unitarias, integración y build.
4. Actualizar documentación y `CHANGELOG`.
5. Registrar evidencia (comandos, capturas, resultados).
6. Commit convencional y push a la rama.
7. Abrir PR con checklist de la fase.
8. **Detenerse** y solicitar revisión humana solo si:
   - se requiere una credencial o secreto real;
   - hay que ejecutar una migración destructiva en producción;
   - una prueba de seguridad falla de forma no trivial;
   - se debe publicar un release o cambiar configuración de infraestructura.

### Reglas de seguridad para el agente

- Nunca imprimir secretos en logs, commits o respuestas.
- Nunca ejecutar pruebas destructivas contra producción.
- Nunca desactivar controles de seguridad para “hacer pasar” una prueba.
- Toda migración debe ser reversible o tener respaldo.
- Los cambios de esquema se acompañan de migración y prueba de rollback.

## 4. Automatización local

| Comando | Propósito |
|---|---|
| `cd servidor && composer install` | Dependencias PHP |
| `php bin/migrate.php` | Crear/actualizar esquema |
| `php bin/seed.php` | Datos de desarrollo |
| `cd servidor && composer test` | Pruebas PHP |
| `cd servidor && composer lint` | Estilo y análisis |
| `cd cliente && npm install` | Dependencias frontend |
| `npm run dev` | Servidor de desarrollo Vite |
| `npm run build` | Build de producción |
| `npm run test` | Pruebas frontend |
| `npm run test:e2e` | E2E con Playwright (fase 9) |
| `npm run lint` | ESLint/TypeScript |

## 5. Matriz de roles (resumen)

| Capacidad | Super admin | Líder | Vendedor | Comprador |
|---|:---:|:---:|:---:|:---:|
| Aprobar/rechazar rifa | ✅ | — | — | — |
| Crear/editar rifa | ✅ | ✅ (propias) | — | — |
| Asignar vendedores/boletas | ✅ | ✅ (propias) | — | — |
| Reservar/vender/pagar boleta | ✅ | — | ✅ (asignadas) | — |
| Registrar comprador | ✅ | ✅ | ✅ | — |
| Ver todas las rifas | ✅ | ✅ (propias) | ✅ (asignadas) | ✅ (públicas) |
| Ver PII de compradores | ✅ | ✅ (propias) | ✅ (propias) | Solo la propia |
| Gestionar usuarios | ✅ | — | — | — |
| API keys / auditoría / configuración | ✅ | — | — | — |
| Ver sus boletas | ✅ | — | ✅ | ✅ |

## 6. Riesgos del programa y mitigación

| Riesgo | Impacto | Mitigación |
|---|---|---|
| Alcance excesivo en una sola fase | Retraso | Vertical slices y criterios de salida por fase |
| Deuda de seguridad en el frontend | Alto | CSP, sanitización, revisión de dependencias, pruebas E2E |
| Doble venta / concurrencia | Crítico | Transacciones, bloqueo pesimista, pruebas de carga |
| Multi-rifa mal modelada | Alto | ADR y pruebas de integridad antes de UI |
| Theming inaccesible | Medio | Tokens, contraste automatizado, modo alto contraste |
| Secretos filtrados | Crítico | `.env`, escaneo de secretos, rotación, CI |
| Normativa local incompleta | Alto | Revisión legal y matriz de cumplimiento en fase 8 |

## 7. Definición de terminado (global)

- [ ] Build reproducible en Linux/Windows.
- [ ] Pruebas unitarias, integración, contrato y E2E en verde.
- [ ] Cobertura ≥ 80 % en dominio y ≥ 70 % global.
- [ ] Sin hallazgos críticos/altos de seguridad.
- [ ] Accesibilidad WCAG 2.2 AA verificada.
- [ ] Manuales y OpenAPI actualizados.
- [ ] Sin secretos en el repositorio.
- [ ] Release `v1.0.0` con notas y artefactos.
