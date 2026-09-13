USE pitter_ponto;

CREATE TABLE IF NOT EXISTS day_offs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type ENUM(
        'folga',
        'ferias',
        'falta_justificada',
        'atestado',
        'compensacao'
    ) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    note VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_day_offs_user_dates (user_id, start_date, end_date),
    CONSTRAINT fk_day_offs_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
);

-- Os feriados nacionais são inseridos automaticamente pelo PHP
-- ao abrir o Dashboard ou o Calendário.
