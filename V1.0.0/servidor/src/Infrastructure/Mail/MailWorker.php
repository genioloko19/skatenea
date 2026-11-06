<?php

declare(strict_types=1);

namespace Skatenea\Infrastructure\Mail;

use Monolog\Logger;
use Skatenea\Application\Mail\EmailOutboxRepositoryInterface;
use Skatenea\Application\Mail\MailerInterface;
use Skatenea\Core\Config;

final class MailWorker
{
    public function __construct(
        private readonly EmailOutboxRepositoryInterface $outbox,
        private readonly MailerInterface $mailer,
        private readonly Config $config,
        private readonly Logger $logger
    ) {
    }

    /** @return array{sent: int, failed: int} */
    public function process(int $limit = 50): array
    {
        $sent = 0;
        $failed = 0;
        $maxAttempts = $this->config->int('MAIL_MAX_ATTEMPTS', 5);

        foreach ($this->outbox->pending($limit) as $message) {
            $id = (string) $message['id'];
            if (!$this->outbox->claim($id)) {
                continue;
            }

            try {
                $this->mailer->send(
                    (string) $message['to_email'],
                    isset($message['to_name']) ? (string) $message['to_name'] : null,
                    (string) $message['subject'],
                    (string) $message['body_html']
                );
                $this->outbox->markSent($id, gmdate('Y-m-d H:i:s'));
                $sent++;
            } catch (\Throwable $e) {
                $attempts = (int) $message['attempts'] + 1;
                $permanent = $attempts >= $maxAttempts;
                $delay = min(3600, 60 * (2 ** min(6, $attempts)));
                $this->outbox->markFailed(
                    $id,
                    $e->getMessage(),
                    $attempts,
                    gmdate('Y-m-d H:i:s', time() + $delay),
                    $permanent
                );
                $this->logger->error('No se pudo enviar correo', [
                    'outbox_id' => $id,
                    'attempts' => $attempts,
                    'exception' => $e,
                ]);
                $failed++;
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }
}
