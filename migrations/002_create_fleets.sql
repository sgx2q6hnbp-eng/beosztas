-- ============================================================
-- 002: Flotta tábla
-- ============================================================
CREATE TABLE IF NOT EXISTS fleets (
    id    TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name  VARCHAR(50)  NOT NULL,     -- pl. "I. Flotta"
    color VARCHAR(7)   NOT NULL DEFAULT '#3B82F6'  -- HEX szín
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO fleets (name, color) VALUES
    ('I. Flotta',  '#3B82F6'),   -- kék
    ('II. Flotta', '#F59E0B');   -- sárga/narancs
