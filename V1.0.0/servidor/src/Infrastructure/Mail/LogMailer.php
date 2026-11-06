<?php

declare(strict_types=1);

namespace Skatenea\Infrastructure\Mail;

use Skatenea\Application\Mail\MailerInterface;

final class LogMailer implements MailerInterface
{
    public function __construct(private readonly string $logPath)
    {
    }

    public function send(string $toEmail, ?string $toName, string $subject, string $html): void
    {
        $directory = dirname($this->logPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $entry = sprintf(
            "[%s] TO=%s <%s> SUBJECT=%s\n%s\n\n",
            gmdate('c'),
            $toName ?? '',
            $toEmail,
            $subject,
            $html
        );

        file_put_contents($this->logPath, $entry, FILE_APPEND | LOCK_EX);
    }
}
