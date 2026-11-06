-- Fase 7: correo, webhooks, impresión y bloqueo de cuenta.
-- Migración incremental e idempotente. Los errores de columna duplicada
-- los ignora bin/migrate.php.

ALTER TABLE users ADD COLUMN failed_login_attempts INTEGER NOT NULL DEFAULT 0;
ALTER TABLE users ADD COLUMN locked_until DATETIME NULL;

CREATE TABLE IF NOT EXISTS email_outbox (
    id CHAR(36) NOT NULL,
    to_email VARCHAR(190) NOT NULL,
    to_name VARCHAR(150) NULL,
    subject VARCHAR(255) NOT NULL,
    body_html TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    attempts INTEGER NOT NULL DEFAULT 0,
    last_error TEXT NULL,
    scheduled_at DATETIME NOT NULL,
    sent_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    CHECK (status IN ('pending', 'sending', 'sent', 'failed', 'cancelled'))
);

CREATE INDEX idx_email_outbox_status ON email_outbox (status, scheduled_at);

CREATE TABLE IF NOT EXISTS webhooks (
    id CHAR(36) NOT NULL,
    name VARCHAR(100) NOT NULL,
    url VARCHAR(500) NOT NULL,
    secret VARCHAR(255) NOT NULL,
    events TEXT NOT NULL,
    active INTEGER NOT NULL DEFAULT 1,
    created_by CHAR(36) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS webhook_deliveries (
    id CHAR(36) NOT NULL,
    webhook_id CHAR(36) NOT NULL,
    event VARCHAR(80) NOT NULL,
    payload TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    attempts INTEGER NOT NULL DEFAULT 0,
    response_code INTEGER NULL,
    last_error TEXT NULL,
    next_attempt_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    delivered_at DATETIME NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (webhook_id) REFERENCES webhooks (id) ON DELETE CASCADE,
    CHECK (status IN ('pending', 'delivered', 'failed', 'cancelled'))
);

CREATE INDEX idx_webhook_deliveries_status ON webhook_deliveries (status, next_attempt_at);

CREATE TABLE IF NOT EXISTS print_jobs (
    id CHAR(36) NOT NULL,
    ticket_id CHAR(36) NOT NULL,
    type VARCHAR(30) NOT NULL DEFAULT 'ticket',
    payload TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    attempts INTEGER NOT NULL DEFAULT 0,
    last_error TEXT NULL,
    created_at DATETIME NOT NULL,
    processed_at DATETIME NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON DELETE CASCADE,
    CHECK (status IN ('pending', 'processing', 'done', 'failed', 'cancelled'))
);

CREATE INDEX idx_print_jobs_status ON print_jobs (status, created_at);
