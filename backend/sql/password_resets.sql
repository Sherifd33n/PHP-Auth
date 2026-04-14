-- Run this in phpMyAdmin → login_register database → SQL tab
--
-- Why this table?
-- We can't store reset tokens in the users table because one user could
-- have multiple pending reset requests. A separate table is clean and scalable.

CREATE TABLE IF NOT EXISTS password_resets (
    id         INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(254)  NOT NULL,              -- which user requested it
    token      VARCHAR(64)   NOT NULL,              -- the secret reset token
    expires_at DATETIME      NOT NULL,              -- token dies after 30 min
    used       TINYINT(1)    NOT NULL DEFAULT 0,    -- 1 = already consumed
    created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,

    -- Fast lookup when a user submits their email
    INDEX  idx_email (email),

    -- No two tokens can ever be the same (prevents collision attacks)
    UNIQUE KEY uq_token (token)
);
