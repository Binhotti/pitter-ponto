-- Pitter Ponto v4.3 - LOCAL / XAMPP
USE pitter_ponto;

INSERT INTO work_settings (setting_key, setting_value)
VALUES ('overtime_weekday_percent', '65')
ON DUPLICATE KEY UPDATE
    setting_value = VALUES(setting_value);
