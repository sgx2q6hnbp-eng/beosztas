-- ============================================================
-- 004: Szabadság / táppénz kérelmek tábla
-- ============================================================
CREATE TABLE IF NOT EXISTS leave_requests (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED  NOT NULL,
    leave_type   ENUM('vacation','sick','unpaid','other') NOT NULL,
    start_date   DATE          NOT NULL,
    end_date     DATE          NOT NULL,
    reason       TEXT          DEFAULT NULL,
    status       ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    reviewed_by  INT UNSIGNED  DEFAULT NULL,
    reviewed_at  DATETIME      DEFAULT NULL,
    admin_note   TEXT          DEFAULT NULL,
    created_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user_leave (user_id, status),
    FOREIGN KEY (user_id)     REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
