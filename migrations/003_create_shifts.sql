-- ============================================================
-- 003: Műszakok tábla
-- ============================================================
CREATE TABLE IF NOT EXISTS shifts (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED  NOT NULL,
    fleet_id     TINYINT UNSIGNED NOT NULL,
    shift_date   DATE          NOT NULL,
    start_time   TIME          NOT NULL DEFAULT '06:00:00',
    end_time     TIME          NOT NULL DEFAULT '18:00:00',
    location     VARCHAR(100)  DEFAULT NULL,   -- település
    license_plate VARCHAR(20)  DEFAULT NULL,   -- rendszám
    status       ENUM('active','sick','vacation','absence','swap_pending') NOT NULL DEFAULT 'active',
    note         TEXT          DEFAULT NULL,
    imported_at  DATETIME      DEFAULT NULL,   -- Excel importból jött-e
    created_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_user_date (user_id, shift_date),   -- egy embernek egy nap = egy sor
    INDEX idx_shift_date   (shift_date),
    INDEX idx_fleet_date   (fleet_id, shift_date),

    FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
    FOREIGN KEY (fleet_id) REFERENCES fleets(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
