# Modelo de amenazas — SKATENEA V1.0.0

Metodología STRIDE sobre los activos principales.

## Activos

| Activo | Impacto si se compromete |
|---|---|
| Credenciales y sesiones | Suplantación, fraude |
| Datos personales de compradores | Sanción legal, phishing |
| Boletas y pagos | Fraude económico, descuadre |
| Rifas y aprobaciones | Operación no autorizada |
| Auditoría | Pérdida de trazabilidad |
| API keys y webhooks | Acceso no autorizado, SSRF |
| Correo y PDF | Phishing, suplantación |

## Actores

- Visitante anónimo.
- Comprador registrado.
- Vendedor.
- Líder.
- Super administrador.
- Integración con API key.
- Atacante externo o interno.

## STRIDE

| Amenaza | Escenario | Control |
|---|---|---|
| **S**poofing | Robo de JWT o refresh | Token corto, HttpOnly, rotación, revocación, lockout |
| **T**ampering | Modificar boleta, pago o rifa | RBAC + ownership + auditoría + transiciones condicionales |
| **R**epudiation | Negar una venta/anulación | Auditoría append-only con IP/user-agent |
| **I**nformation disclosure | Enumerar PII | BOLA mitigado, QR sin PII, rate limit |
| **D**enial of service | Fuerza bruta, spam de correos/webhooks | Rate limit, colas, reintentos, límites |
| **E**levation of privilege | Vendedor aprobando rifas | RBAC por permiso y pruebas |
| SSRF | Webhook a `localhost` o metadata cloud | Validación de URL; allowlist pendiente |
| XSS | Campos de comprador | Escape de salida, React, CSP |
| SQLi | Filtros de búsqueda | PDO preparado |
| CSRF | Refresh con cookie | CSRF double-submit, SameSite |
| Race condition | Doble venta | UPDATE condicional por estado |
| Abuso de correo | Envío masivo | Rate limit de recuperación, cola |

## Riesgos residuales

1. Webhooks permiten URLs internas; implementar allowlist/deny private IPs.
2. No hay MFA para super admin.
3. La verificación de edad/identidad del comprador es manual.
4. La autorización estatal de la rifa es externa al software.
5. La impresión térmica depende de la red/servicio local.

## Controles compensatorios

- Monitoreo de 401/403/429 y de entregas de webhook fallidas.
- Revisión periódica de auditoría.
- Rotación de secretos y API keys.
- Backups cifrados y restauración probada.
