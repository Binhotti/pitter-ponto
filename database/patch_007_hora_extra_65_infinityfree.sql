-- Pitter Ponto v4.3 - INFINITYFREE
-- Abra primeiro o banco correto no phpMyAdmin.

INSERT INTO work_settings (setting_key, setting_value)
VALUES ('overtime_weekday_percent', '65')
ON DUPLICATE KEY UPDATE
    setting_value = VALUES(setting_value);
