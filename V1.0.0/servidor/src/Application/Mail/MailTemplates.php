<?php

declare(strict_types=1);

namespace Skatenea\Application\Mail;

final class MailTemplates
{
    public static function layout(string $appName, string $title, string $body, string $footer = ''): string
    {
        $safeApp = htmlspecialchars($appName, ENT_QUOTES, 'UTF-8');
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeFooter = $footer !== '' ? $footer : 'Este es un mensaje automático. No respondas a este correo.';

        return <<<HTML
<!doctype html>
<html lang="es">
<body style="margin:0;background:#f3f6ff;font-family:Arial,Helvetica,sans-serif;color:#111827;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f6ff;padding:24px 0;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e5e7eb;">
          <tr>
            <td style="padding:22px 28px;background:linear-gradient(135deg,#7c9cff,#4be3c1);color:#04101f;">
              <strong style="font-size:20px;letter-spacing:2px;">{$safeApp}</strong>
              <div style="font-size:13px;opacity:.8;">Gestión de rifas</div>
            </td>
          </tr>
          <tr>
            <td style="padding:28px;">
              <h1 style="margin:0 0 14px;font-size:22px;">{$safeTitle}</h1>
              {$body}
            </td>
          </tr>
          <tr>
            <td style="padding:16px 28px;background:#f8fafc;color:#6b7280;font-size:12px;">
              {$safeFooter}
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }

    public static function welcome(string $appName, string $fullName): string
    {
        $name = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
        $body = '<p style="margin:0 0 12px;">Hola <strong>' . $name . '</strong>,</p>'
            . '<p style="margin:0 0 12px;color:#374151;">Tu cuenta en ' . htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') . ' fue creada correctamente.</p>'
            . '<p style="margin:0 0 12px;color:#374151;">Ya puedes consultar las rifas activas y tus boletas desde el portal de compradores.</p>'
            . '<p style="margin:0;color:#6b7280;font-size:13px;">Si no creaste esta cuenta, ignora este mensaje.</p>';

        return self::layout($appName, 'Bienvenido a ' . $appName, $body);
    }

    public static function passwordReset(string $appName, string $fullName, string $resetUrl, int $minutes): string
    {
        $name = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
        $url = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
        $body = '<p style="margin:0 0 12px;">Hola <strong>' . $name . '</strong>,</p>'
            . '<p style="margin:0 0 16px;color:#374151;">Recibimos una solicitud para restablecer tu contraseña. El enlace es de un solo uso y vence en ' . $minutes . ' minutos.</p>'
            . '<p style="margin:0 0 20px;"><a href="' . $url . '" style="display:inline-block;padding:12px 20px;background:#2563eb;color:#ffffff;text-decoration:none;border-radius:10px;font-weight:bold;">Restablecer contraseña</a></p>'
            . '<p style="margin:0 0 8px;color:#6b7280;font-size:13px;">Si el botón no funciona, copia este enlace:</p>'
            . '<p style="margin:0;color:#2563eb;font-size:13px;word-break:break-all;">' . $url . '</p>';

        return self::layout($appName, 'Recupera tu acceso', $body);
    }

    public static function ticketPaid(string $appName, string $buyerName, string $raffleName, int $number, string $amount, string $verifyUrl): string
    {
        $body = '<p style="margin:0 0 12px;">Hola <strong>' . htmlspecialchars($buyerName, ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
            . '<p style="margin:0 0 12px;color:#374151;">Tu pago fue confirmado para la rifa <strong>' . htmlspecialchars($raffleName, ENT_QUOTES, 'UTF-8') . '</strong>.</p>'
            . '<table cellpadding="0" cellspacing="0" style="width:100%;background:#f8fafc;border-radius:12px;padding:14px;margin:12px 0;">'
            . '<tr><td style="padding:6px 10px;color:#6b7280;">Boleta</td><td style="padding:6px 10px;font-weight:bold;">#' . $number . '</td></tr>'
            . '<tr><td style="padding:6px 10px;color:#6b7280;">Valor</td><td style="padding:6px 10px;font-weight:bold;">' . htmlspecialchars($amount, ENT_QUOTES, 'UTF-8') . '</td></tr>'
            . '</table>'
            . '<p style="margin:0 0 16px;"><a href="' . htmlspecialchars($verifyUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;padding:10px 18px;background:#0f766e;color:#ffffff;text-decoration:none;border-radius:10px;font-weight:bold;">Verificar boleta</a></p>'
            . '<p style="margin:0;color:#6b7280;font-size:13px;">Conserva este correo como comprobante.</p>';

        return self::layout($appName, 'Pago confirmado', $body);
    }

    public static function raffleApproved(string $appName, string $leaderName, string $raffleName): string
    {
        $body = '<p style="margin:0 0 12px;">Hola <strong>' . htmlspecialchars($leaderName, ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
            . '<p style="margin:0 0 12px;color:#374151;">La rifa <strong>' . htmlspecialchars($raffleName, ENT_QUOTES, 'UTF-8') . '</strong> fue aprobada por el super administrador.</p>'
            . '<p style="margin:0;color:#374151;">Ya puedes activarla, generar las boletas y asignar vendedores desde el panel.</p>';

        return self::layout($appName, 'Rifa aprobada', $body);
    }

    public static function raffleRejected(string $appName, string $leaderName, string $raffleName, string $reason): string
    {
        $body = '<p style="margin:0 0 12px;">Hola <strong>' . htmlspecialchars($leaderName, ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
            . '<p style="margin:0 0 12px;color:#374151;">La rifa <strong>' . htmlspecialchars($raffleName, ENT_QUOTES, 'UTF-8') . '</strong> requiere ajustes antes de ser aprobada.</p>'
            . '<p style="margin:0 0 8px;color:#6b7280;font-size:13px;">Motivo:</p>'
            . '<blockquote style="margin:0;padding:12px;border-left:4px solid #f59e0b;background:#fffbeb;color:#92400e;">' . nl2br(htmlspecialchars($reason, ENT_QUOTES, 'UTF-8')) . '</blockquote>';

        return self::layout($appName, 'Rifa rechazada', $body);
    }
}
