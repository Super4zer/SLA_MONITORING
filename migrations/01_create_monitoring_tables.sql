CREATE TABLE IF NOT EXISTS `ts_sla_monitoring` (
    `id_monitoring` INT AUTO_INCREMENT PRIMARY KEY,
    `platform` ENUM('WA', 'WECHAT') NOT NULL DEFAULT 'WA',
    `group_id` VARCHAR(50) NOT NULL,
    `client_phone` VARCHAR(20) NOT NULL,
    `message_content` TEXT NOT NULL,
    `time_received` DATETIME NOT NULL,
    `responded_by` VARCHAR(20) DEFAULT NULL,
    `time_responded` DATETIME DEFAULT NULL,
    `sla_seconds` INT DEFAULT NULL,
    `status_sla` ENUM('HIJAU', 'KUNING', 'MERAH') DEFAULT 'KUNING',
    `log_klikdsi_id` INT DEFAULT NULL,
    `is_resolved_by_explanation` TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cs_staff_whitelist` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `phone_number` VARCHAR(20) NOT NULL UNIQUE,
    `staff_name` VARCHAR(100) NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert some dummy staff data for testing
INSERT IGNORE INTO `cs_staff_whitelist` (`phone_number`, `staff_name`, `is_active`) VALUES
('6281234567890', 'CS Andi', 1),
('6289876543210', 'CS Budi', 1);
