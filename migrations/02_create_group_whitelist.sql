CREATE TABLE IF NOT EXISTS `ts_group_whitelist` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `group_id` VARCHAR(50) NOT NULL UNIQUE,
    `group_name` VARCHAR(100) NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert some dummy group data for testing
INSERT IGNORE INTO `ts_group_whitelist` (`group_id`, `group_name`, `is_active`) VALUES
('group-abc-123', 'Grup Komplain VIP', 1),
('group-xyz-987', 'Grup Komplain Reguler', 1);
