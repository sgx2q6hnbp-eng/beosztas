-- ============================================================
-- 005: Műszakcsere kérelmek tábla
-- ============================================================
CREATE TABLE IF NOT EXISTS swap_requests (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    requester_id      INT UNSIGNED NOT NULL,
    target_id         INT UNSIGNED NOT NULL,
    requester_shift_id INT UNSIGNED NOT NULL,
    target_shift_id   INT UNSIGNED NOT NULL,
    message           TEXT         DEFAULT NULL,
    status            ENUM(
                        'pending',        -- elküldve, kolléga nem válaszolt
                        'accepted',       -- kolléga elfogadta, admin dönt
                        'rejected',       -- kolléga elutasította
                        'approved',       -- admin jóváhagyta, csere megtörtént
                        'cancelled'       -- kérelmező visszavonta
                      ) NOT NULL DEFAULT 'pending',
    reviewed_by       INT UNSIGNED DEFAULT NULL,
    reviewed_at       DATETIME     DEFAULT NULL,
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_requester (requester_id, status),
    INDEX idx_target    (target_id, status),
    FOREIGN KEY (requester_id)       REFERENCES users(id)  ON DELETE CASCADE,
    FOREIGN KEY (target_id)          REFERENCES users(id)  ON DELETE CASCADE,
    FOREIGN KEY (requester_shift_id) REFERENCES shifts(id) ON DELETE CASCADE,
    FOREIGN KEY (target_shift_id)    REFERENCES shifts(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by)        REFERENCES users(id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
