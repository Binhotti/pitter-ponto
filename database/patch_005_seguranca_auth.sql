USE pitter_ponto;

CREATE TABLE IF NOT EXISTS login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(160) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    first_attempt_at DATETIME NOT NULL,
    last_attempt_at DATETIME NOT NULL,
    locked_until DATETIME NULL,
    UNIQUE KEY uq_login_attempt_email_ip (email, ip_address),
    INDEX idx_login_attempt_locked_until (locked_until),
    INDEX idx_login_attempt_last_attempt (last_attempt_at)
);

-- Garante UNIQUE no e-mail caso o banco tenha vindo de uma versão antiga.
SET @users_email_unique_exists = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'users'
      AND column_name = 'email'
      AND non_unique = 0
);

SET @users_email_unique_sql = IF(
    @users_email_unique_exists = 0,
    'ALTER TABLE users ADD UNIQUE KEY uq_users_email (email)',
    'SELECT 1'
);

PREPARE users_email_unique_stmt FROM @users_email_unique_sql;
EXECUTE users_email_unique_stmt;
DEALLOCATE PREPARE users_email_unique_stmt;
