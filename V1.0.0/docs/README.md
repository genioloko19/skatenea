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
| [Manual de usuario](manual-usuario/MANUAL_USUARIO.md) | Operación por rol |
| [Plan de QA](qa/QA_PLAN.md) | Matriz y criterios |
| [Resultados de QA](qa/QA_RESULTS.md) | Evidencia de la ejecución |
| [Capturas](assets/screenshots/README.md) | Galería visual |

## Convenciones

- Fechas en UTC.
- Dinero en COP con dos decimales.
- JSON con sobre `{ data }` y errores `{ error }`.
- IDs UUID v4.
- Commits convencionales (`feat:`, `fix:`, `docs:`, `test:`, `chore:`).
