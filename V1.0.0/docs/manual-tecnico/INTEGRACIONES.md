# Integraciones — fase 7

## 1. Correo transaccional

- Cola: `email_outbox`.
- Servicio: `Application\Mail\MailService`.
- Plantillas: `Application\Mail\MailTemplates`.
- Driver:
  - `MAIL_DRIVER=log` → `storage/logs/mail.log` (desarrollo).
  - `MAIL_DRIVER=smtp` → PHPMailer con TLS.
  - `MAIL_DRIVER=null` → desactivado.
- Worker: `php bin/mail-worker.php` (o `--once`).
- Reintentos: `MAIL_MAX_ATTEMPTS` con backoff exponencial.

Eventos encolados:

- Bienvenida al registrarse.
- Recuperación de contraseña.
- Pago confirmado (si el comprador tiene correo).
- Rifa aprobada/rechazada al líder.

## 2. Recuperación de contraseña

1. `POST /api/v1/auth/forgot-password` con `{email}`.
2. Se genera token aleatorio de 32 bytes; en BD solo el hash SHA-256.
3. Se encola correo con enlace `PUBLIC_WEB_URL/reset-password?token=...`.
4. `POST /api/v1/auth/reset-password` con `{token,password}`.
5. Se invalida el token, se revocan sesiones y se resetea el lockout.

## 3. Webhooks

- Tabla `webhooks` y `webhook_deliveries`.
- Firma: `X-SkateNea-Signature: sha256=<HMAC-SHA256(rawBody, secret)>`.
- Cabeceras: `X-SkateNea-Event`, `X-SkateNea-Delivery`.
- Worker: `php bin/webhook-worker.php` (o `--once`).
- Reintentos: `WEBHOOK_MAX_ATTEMPTS` con backoff.
- Eventos: rifas, boletas y `*`.
- SSRF: `WEBHOOK_ALLOW_PRIVATE=false` en producción bloquea IP privadas/reservadas.

Verificación en el receptor:

```php
$expected = 'sha256=' . hash_hmac('sha256', $rawBody, $secret);
if (!hash_equals($expected, $_SERVER['HTTP_X_SKATENEA_SIGNATURE'] ?? '')) {
    http_response_code(401);
    exit;
}
```

## 4. Impresión térmica desacoplada

- Tabla `print_jobs`.
- `POST /api/v1/tickets/{id}/print` encola.
- `GET /api/v1/print-jobs/{id}` consulta estado.
- Worker: `php bin/print-worker.php` (o `--once`).
- Drivers:
  - `file` → `storage/prints/*.bin`.
  - `network` → `PRINT_HOST:PRINT_PORT` (ESC/POS raw, típico 9100).
  - `windows` → recurso compartido `\\localhost\POS-80`.
  - `null` → simulación.

## 5. PDF y QR

- `GET /api/v1/tickets/{id}/receipt.pdf`.
- mPDF + `mpdf/qrcode`.
- Incluye número, rifa, premio, comprador, vendedor, valor, fecha y QR.
- El QR apunta a `PUBLIC_WEB_URL/verify/<token>`.
- La verificación pública no muestra PII.

## 6. Exportaciones

- `GET /api/v1/raffles/{id}/export.csv` (propietario/admin; vendedor solo sus boletas).
- `GET /api/v1/audit/export.csv` (super admin).
- CSV UTF-8 con BOM y separador `;` para Excel en español.

## 7. Workers y operación

```powershell
cd servidor
php bin/mail-worker.php
php bin/webhook-worker.php
php bin/print-worker.php
php bin/maintenance.php
```

En producción usar systemd/supervisor y un timer diario para mantenimiento. Ver `docs/deploy/NGINX.md`.

## 8. Variables

| Variable | Uso |
|---|---|
| `MAIL_DRIVER`, `MAIL_ENABLED` | Canal de correo |
| `MAIL_HOST`, `MAIL_PORT`, `MAIL_USER`, `MAIL_PASS`, `MAIL_FROM` | SMTP |
| `PUBLIC_WEB_URL` | Enlaces de verificación/reset |
| `PASSWORD_RESET_TTL_MINUTES` | Vida del token |
| `WEBHOOK_MAX_ATTEMPTS`, `WEBHOOK_ALLOW_PRIVATE` | Webhooks/SSRF |
| `PRINT_DRIVER`, `PRINT_HOST`, `PRINT_PORT`, `PRINT_WINDOWS_SHARE` | Impresión |
| `MAIL_MAX_ATTEMPTS` | Reintentos de correo |
