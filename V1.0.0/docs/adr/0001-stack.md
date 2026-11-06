# ADR 0001 — Stack backend PHP 8.2 + PDO + capas

**Estado:** aceptado  
**Fecha:** 2026-11-05

## Contexto

El sistema alfa es PHP monolítico con lógica de negocio, SQL y presentación mezclados. Se requiere una base mantenible, compatible con el hosting actual y que permita separar cliente y servidor.

## Decisión

- PHP 8.2+ como lenguaje de servidor.
- PDO con consultas preparadas y transacciones.
- Arquitectura por capas: Dominio, Aplicación, Infraestructura, HTTP.
- Composer + PSR-4 (`Skatenea\` → `src/`).
- Sin framework completo; router y contenedor propios, mínimos y testeables.
- OpenAPI 3.1 como contrato.

## Consecuencias

- Se mantiene compatibilidad con XAMPP/hosting PHP.
- Mayor control sobre seguridad y dependencias.
- Requiere disciplina de capas y pruebas.
- Migrar a un framework (Laravel/Symfony) sigue siendo posible sin reescribir el dominio.
