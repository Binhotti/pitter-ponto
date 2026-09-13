USE pitter_ponto;

ALTER TABLE users
    ADD COLUMN workday_start TIME NOT NULL DEFAULT '08:00:00',
    ADD COLUMN workday_end TIME NOT NULL DEFAULT '17:48:00',
    ADD COLUMN lunch_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 60,
    ADD COLUMN theme ENUM('light', 'dark') NOT NULL DEFAULT 'light',
    ADD COLUMN notifications_enabled TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN browser_notifications TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN avatar_path VARCHAR(255) NULL;

-- Mantém a jornada atual já utilizada no sistema.
UPDATE users
SET daily_minutes = TIMESTAMPDIFF(
        MINUTE,
        CONCAT('1970-01-01 ', workday_start),
        CONCAT('1970-01-01 ', workday_end)
    ) - lunch_minutes
WHERE daily_minutes IS NULL OR daily_minutes = 0;
