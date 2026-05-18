-- ============================================================
-- 006: Audit log tábla (ki, mikor, mit módosított)
-- ============================================================
CREATE TABLE IF NOT EXISTS shift_logs (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shift_id     INT UNSIGNED  NOT NULL,
    changed_by   INT UNSIGNED  NOT NULL,
    change_type  ENUM('create','update','delete') NOT NULL,
    old_value    JSON          DEFAULT NULL,
    new_value    JSON          DEFAULT NULL,
    changed_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_shift_log (shift_id),
    FOREIGN KEY (shift_id)   REFERENCES shifts(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
