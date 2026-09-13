-- Pitter Ponto v4.0 - Área Administrativa - LOCAL/XAMPP
USE pitter_ponto;

CREATE TABLE IF NOT EXISTS login_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) NULL,
    logged_in_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_login_history_user_date (user_id, logged_in_at),
    INDEX idx_login_history_date (logged_in_at),
    CONSTRAINT fk_login_history_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
);

-- Depois, troque o e-mail e execute:
-- UPDATE users SET role = 'admin' WHERE email = 'seu-email@empresa.com';
