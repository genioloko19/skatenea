# ADR 0003 — Modelo multi-rifa con aprobación

**Estado:** aceptado  
**Fecha:** 2026-11-05

## Contexto

La versión alfa está pensada para una sola rifa. El nuevo requisito introduce super administrador, líderes, vendedores y compradores, con múltiples rifas simultáneas y aprobación central.

## Decisión

Estados de rifa:

```
draft → pending_approval → approved → active → closed → finished
                          ↘ rejected
active/approved → cancelled
```

- El **líder** crea la rifa en `draft` y la envía a `pending_approval`.
- El **super administrador** aprueba o rechaza (con motivo).
- Solo una rifa `active` acepta ventas.
- El cierre y el sorteo generan acta y semilla verificable.
- Las boletas tienen estados `available`, `reserved`, `paid`, `cancelled`, `winner`.
- Cada boleta pertenece a una rifa y puede tener vendedor y comprador.
- Un comprador puede existir sin cuenta (visitante) y luego vincularse a un usuario.

## Consecuencias

- Índices únicos `(raffle_id, number)` y `token` para QR.
- Toda transición se valida en el dominio y se audita.
- El dashboard y los reportes se agregan por rifa.
