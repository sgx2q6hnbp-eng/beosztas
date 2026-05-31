-- Ünnepnapok tábla
CREATE TABLE IF NOT EXISTS holidays (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    holiday_date DATE         NOT NULL UNIQUE,
    name         VARCHAR(100) NOT NULL,
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);
