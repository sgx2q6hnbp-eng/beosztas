-- ============================================================
-- 001: Felhasználók tábla
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100)  NOT NULL,
    email        VARCHAR(150)  NOT NULL UNIQUE,
    password     VARCHAR(255)  NOT NULL,               -- bcrypt hash
    role         ENUM('admin','employee') NOT NULL DEFAULT 'employee',
    fleet_id     TINYINT UNSIGNED DEFAULT NULL,        -- 1 = I. Flotta, 2 = II. Flotta
    phone        VARCHAR(20)   DEFAULT NULL,
    is_active    TINYINT(1)    NOT NULL DEFAULT 1,
    remember_token VARCHAR(100) DEFAULT NULL,
    last_login   DATETIME      DEFAULT NULL,
    created_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alapértelmezett admin fiók (jelszó: Admin1234! — VÁLTOZTASD MEG!)
INSERT INTO users (name, email, password, role)
VALUES (
    'Rendszergazda',
    'admin@yourdomain.com',
    '$2y$12$placeholder_change_this_hash',
    'admin'
);
