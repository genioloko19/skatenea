<?php

declare(strict_types=1);

namespace Skatenea\Infrastructure\Mail;

use Skatenea\Application\Mail\MailerInterface;

final class NullMailer implements MailerInterface
{
    public function send(string $toEmail, ?string $toName, string $subject, string $html): void
    {
        // Intencionalmente no envía nada (entornos de prueba).
    }
}
