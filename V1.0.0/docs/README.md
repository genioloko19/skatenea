# Documentación — SKATENEA V1.0.0

| Documento | Descripción |
|---|---|
| [Plan maestro](plan/PLAN_MAESTRO.md) | Fases, estándares, autonomía y criterios de salida |
| [ADR 0001 — Stack](adr/0001-stack.md) | PHP 8.2 + PDO + capas |
| [ADR 0002 — Auth](adr/0002-auth-jwt.md) | JWT + refresh rotativo + API keys |
| [ADR 0003 — Multi-rifa](adr/0003-multiraffle.md) | Estados y aprobación |
| [ADR 0004 — Theming](adr/0004-theming.md) | 12 temas × 3 modos |
| [ADR 0005 — Seguridad](adr/0005-security-standards.md) | ASVS, WCAG, Ley 1581/643 |
| [OpenAPI](api/openapi.yaml) | Contrato de 42 rutas |
| [Manual técnico](manual-tecnico/MANUAL_TECNICO.md) | Arquitectura, seguridad, despliegue |
| [Integraciones](manual-tecnico/INTEGRACIONES.md) | Correo, webhooks, impresión, PDF y exports |
| [Manual de usuario](manual-usuario/MANUAL_USUARIO.md) | Operación por rol |
| [Plan de QA](qa/QA_PLAN.md) | Matriz y criterios |
| [Resultados de QA](qa/QA_RESULTS.md) | Evidencia de la ejecución |
| [E2E](qa/E2E_REPORT.md) | Flujo completo por roles |
| [Accesibilidad](qa/ACCESSIBILITY_REPORT.md) | Axe/WCAG 2.2 AA |
| [Performance](qa/PERFORMANCE_REPORT.md) | Línea base de carga |
| [Checklist de release](qa/RELEASE_CHECKLIST.md) | Criterios de salida |
| [Matriz de cumplimiento](compliance/MATRIZ_CUMPLIMIENTO.md) | ASVS, Ley 1581, Ley 643 |
| [Modelo de amenazas](security/MODELO_AMENAZAS.md) | STRIDE y riesgos |
| [Checklist ASVS](security/ASVS_CHECKLIST.md) | Nivel 2 |
| [Privacidad](legal/POLITICA_PRIVACIDAD.md) | Plantilla legal |
| [Términos](legal/TERMINOS_USO.md) | Plantilla legal |
| [Retención](legal/RETENCION_DATOS.md) | Política de datos |
| [Despliegue Nginx](deploy/NGINX.md) | API + cliente + workers |
| [Hardening](deploy/HARDENING.md) | Checklist de producción |
| [Backups](deploy/BACKUPS.md) | Respaldo y restauración |
| [Capturas](assets/screenshots/README.md) | Galería visual |

## Convenciones

- Fechas en UTC.
- Dinero en COP con dos decimales.
- JSON con sobre `{ data }` y errores `{ error }`.
- IDs UUID v4.
- Commits convencionales (`feat:`, `fix:`, `docs:`, `test:`, `chore:`).
