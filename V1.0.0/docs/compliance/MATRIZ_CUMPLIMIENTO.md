# Matriz de cumplimiento — SKATENEA V1.0.0

**Fecha:** 2026-11-06  
**Estado:** línea base técnica; requiere revisión legal y auditoría externa antes de producción.

---

## 1. Resumen

| Marco | Nivel / alcance | Estado | Evidencia |
|---|---|---|---|
| OWASP ASVS 4.0 | Nivel 2 | 🟡 Parcial | `docs/security/ASVS_CHECKLIST.md` |
| OWASP Top 10 2021 | A01–A10 | 🟢 Controles base | API, RBAC, validación, auditoría |
| OWASP API Security Top 10 2023 | API1–API10 | 🟢 Controles base | JWT, API keys, rate limit, ownership |
| WCAG 2.2 AA | Cliente | 🟡 Parcial | Teclado, foco, contraste, ARIA; falta axe formal |
| ISO/IEC 25010 | Calidad | 🟡 Parcial | Pruebas, documentación, mantenibilidad |
| Ley 1581 de 2012 | Datos personales | 🟡 Parcial | Minimización, roles, política; falta revisión legal |
| Decreto 1377 de 2013 | Registro nacional de BD | 🔴 Pendiente | Requiere trámite ante SIC |
| Ley 643 de 2001 | Juegos de suerte y azar | 🟡 Parcial | Trazabilidad, sorteo, aprobación; falta concepto de Coljuegos |
| Coljuegos | Requerimientos técnicos | 🟡 Parcial | Flujo y auditoría; falta certificación |

## 2. Ley 1581 de 2012 — protección de datos personales

| Requisito | Implementación | Estado |
|---|---|---|
| Principio de finalidad | Datos usados para boletas, pago y contacto | 🟢 |
| Minimización | Solo datos necesarios; verificación QR sin PII | 🟢 |
| Consentimiento | Registro con aceptación de términos (pendiente en UI) | 🟡 |
| Derecho de acceso | Portal de comprador y perfil | 🟢 |
| Rectificación | Edición de perfil | 🟢 |
| Supresión | Desactivación de usuario; falta borrado/anonimización programada | 🟡 |
| Seguridad | RBAC, TLS recomendado, secretos fuera del código | 🟢 |
| Retención | Política documentada y job de mantenimiento | 🟢 |
| Encargados | Documento de encargados pendiente | 🔴 |
| Registro nacional de BD | Trámite ante SIC pendiente | 🔴 |
| Aviso de privacidad | Plantilla en `docs/legal/POLITICA_PRIVACIDAD.md` | 🟡 |

## 3. Ley 643 de 2001 y Coljuegos

| Requisito | Implementación | Estado |
|---|---|---|
| Operador autorizado | Validación externa (fuera del software) | 🔴 |
| Rifas con autorización | Flujo de aprobación interna; no reemplaza autorización estatal | 🟡 |
| Transparencia del sorteo | `random_int`, semilla, hash, auditoría | 🟢 |
| Boletas emitidas en serie | `UNIQUE(raffle_id, number)` | 🟢 |
| Precio fijo | `ticket_price` en rifa; pago toma el valor de la boleta | 🟢 |
| Trazabilidad | Auditoría append-only y estados | 🟢 |
| Premios en especie | Campo `prize` y acta de sorteo | 🟡 |
| Reportes a autoridad | Export CSV/PDF; formato oficial pendiente | 🟡 |
| Prohibición de menores | Verificación de edad/identidad pendiente | 🔴 |
| Publicidad responsable | Textos legales pendientes | 🟡 |

> La autorización, el registro ante Coljuegos y la revisión de la modalidad de rifa son responsabilidad del operador. El software no sustituye esos trámites.

## 4. OWASP ASVS 4.0 (resumen)

| Capítulo | Control | Estado |
|---|---|---|
| V1 Arquitectura | Capas, dominio, separación cliente/servidor | 🟢 |
| V2 Autenticación | Bcrypt/Argon2, lockout, política de contraseñas | 🟢 |
| V3 Sesión | JWT corto, refresh rotativo, cookies HttpOnly | 🟢 |
| V4 Control de acceso | RBAC + ownership | 🟢 |
| V5 Validación | Validator, prepared statements, escape | 🟢 |
| V6 Criptografía | Secretos en entorno, TLS recomendado, HMAC | 🟡 |
| V7 Errores/logs | Errores genéricos, request_id, auditoría | 🟢 |
| V8 Protección de datos | Minimización, PII fuera del QR | 🟢 |
| V9 Comunicaciones | HTTPS/HSTS documentado; falta despliegue TLS | 🟡 |
| V10 Código malicioso | Sin uploads ejecutables, CSP | 🟡 |
| V11 Lógica de negocio | Aprobación, estados, concurrencia | 🟢 |
| V12 Archivos | Sin carga de archivos de usuario; PDF generado | 🟢 |
| V13 API | OpenAPI, versionado, auth, rate limit | 🟢 |
| V14 Configuración | `.env`, secretos, headers, CORS | 🟢 |

## 5. OWASP API Security Top 10 2023

| Riesgo | Control |
|---|---|
| API1 BOLA | Ownership en boletas/rifas |
| API2 Broken Auth | JWT, refresh rotativo, lockout |
| API3 BOPLA | DTOs explícitos, sin mass assignment |
| API4 Resource Consumption | Rate limit, límites de paginación |
| API5 BFLA | RBAC por endpoint |
| API6 Business Flow | Aprobación, estados, idempotencia parcial |
| API7 SSRF | Sin fetch de URLs de usuario salvo webhooks con allowlist pendiente |
| API8 Misconfiguration | Headers, CORS, errores genéricos |
| API9 Inventory | OpenAPI 3.1, rutas versionadas |
| API10 Unsafe Consumption | Dependencias auditadas, firmas HMAC |

## 6. Acciones pendientes antes de producción

- [ ] Revisión legal de política, términos y consentimiento.
- [ ] Registro de bases de datos ante la SIC (si aplica).
- [ ] Concepto/autorización de Coljuegos y validación de la modalidad.
- [ ] Verificación de edad e identidad para participantes.
- [ ] Pentest externo y cierre de hallazgos.
- [ ] TLS, HSTS, WAF y backups probados.
- [ ] Allowlist de destinos de webhooks (mitigar SSRF).
- [ ] Borrado/anonimización programada y ventana de retención.
- [ ] Certificación de accesibilidad WCAG 2.2 AA.
- [ ] MFA para super administradores (recomendado).
