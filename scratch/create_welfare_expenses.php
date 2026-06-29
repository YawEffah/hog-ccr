<?php
require 'includes/db.php';
$db = getDB();

$sql = "
CREATE TABLE IF NOT EXISTS `welfare_expenses` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `expense_date` date NOT NULL,
    `amount` decimal(12,2) NOT NULL,
    `category_id` int(10) unsigned NOT NULL,
    `asset_account_id` int(10) unsigned NOT NULL,
    `recipient_type` enum('Member', 'External') NOT NULL DEFAULT 'Member',
    `recipient_member_id` int(10) unsigned DEFAULT NULL,
    `recipient_name` varchar(255) DEFAULT NULL,
    `description` text,
    `reference_no` varchar(100) DEFAULT NULL,
    `recorded_by` int(10) unsigned NOT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_w_exp_cat` FOREIGN KEY (`category_id`) REFERENCES `welfare_accounts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_w_exp_ast` FOREIGN KEY (`asset_account_id`) REFERENCES `welfare_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

try {
    $db->exec($sql);
    echo "Table welfare_expenses created successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
