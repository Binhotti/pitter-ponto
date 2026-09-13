USE pitter_ponto;

ALTER TABLE users
    ADD COLUMN lunch_start_time TIME NOT NULL DEFAULT '12:00:00' AFTER workday_end,
    ADD COLUMN notification_before_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 10 AFTER browser_notifications,
    ADD COLUMN notification_after_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 5 AFTER notification_before_minutes,
    ADD COLUMN notify_entry_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER notification_after_minutes,
    ADD COLUMN notify_lunch_start_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER notify_entry_enabled,
    ADD COLUMN notify_lunch_return_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER notify_lunch_start_enabled,
    ADD COLUMN notify_clock_out_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER notify_lunch_return_enabled;
