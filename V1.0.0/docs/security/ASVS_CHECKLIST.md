# Checklist ASVS 4.0 — nivel 2

Marcar solo con evidencia verificable. Estado inicial de V1.0.0.

## V1 — Arquitectura

- [x] Separación de capas y responsabilidades.
- [x] Cliente y servidor desacoplados.
- [x] Contrato OpenAPI.
- [x] Dependencias auditadas.
- [ ] Diagrama de despliegue firmado por operaciones.

## V2 — Autenticación

- [x] Hash de contraseñas con `password_hash`.
- [x] Política de contraseñas en servidor.
- [x] Bloqueo temporal por intentos fallidos.
- [x] Rate limiting de login.
- [x] Respuestas genéricas ante credenciales inválidas.
- [ ] MFA para super administradores.

## V3 — Sesión

- [x] JWT de corta duración.
- [x] Refresh rotativo y revocable.
- [x] Cookies HttpOnly/SameSite/Secure configurables.
- [x] CSRF en refresh.
- [x] Logout revoca refresh.
- [ ] Prueba formal de fijación de sesión.

## V4 — Control de acceso

- [x] RBAC por permisos.
- [x] Ownership en rifas, boletas y compradores.
- [x] Pruebas de autorización (vendedor vs. boleta ajena).
- [x] Rutas administrativas protegidas.
- [ ] Pruebas E2E de todos los roles en CI.

## V5 — Validación

- [x] Validator central.
- [x] PDO preparado.
- [x] Escape de salida en React y manual en PHP.
- [x] Límites de longitud y tipos.
- [ ] Fuzzing de endpoints.

## V6 — Criptografía

- [x] Secretos en `.env`.
- [x] HMAC SHA-256 para webhooks.
- [x] Hash de refresh/reset tokens.
- [ ] TLS de MySQL en producción.
- [ ] Rotación documentada de `JWT_SECRET`.

## V7 — Errores y logs

- [x] Errores genéricos en producción.
- [x] `X-Request-Id`.
- [x] Auditoría de operaciones críticas.
- [ ] Alertas y SIEM.

## V8 — Protección de datos

- [x] Minimización de PII.
- [x] QR sin PII.
- [x] Exportaciones autenticadas.
- [ ] Anonimización programada.
- [ ] Consentimiento explícito en registro.

## V9 — Comunicaciones

- [x] CORS por lista blanca.
- [x] Guía TLS/HSTS.
- [ ] Certificado y HSTS desplegados.
- [ ] TLS de MySQL desplegado.

## V10 — Código malicioso

- [x] Sin uploads de usuario.
- [x] CSP de API.
- [x] Dependencias auditadas.
- [ ] CSP de frontend desplegada.

## V11 — Lógica de negocio

- [x] Flujo de aprobación.
- [x] Transiciones de estado.
- [x] Pago con monto del servidor.
- [x] Sorteo con `random_int`.
- [ ] Pruebas de concurrencia automatizadas.

## V12 — Archivos

- [x] PDF generado en servidor.
- [x] Sin rutas de usuario para escritura.
- [x] Impresión en cola.
- [ ] Validar tamaño/tiempo de PDFs.

## V13 — API

- [x] OpenAPI 3.1.
- [x] Versionado `/api/v1`.
- [x] JWT y API keys.
- [x] Rate limit.
- [x] Errores consistentes.

## V14 — Configuración

- [x] `.env` fuera de Git.
- [x] Headers de seguridad.
- [x] CORS restringido.
- [x] `display_errors=0` en producción.
- [ ] Escaneo de secretos en CI.
