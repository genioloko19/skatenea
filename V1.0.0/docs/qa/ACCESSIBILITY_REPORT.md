# Reporte de accesibilidad — SKATENEA V1.0.0

**Estándar:** WCAG 2.2 AA  
**Herramienta:** axe-core 4.x inyectado con Playwright  
**Script:** `cliente/scripts/qa-axe.mjs`  
**Resultado:** **0 violaciones graves/críticas** en 27 combinaciones (9 pantallas × 3 modos).  
**Evidencia:** `docs/qa/axe-result.json`

## Cobertura

| Pantalla | Claro | Oscuro | Alto contraste |
|---|---|---|---|
| Landing | ✅ | ✅ | ✅ |
| Login | ✅ | ✅ | ✅ |
| Recuperación | ✅ | ✅ | ✅ |
| Nueva contraseña | ✅ | ✅ | ✅ |
| Dashboard | ✅ | ✅ | ✅ |
| Rifas | ✅ | ✅ | ✅ |
| Boletas | ✅ | ✅ | ✅ |
| Usuarios | ✅ | ✅ | ✅ |
| Auditoría | ✅ | ✅ | ✅ |

## Correcciones aplicadas

1. **Contraste en modo claro:** `--primary`, `--secondary` y `--accent` se oscurecieron con `color-mix` para superar 4.5:1 en textos pequeños.
2. **Nombre accesible del logo público:** se eliminó un `aria-label` que ocultaba el texto visible.

## Controles manuales

- Navegación por teclado en menús, tabs y modales.
- `Escape` cierra modales y dropdowns.
- Foco visible y focus trap en modales.
- Enlace “Saltar al contenido”.
- `prefers-reduced-motion` y toggle de animaciones.
- Alto contraste sin transparencias.

## Pendiente

- Auditoría manual con lector de pantalla (NVDA/VoiceOver).
- Pruebas con zoom 200 % y 400 %.
- Certificación externa WCAG 2.2 AA.
