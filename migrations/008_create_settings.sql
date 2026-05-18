CREATE TABLE IF NOT EXISTS settings (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    key_name    VARCHAR(100) NOT NULL UNIQUE,
    value       VARCHAR(255) NOT NULL DEFAULT '0',
    label       VARCHAR(200) DEFAULT NULL,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (key_name, value, label) VALUES
    ('mail_enabled',                    '0', 'E-mail értesítések globálisan'),
    ('mail_leave_new_admin',            '1', 'Új szabadságkérelem → admin értesítés'),
    ('mail_leave_reviewed_employee',    '1', 'Szabadságkérelem elbírálva → dolgozó értesítés'),
    ('mail_swap_new',                   '1', 'Új műszakcsere kérelem → érintett értesítés'),
    ('mail_swap_reviewed',              '1', 'Műszakcsere elbírálva → érintett értesítés');
