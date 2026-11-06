<?php

declare(strict_types=1);

namespace Skatenea\Application\Mail;

interface MailerInterface
{
    public function send(string $toEmail, ?string $toName, string $subject, string $html): void;
}
