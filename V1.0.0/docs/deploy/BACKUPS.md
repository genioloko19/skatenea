# Backups y recuperación

## Estrategia

- **Completo diario** de MySQL/MariaDB con `mysqldump --single-transaction --routines --triggers`.
- **Incremental/binlog** cada hora si el volumen lo requiere.
- **Cifrado** en reposo con clave fuera del servidor.
- **Retención** 30 días diarios, 12 meses mensuales.
- **Copia externa** en otra región/proveedor.
- **Prueba de restauración** trimestral documentada.

## Comando base

```bash
mysqldump --single-transaction --routines --triggers \
  --default-character-set=utf8mb4 \
  -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" \
  | gzip > "skatenea-$(date +%F-%H%M).sql.gz"
```

## Restauración

```bash
gunzip -c skatenea-2026-11-06.sql.gz | mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME"
```

## Archivos

- `servidor/storage/prints` (si aplica).
- `servidor/storage/logs` (retención corta; no crítico).
- Configuración `.env` en gestor de secretos, no en el backup de base.

## Verificación

1. Restaurar en entorno aislado.
2. Ejecutar migraciones y healthcheck.
3. Validar login, una rifa, una boleta pagada y una exportación.
4. Registrar resultado y tiempo de recuperación (RTO/RPO).
