-- SKATENEA V1.0.0 â€” esquema portable (MySQL 8+/MariaDB 10.6+ y SQLite 3.35+)
-- Fechas en UTC (DATETIME), dinero DECIMAL(12,2), IDs CHAR(36) (UUID v4).
-- No usa ENUM/JSON para mantener portabilidad entre motores.

CREATE TABLE IF NOT EXISTS users (
    id CHAR(36) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    role VARCHAR(20) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    phone VARCHAR(30) NULL,
    document_type VARCHAR(10) NULL,
    document_number VARCHAR(50) NULL,
    must_change_password INTEGER NOT NULL DEFAULT 0,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (email),
    CHECK (role IN ('super_admin', 'leader', 'seller', 'buyer')),
    CHECK (status IN ('active', 'inactive', 'locked'))
);

CREATE TABLE IF NOT EXISTS refresh_tokens (
    id CHAR(36) NOT NULL,
    user_id CHAR(36) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    csrf_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    revoked_at DATETIME NULL,
    replaced_by CHAR(36) NULL,
    user_agent VARCHAR(255) NULL,
    ip VARCHAR(45) NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (token_hash),
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE INDEX idx_refresh_user ON refresh_tokens (user_id);
CREATE INDEX idx_refresh_expires ON refresh_tokens (expires_at);

CREATE TABLE IF NOT EXISTS api_keys (
    id CHAR(36) NOT NULL,
    name VARCHAR(100) NOT NULL,
    prefix VARCHAR(16) NOT NULL,
    key_hash CHAR(64) NOT NULL,
    scopes TEXT NOT NULL,
    created_by CHAR(36) NOT NULL,
    last_used_at DATETIME NULL,
    expires_at DATETIME NULL,
    revoked_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (prefix),
    UNIQUE (key_hash),
    FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS raffles (
    id CHAR(36) NOT NULL,
    code VARCHAR(30) NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    prize VARCHAR(255) NOT NULL,
    ticket_price DECIMAL(12, 2) NOT NULL,
    total_tickets INTEGER NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    created_by CHAR(36) NOT NULL,
    approved_by CHAR(36) NULL,
    approved_at DATETIME NULL,
    rejection_reason TEXT NULL,
    start_at DATETIME NOT NULL,
    end_at DATETIME NOT NULL,
    draw_seed_hash CHAR(64) NULL,
    draw_seed VARCHAR(128) NULL,
    winner_ticket_id CHAR(36) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (code),
    FOREIGN KEY (created_by) REFERENCES users (id),
    FOREIGN KEY (approved_by) REFERENCES users (id),
    CHECK (status IN ('draft', 'pending_approval', 'approved', 'active', 'closed', 'finished', 'cancelled', 'rejected')),
    CHECK (total_tickets > 0),
    CHECK (ticket_price >= 0)
);

CREATE INDEX idx_raffles_status ON raffles (status);
CREATE INDEX idx_raffles_owner ON raffles (created_by);
CREATE INDEX idx_raffles_dates ON raffles (start_at, end_at);

CREATE TABLE IF NOT EXISTS raffle_sellers (
    raffle_id CHAR(36) NOT NULL,
    seller_id CHAR(36) NOT NULL,
    assigned_by CHAR(36) NOT NULL,
    assigned_at DATETIME NOT NULL,
    PRIMARY KEY (raffle_id, seller_id),
    FOREIGN KEY (raffle_id) REFERENCES raffles (id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users (id)
);

CREATE TABLE IF NOT EXISTS buyers (
    id CHAR(36) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NULL,
    phone VARCHAR(30) NOT NULL,
    document_type VARCHAR(10) NULL,
    document_number VARCHAR(50) NULL,
    user_id CHAR(36) NULL,
    created_by CHAR(36) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (email),
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
);

CREATE INDEX idx_buyers_document ON buyers (document_type, document_number);
CREATE INDEX idx_buyers_phone ON buyers (phone);

CREATE TABLE IF NOT EXISTS tickets (
    id CHAR(36) NOT NULL,
    raffle_id CHAR(36) NOT NULL,
    number INTEGER NOT NULL,
    seller_id CHAR(36) NULL,
    buyer_id CHAR(36) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'available',
    price DECIMAL(12, 2) NOT NULL,
    token CHAR(64) NOT NULL,
    reserved_until DATETIME NULL,
    reserved_at DATETIME NULL,
    paid_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (raffle_id, number),
    UNIQUE (token),
    FOREIGN KEY (raffle_id) REFERENCES raffles (id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES users (id) ON DELETE SET NULL,
    FOREIGN KEY (buyer_id) REFERENCES buyers (id) ON DELETE SET NULL,
    CHECK (status IN ('available', 'reserved', 'paid', 'cancelled', 'winner')),
    CHECK (number > 0)
);

CREATE INDEX idx_tickets_raffle_status ON tickets (raffle_id, status);
CREATE INDEX idx_tickets_seller ON tickets (seller_id);
CREATE INDEX idx_tickets_buyer ON tickets (buyer_id);
CREATE INDEX idx_tickets_number ON tickets (number);

CREATE TABLE IF NOT EXISTS payments (
    id CHAR(36) NOT NULL,
    ticket_id CHAR(36) NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    method VARCHAR(20) NOT NULL,
    reference VARCHAR(100) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    confirmed_by CHAR(36) NULL,
    confirmed_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON DELETE CASCADE,
    FOREIGN KEY (confirmed_by) REFERENCES users (id),
    CHECK (status IN ('pending', 'confirmed', 'rejected', 'refunded')),
    CHECK (amount >= 0)
);

CREATE INDEX idx_payments_ticket ON payments (ticket_id);
CREATE INDEX idx_payments_status ON payments (status);

CREATE TABLE IF NOT EXISTS raffle_approvals (
    id CHAR(36) NOT NULL,
    raffle_id CHAR(36) NOT NULL,
    actor_id CHAR(36) NOT NULL,
    action VARCHAR(20) NOT NULL,
    reason TEXT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (raffle_id) REFERENCES raffles (id) ON DELETE CASCADE,
    FOREIGN KEY (actor_id) REFERENCES users (id),
    CHECK (action IN ('submitted', 'approved', 'rejected', 'cancelled'))
);

CREATE INDEX idx_approvals_raffle ON raffle_approvals (raffle_id);

CREATE TABLE IF NOT EXISTS audit_logs (
    id CHAR(36) NOT NULL,
    actor_id CHAR(36) NULL,
    actor_type VARCHAR(20) NOT NULL DEFAULT 'user',
    action VARCHAR(80) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id CHAR(36) NULL,
    before_json TEXT NULL,
    after_json TEXT NULL,
    ip VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (actor_id) REFERENCES users (id) ON DELETE SET NULL
);

CREATE INDEX idx_audit_actor ON audit_logs (actor_id);
CREATE INDEX idx_audit_entity ON audit_logs (entity_type, entity_id);
CREATE INDEX idx_audit_date ON audit_logs (created_at);

CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT NOT NULL,
    updated_by CHAR(36) NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (setting_key),
    FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS user_preferences (
    user_id CHAR(36) NOT NULL,
    theme VARCHAR(30) NOT NULL DEFAULT 'aurora',
    mode VARCHAR(20) NOT NULL DEFAULT 'dark',
    geometry VARCHAR(20) NOT NULL DEFAULT 'soft',
    background VARCHAR(20) NOT NULL DEFAULT 'aurora',
    sidebar_collapsed INTEGER NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (user_id),
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CHECK (mode IN ('light', 'dark', 'contrast')),
    CHECK (geometry IN ('soft', 'sharp', 'pill', 'organic'))
);

CREATE TABLE IF NOT EXISTS password_resets (
    id CHAR(36) NOT NULL,
    user_id CHAR(36) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (token_hash),
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE INDEX idx_password_resets_user ON password_resets (user_id);

CREATE TABLE IF NOT EXISTS notifications (
    id CHAR(36) NOT NULL,
    user_id CHAR(36) NOT NULL,
    title VARCHAR(150) NOT NULL,
    body TEXT NOT NULL,
    read_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE INDEX idx_notifications_user ON notifications (user_id, read_at);
