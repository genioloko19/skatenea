# ADR 0004 — Sistema de diseño: 12 temas × 3 modos

**Estado:** aceptado  
**Fecha:** 2026-11-05

## Contexto

Se requiere una UI moderna, divertida y accesible, con glassmorphism, geometrías variables, fondos dinámicos y alto contraste.

## Decisión

- Tokens CSS en `cliente/src/styles/tokens.css`.
- Atributos en `<html>`:
  - `data-theme="aurora|neon|sunset|ocean|forest|candy|cyber|royal|terracotta|mono|tropical|galaxy"`.
  - `data-mode="light|dark|contrast"`.
  - `data-geometry="soft|sharp|pill|organic"`.
  - `data-background="aurora|mesh|waves|orbs|grid|static"`.
- 12 temas × 3 modos = 36 combinaciones.
- Glassmorphism con `backdrop-filter` y superficies traslúcidas; en alto contraste se desactiva la transparencia.
- Fondos animados solo con CSS/SVG, respetando `prefers-reduced-motion`.
- Persistencia: `user_preferences` en el servidor + `localStorage` como caché.
- Componentes con ARIA y foco visible.

## Consecuencias

- Un solo conjunto de componentes sirve a todos los temas.
- Las pruebas de contraste se automatizan en CI (fase 9).
- El alto contraste no es un tema extra: es un modo transversal.
