<?php

declare(strict_types=1);

namespace Skatenea\Application\Receipt;

use Mpdf\Mpdf;
use Skatenea\Core\Config;
use Skatenea\Domain\Buyer\BuyerRepositoryInterface;
use Skatenea\Domain\Raffle\RaffleRepositoryInterface;
use Skatenea\Domain\Ticket\Ticket;

final class ReceiptService
{
    public function __construct(
        private readonly RaffleRepositoryInterface $raffles,
        private readonly BuyerRepositoryInterface $buyers,
        private readonly Config $config
    ) {
    }

    public function render(Ticket $ticket): string
    {
        $raffle = $this->raffles->findById($ticket->raffleId);
        $buyer = $ticket->buyerId !== null ? $this->buyers->findById($ticket->buyerId) : null;
        $appName = htmlspecialchars($this->config->string('APP_NAME', 'SKATENEA'), ENT_QUOTES, 'UTF-8');
        $verifyUrl = rtrim($this->config->string('PUBLIC_WEB_URL', 'http://localhost:5173'), '/')
            . '/verify/' . $ticket->token;
        $safeVerify = htmlspecialchars($verifyUrl, ENT_QUOTES, 'UTF-8');
        $raffleName = htmlspecialchars($raffle !== null ? $raffle->name : ($ticket->raffleName ?? 'Rifa'), ENT_QUOTES, 'UTF-8');
        $raffleCode = htmlspecialchars($raffle !== null ? $raffle->code : '—', ENT_QUOTES, 'UTF-8');
        $prize = htmlspecialchars($raffle !== null ? $raffle->prize : '—', ENT_QUOTES, 'UTF-8');
        $buyerName = htmlspecialchars($buyer !== null ? $buyer->fullName : ($ticket->buyerName ?? 'No registrado'), ENT_QUOTES, 'UTF-8');
        $buyerDoc = $buyer !== null
            ? htmlspecialchars(trim(($buyer->documentType ?? '') . ' ' . ($buyer->documentNumber ?? '')), ENT_QUOTES, 'UTF-8')
            : '';
        $sellerName = htmlspecialchars($ticket->sellerName ?? '—', ENT_QUOTES, 'UTF-8');
        $price = number_format((float) $ticket->price, 0, ',', '.');
        $issued = htmlspecialchars($ticket->paidAt ?? gmdate('Y-m-d H:i:s'), ENT_QUOTES, 'UTF-8');
        $status = htmlspecialchars($ticket->status, ENT_QUOTES, 'UTF-8');

        $html = <<<HTML
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><style>
body { font-family: dejavusans, sans-serif; color: #111827; font-size: 11pt; }
.header { background: #0f172a; color: #ffffff; padding: 18px; text-align: center; border-radius: 8px; }
.header h1 { margin: 0; font-size: 20pt; letter-spacing: 2px; }
.header p { margin: 4px 0 0; font-size: 9pt; color: #cbd5e1; }
.box { border: 1px solid #d1d5db; border-radius: 8px; padding: 14px; margin-top: 14px; }
.row { width: 100%; }
.row td { padding: 5px 6px; vertical-align: top; }
.label { color: #6b7280; font-size: 8pt; text-transform: uppercase; }
.value { font-weight: bold; }
.number { font-size: 26pt; font-weight: bold; color: #0f766e; text-align: center; margin: 8px 0; }
.qr { text-align: center; margin-top: 10px; }
.footer { margin-top: 18px; color: #6b7280; font-size: 7.5pt; text-align: center; }
.status { display: inline-block; padding: 3px 8px; border-radius: 10px; background: #dcfce7; color: #166534; font-size: 8pt; }
</style></head>
<body>
  <div class="header">
    <h1>{$appName}</h1>
    <p>Comprobante de boleta · Sistema de Gestión de Rifas</p>
  </div>
  <div class="number">Boleta #{$ticket->number}</div>
  <div style="text-align:center"><span class="status">{$status}</span></div>
  <div class="box">
    <table class="row">
      <tr><td><div class="label">Rifa</div><div class="value">{$raffleName}</div></td><td><div class="label">Código</div><div class="value">{$raffleCode}</div></td></tr>
      <tr><td><div class="label">Premio</div><div class="value">{$prize}</div></td><td><div class="label">Valor</div><div class="value">$ {$price}</div></td></tr>
      <tr><td><div class="label">Comprador</div><div class="value">{$buyerName}</div></td><td><div class="label">Documento</div><div class="value">{$buyerDoc}</div></td></tr>
      <tr><td><div class="label">Vendedor</div><div class="value">{$sellerName}</div></td><td><div class="label">Fecha de pago</div><div class="value">{$issued}</div></td></tr>
    </table>
  </div>
  <div class="box" style="text-align:center;">
    <div class="label">Verificación en línea</div>
    <div class="qr"><barcode code="{$safeVerify}" type="QR" class="barcode" size="1.2" error="M" /></div>
    <div style="font-size:8pt;color:#6b7280;word-break:break-all;">{$safeVerify}</div>
  </div>
  <div class="footer">
    Documento generado por {$appName}. Verifica la autenticidad del QR antes de aceptar esta boleta.<br>
    Transparencia y trazabilidad para rifas.
  </div>
</body>
</html>
HTML;

        $tempDir = dirname(__DIR__, 3) . '/storage/mpdf';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        $mpdf = new Mpdf([
            'tempDir' => $tempDir,
            'format' => 'A5',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
        ]);
        $mpdf->SetTitle('Boleta #' . $ticket->number . ' · ' . $appName);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }

    public function fileName(Ticket $ticket): string
    {
        return 'boleta-' . $ticket->number . '-' . substr($ticket->id, 0, 8) . '.pdf';
    }
}
