# Política de retención y eliminación de datos

| Dato | Retención sugerida | Acción |
|---|---|---|
| Cuenta activa | Mientras esté activa | — |
| Cuenta inactiva | 24 meses | Anonimizar o eliminar |
| Boletas y pagos | 5 años | Conservar por trazabilidad contable/legal |
| Comprobantes PDF | 5 años | Archivar cifrado |
| Logs de auditoría | 24 meses | Rotar y archivar |
| Refresh tokens | Hasta expiración/revocación | Eliminar diariamente |
| Tokens de recuperación | 30 minutos | Marcar usado / eliminar |
| Correos en outbox | 30 días tras envío/fallo | Eliminar |
| Entregas de webhook | 30 días | Eliminar |
| Trabajos de impresión | 30 días | Eliminar |
| Notificaciones | 12 meses | Eliminar |
| Preferencias visuales | Mientras la cuenta exista | Eliminar con la cuenta |

## Automatización

`php bin/maintenance.php` elimina tokens expirados y registros antiguos de colas.

## Borrado por solicitud

1. Verificar identidad del titular.
2. Anonimizar datos personales cuando no exista obligación legal de conservarlos.
3. Conservar boletas/pagos disociados para trazabilidad.
4. Registrar la solicitud en auditoría.

## Respaldos

Los respaldos siguen la misma retención. Al restaurar, se reaplican las reglas de eliminación.
