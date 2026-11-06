<?php

declare(strict_types=1);

namespace Skatenea\Infrastructure\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use Skatenea\Application\Mail\MailerInterface;
use Skatenea\Core\Config;

final class SmtpMailer implements MailerInterface
{
    public function __construct(private readonly Config $config)
    {
    }

    public function send(string $toEmail, ?string $toName, string $subject, string $html): void
    {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $this->config->string('MAIL_HOST');
        $mail->Port = $this->config->int('MAIL_PORT', 587);
        $mail->SMTPAuth = true;
        $mail->Username = $this->config->string('MAIL_USER');
        $mail->Password = $this->config->string('MAIL_PASS');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom(
            $this->config->string('MAIL_FROM', 'no-reply@skatenea.local'),
            $this->config->string('MAIL_FROM_NAME', 'SKATENEA')
        );
        $mail->addAddress($toEmail, $toName ?? '');
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $html;
        $mail->AltBody = strip_tags($html);

        $mail->send();
    }
}
