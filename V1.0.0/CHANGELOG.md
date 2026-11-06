# Changelog

Todas las novedades relevantes de SKATENEA.

El formato sigue [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/) y [SemVer](https://semver.org/lang/es/).

## [1.0.0] — 2026-11-06

### Añadido

- Arquitectura cliente/servidor separada en `servidor/`, `cliente/`, `bd/` y `docs/`.
- API REST `/api/v1` con autenticación JWT, refresh rotativo y API keys con scopes.
- RBAC de cuatro roles: super administrador, líder, vendedor y comprador.
- Modelo multi-rifa con aprobación central, asignación de vendedores, boletas y pagos.
- Estados de boleta: disponible, separada, pagada, anulada y ganadora.
- Sorteo con semilla criptográfica y hash de verificación.
- Auditoría append-only de operaciones críticas.
- Cliente React 19 + TypeScript + Vite.
- Sistema de diseño glassmorphism con 12 temas, 3 modos, 4 geometrías y 6 fondos.
- Panel de control modal con 12 pestañas.
- Header con dropdown de panel, perfil y cierre de sesión.
- HTML semántico, navegación por teclado y alto contraste.
- OpenAPI 3.1, manual técnico, manual de usuario y plan de QA.
- Pruebas unitarias PHPUnit, PHPStan, PHPCS y smoke E2E con Playwright.
- 46 capturas de evidencia visual.
- Correo transaccional en cola (`email_outbox`) con worker y plantillas.
- Recuperación de contraseña con token de un solo uso.
- Bloqueo temporal de cuenta por intentos fallidos.
- Webhooks con firma HMAC-SHA256, reintentos y mitigación SSRF.
- Impresión térmica desacoplada en cola (`print_jobs`) con worker.
- PDF de comprobante con QR verificable.
- Exportaciones CSV de rifas y auditoría.
- Notificaciones internas.
- Pruebas E2E por roles (23 checks), axe-core (0 graves) y performance base.
- Matriz de cumplimiento, modelo de amenazas, ASVS y documentos legales base.

### Corregido respecto a V0.0.1

- Credenciales fuera del código.
- Consultas preparadas y transacciones.
- Control de acceso por propietario.
- Validación de usuario activo en login.
- Token QR aleatorio.
- CSRF, rate limiting y cabeceras de seguridad.
- Auditoría y trazabilidad de anulaciones y aprobaciones.

### Pendiente

- Pentest externo ASVS L2 y MFA para super administradores.
- Revisión legal (Ley 1581 / Ley 643) y autorización de Coljuegos.
- Verificación de edad/identidad de participantes.
- Pruebas en Firefox/Safari/Edge y certificación WCAG externa.
- Despliegue en staging con PHP-FPM/MySQL y release en GitHub.
