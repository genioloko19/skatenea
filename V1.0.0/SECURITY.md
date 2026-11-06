# Política de seguridad — SKATENEA

## Versiones soportadas

| Versión | Soporte |
|---|---|
| 1.x | ✅ Activa |
| 0.x | ❌ Solo referencia histórica (no usar en producción) |

## Reporte de vulnerabilidades

No abras un issue público con detalles de una vulnerabilidad. Envía un correo al responsable del proyecto con:

- Descripción y impacto.
- Pasos de reproducción.
- Versión afectada.
- Evidencia (sin datos personales reales).
- Propuesta de mitigación, si aplica.

Compromiso orientativo: acuse de recibo en 48 h y evaluación inicial en 5 días hábiles.

## Prácticas del proyecto

- Secretos en `.env`, nunca en Git.
- Revisión de dependencias con `composer audit` y `npm audit`.
- Rotación de credenciales ante cualquier sospecha.
- HTTPS obligatorio en producción.
- Principio de mínimo privilegio en base de datos y API keys.
- Auditoría de operaciones críticas.
- No usar datos reales en entornos de desarrollo.
- No ejecutar pruebas destructivas contra producción.

## Alcance

Incluye API, cliente, base de datos, scripts y configuración de despliegue. Excluye infraestructura de hosting y servicios de terceros, salvo que afecten directamente al sistema.

## Divulgación

Se coordinará una divulgación responsable después de la corrección y validación de la mitigación.
