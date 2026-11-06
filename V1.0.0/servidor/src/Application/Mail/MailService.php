<?php

declare(strict_types=1);

namespace Skatenea\Application\Mail;

use Skatenea\Core\Config;
use Skatenea\Core\Uuid;
use Skatenea\Domain\User\User;

final class MailService
{
    public function __construct(
        private readonly EmailOutboxRepositoryInterface $outbox,
        private readonly Config $config
    ) {
    }

    public function queueWelcome(User $user): void
    {
        $this->queue(
            $user->email,
            $user->fullName,
            'Bienvenido a ' . $this->appName(),
            MailTemplates::welcome($this->appName(), $user->fullName)
        );
    }

    public function queuePasswordReset(User $user, string $token, int $minutes = 30): void
    {
        $url = $this->publicUrl('/reset-password?token=' . urlencode($token));
        $this->queue(
            $user->email,
            $user->fullName,
            'Recupera tu acceso a ' . $this->appName(),
            MailTemplates::passwordReset($this->appName(), $user->fullName, $url, $minutes)
        );
    }

    public function queueTicketPaid(
        string $buyerEmail,
        string $buyerName,
        string $raffleName,
        int $number,
        string $amount,
        string $token
    ): void {
        $this->queue(
            $buyerEmail,
            $buyerName,
            'Pago confirmado · Boleta #' . $number,
            MailTemplates::ticketPaid(
                $this->appName(),
                $buyerName,
                $raffleName,
                $number,
                $amount,
                $this->publicUrl('/verify/' . urlencode($token))
            )
        );
    }

    public function queueRaffleApproved(User $leader, string $raffleName): void
    {
        $this->queue(
            $leader->email,
            $leader->fullName,
            'Rifa aprobada: ' . $raffleName,
            MailTemplates::raffleApproved($this->appName(), $leader->fullName, $raffleName)
        );
    }

    public function queueRaffleRejected(User $leader, string $raffleName, string $reason): void
    {
        $this->queue(
            $leader->email,
            $leader->fullName,
            'Rifa rechazada: ' . $raffleName,
            MailTemplates::raffleRejected($this->appName(), $leader->fullName, $raffleName, $reason)
        );
    }

    public function queue(string $toEmail, ?string $toName, string $subject, string $html): void
    {
        if (!$this->config->bool('MAIL_ENABLED', true)) {
            return;
        }

        $now = gmdate('Y-m-d H:i:s');
        $this->outbox->enqueue([
            'id' => Uuid::v4(),
            'to_email' => $toEmail,
            'to_name' => $toName,
            'subject' => $subject,
            'body_html' => $html,
            'status' => 'pending',
            'attempts' => 0,
            'last_error' => null,
            'scheduled_at' => $now,
            'sent_at' => null,
            'created_at' => $now,
        ]);
    }

    private function appName(): string
    {
        return $this->config->string('APP_NAME', 'SKATENEA');
    }

    private function publicUrl(string $path): string
    {
        return rtrim($this->config->string('PUBLIC_WEB_URL', 'http://localhost:5173'), '/') . $path;
    }
}
