-- ============================================================
-- Pitter Ponto - Patch 001
-- Use este arquivo se você já importou a primeira versão do schema.
-- Ele NÃO apaga usuários.
-- ============================================================

USE pitter_ponto;

-- Remove apenas os pontos de demonstração inseridos na primeira versão.
DELETE FROM time_entries;
ALTER TABLE time_entries AUTO_INCREMENT = 1;

-- Jornada padrão informada:
-- 08:00 até 17:48, com 60 minutos de almoço.
INSERT INTO work_settings (setting_key, setting_value) VALUES
('workday_start', '08:00'),
('workday_end', '17:48'),
('lunch_minutes', '60')
ON DUPLICATE KEY UPDATE
setting_value = VALUES(setting_value);

-- Mantém as regras já definidas de horas extras.
INSERT INTO work_settings (setting_key, setting_value) VALUES
('overtime_weekday_percent', '65'),
('overtime_saturday_percent', '100'),
('overtime_sunday_percent', '100'),
('tolerance_minutes', '5')
ON DUPLICATE KEY UPDATE
setting_value = VALUES(setting_value);
