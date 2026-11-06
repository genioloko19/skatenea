# Reporte E2E — SKATENEA V1.0.0

**Herramienta:** Playwright + Chrome headless  
**Script:** `cliente/scripts/qa-e2e.mjs`  
**Resultado:** **23/23 checks OK**  
**Evidencia:** `docs/qa/e2e-result.json`

## Escenarios

| # | Escenario | Resultado |
|---|---|---|
| 1 | Login API de los cuatro roles | ✅ |
| 2 | Líder lista vendedores | ✅ |
| 3 | Líder crea rifa | ✅ |
| 4 | Líder envía a aprobación | ✅ |
| 5 | Admin ve rifa pendiente en UI | ✅ |
| 6 | Admin aprueba rifa | ✅ |
| 7 | Líder activa y genera 20 boletas | ✅ |
| 8 | Líder asigna vendedor | ✅ |
| 9 | Líder asigna rango 1–10 | ✅ |
| 10 | Vendedor recibe 403 en usuarios | ✅ |
| 11 | API rechaza reserva de boleta no asignada (403) | ✅ |
| 12 | Vendedor ve su boleta en UI | ✅ |
| 13 | Vendedor separa y paga boleta | ✅ |
| 14 | Worker de impresión procesa | ✅ |
| 15 | PDF de recibo válido (`%PDF`) | ✅ |
| 16 | CSV de rifa exportado | ✅ |
| 17 | Líder recibe notificación | ✅ |
| 18 | Comprador ve su boleta | ✅ |
| 19 | Comprador tiene token QR | ✅ |
| 20 | Página pública verifica | ✅ |
| 21 | Correo de recuperación encolado/enviado | ✅ |
| 22 | Contraseña temporal funciona | ✅ |
| 23 | Contraseña restaurada | ✅ |

## Datos creados

- Rifa QA con código `QA-<timestamp>`.
- 20 boletas; 10 asignadas al vendedor demo.
- Boleta #1 separada y pagada por el vendedor.
- Comprador demo vinculado por documento/teléfono.
- Notificación de aprobación para el líder.
- Correo de recuperación en `storage/logs/mail.log`.
- Trabajo de impresión en `storage/prints`.

## Observaciones

- Los datos de QA quedan en la base local. Para una base limpia, ejecutar `php bin/migrate.php --fresh && php bin/seed.php`.
- El script restaura la contraseña original del comprador.
- El worker de webhooks no se prueba en este script; se validó con receptor local y firma HMAC.
