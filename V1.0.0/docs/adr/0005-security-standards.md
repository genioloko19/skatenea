# ADR 0005 — Estándares de seguridad y cumplimiento

**Estado:** aceptado  
**Fecha:** 2026-11-05

## Decisión

Adoptar como línea base:

- OWASP ASVS 4.0 nivel 2.
- OWASP Top 10 (2021) y OWASP API Security Top 10 (2023).
- ISO/IEC 25010 para calidad.
- WCAG 2.2 AA para accesibilidad.
- Ley 1581 de 2012 y Decreto 1377 de 2013 para datos personales.
- Ley 643 de 2001 y lineamientos de Coljuegos para rifas.

## Controles mínimos

- Consultas preparadas en el 100 % de la persistencia.
- Validación de entrada y escape de salida.
- Autorización por rol y por propietario.
- CSRF en operaciones con cookie; JWT en cabecera para API.
- Rate limiting y bloqueo progresivo.
- Secretos en variables de entorno; rotación documentada.
- TLS obligatorio en producción.
- Auditoría append-only de operaciones críticas.
- Copias de seguridad cifradas y restauración probada.
- Revisión de dependencias (`composer audit`, `npm audit`) en CI.
- Escaneo de secretos (`gitleaks`) en CI.

## Consecuencias

- Ninguna fase se considera terminada sin evidencia de estos controles.
- Los hallazgos críticos/altos bloquean el release.
- La documentación legal se revisa con asesoría jurídica antes de producción.
