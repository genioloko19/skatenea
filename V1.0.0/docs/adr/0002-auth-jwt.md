# ADR 0002 — Autenticación JWT + refresh rotativo + API keys

**Estado:** aceptado  
**Fecha:** 2026-11-05

## Contexto

El cliente y el servidor están separados. Se necesita autenticación stateless para la API, sesiones revocables y acceso máquina-a-máquina para integraciones.

## Decisión

- **Access token JWT** firmado HS256, vida 15 minutos, claims `iss`, `aud`, `sub`, `role`, `jti`, `iat`, `nbf`, `exp`.
- **Refresh token** opaco de 64 bytes, almacenado como SHA-256 en base de datos, en cookie `HttpOnly`, `SameSite=Lax` (dev) / `Strict` (prod), rotativo y revocable.
- **CSRF double-submit** para el endpoint de refresh: cookie legible `csrf_token` + header `X-CSRF-Token`.
- **API keys** con prefijo público, hash SHA-256, scopes, expiración y revocación para integraciones.
- RBAC por permisos y verificación de propiedad en la capa de aplicación.
- Contraseñas con `password_hash(PASSWORD_DEFAULT)` y `password_needs_rehash`.

## Consecuencias

- El access token no se guarda en `localStorage`; se mantiene en memoria.
- El refresh token nunca se expone en JSON.
- Se requiere HTTPS en producción.
- La rotación invalida el token anterior y detecta reutilización.
