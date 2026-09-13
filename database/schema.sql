-- ============================================================
-- Pitter Ponto / Pitter Pan Festas
-- Banco: pitter_ponto
-- Compatível com MySQL / MariaDB do XAMPP
-- ============================================================

CREATE DATABASE IF NOT EXISTS pitter_ponto
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE pitter_ponto;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS time_adjustments;
DROP TABLE IF EXISTS time_entries;
DROP TABLE IF EXISTS holidays;
DROP TABLE IF EXISTS work_settings;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'employee') NOT NULL DEFAULT 'employee',
    salary DECIMAL(10,2) NULL,
    daily_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 528 COMMENT '8h48 = 528 minutos',
    monthly_hours SMALLINT UNSIGNED NOT NULL DEFAULT 220,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE time_entries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    entry_type ENUM('clock_in', 'lunch_start', 'lunch_end', 'clock_out') NOT NULL,
    recorded_at DATETIME NOT NULL,
    source ENUM('web', 'manual', 'admin') NOT NULL DEFAULT 'web',
    note VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_time_entries_user_date (user_id, recorded_at),
    CONSTRAINT fk_time_entries_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
);

CREATE TABLE time_adjustments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    time_entry_id BIGINT UNSIGNED NOT NULL,
    changed_by BIGINT UNSIGNED NOT NULL,
    old_recorded_at DATETIME NOT NULL,
    new_recorded_at DATETIME NOT NULL,
    reason VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_adjustment_entry
        FOREIGN KEY (time_entry_id) REFERENCES time_entries(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_adjustment_user
        FOREIGN KEY (changed_by) REFERENCES users(id)
        ON DELETE RESTRICT
);

CREATE TABLE work_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value VARCHAR(255) NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE holidays (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    holiday_date DATE NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    overtime_percent SMALLINT UNSIGNED NOT NULL DEFAULT 100,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO work_settings (setting_key, setting_value) VALUES
('overtime_weekday_percent', '50'),
('overtime_saturday_percent', '100'),
('overtime_sunday_percent', '100'),
('tolerance_minutes', '5'),
('workday_start', '08:00'),
('workday_end', '17:48'),
('lunch_minutes', '60');

-- Senha do admin: 123456
INSERT INTO users (
    name, email, password, role, salary, daily_minutes, monthly_hours
) VALUES (
    'Vitor Binhotti',
    'admin@pitterpan.com',
    '$2y$12$YfFv8IYvebRFH9J1uBSiWuv.eWxhnJmwhDFN5esFXM6/L5mkFp536',
    'admin',
    NULL,
    528,
    220
);

-- O sistema inicia sem marcações de ponto.
-- Os registros serão criados somente quando o usuário bater o ponto.

-- Fim do schema
