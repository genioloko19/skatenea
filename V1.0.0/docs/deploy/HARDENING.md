# Hardening de producción

## Aplicación

- `APP_ENV=production`, `APP_DEBUG=false`.
- Rotar `JWT_SECRET` y revocar sesiones tras el despliegue.
- Cookies `Secure`, `HttpOnly`, `SameSite=Strict`.
- CORS con lista blanca exacta.
- Rate limit por IP y por cuenta.
- `MAIL_DRIVER=smtp` con TLS y credenciales de aplicación.
- `PRINT_DRIVER` según infraestructura; nunca exponer la impresora a Internet.
- Webhooks solo a dominios autorizados (implementar allowlist antes de producción).
- Mantener `vendor/` y `node_modules/` fuera del webroot público.
- Ejecutar `composer audit` y `npm audit` en CI.

## Servidor

- TLS 1.2/1.3, HSTS, OCSP stapling.
- Cabeceras: CSP, X-Content-Type-Options, X-Frame-Options, Referrer-Policy, Permissions-Policy.
- PHP-FPM con usuario sin privilegios; `open_basedir` y funciones peligrosas deshabilitadas.
- `display_errors=0`, `log_errors=1`; logs fuera del webroot.
- Firewall: solo 443 público; MySQL/Redis/impresora en red privada.
- Fail2ban o WAF para fuerza bruta y escaneo.
- Backups cifrados, inmutables y probados.

## Base de datos

- Usuario de aplicación con `SELECT, INSERT, UPDATE, DELETE` únicamente.
- Sin `FILE`, `SUPER`, `PROCESS` ni acceso a otras bases.
- TLS y restricción por IP.
- Copias con `--single-transaction`.
- Monitoreo de tamaño, locks y errores.

## Operación

- Alarmas: 5xx, 401/403/429 anómalos, colas atascadas, webhooks fallidos, espacio en disco.
- Runbook de incidentes y contacto de guardia.
- Rotación de secretos cada 90 días o ante incidente.
- Revisión trimestral de accesos y API keys.
- Prueba de restauración trimestral.

## Pendiente antes de producción

- [ ] Allowlist de webhooks y bloqueo de IP privadas (SSRF).
- [ ] MFA para super administradores.
- [ ] Pentest externo.
- [ ] Revisión legal y autorización de Coljuegos.
- [ ] Certificación WCAG 2.2 AA.
