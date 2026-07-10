<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$db = getDB();

echo "Setting up Families tables...\n";

// Create families table
$db->exec("
CREATE TABLE IF NOT EXISTS `families` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `head_id` int(10) unsigned DEFAULT NULL,
  `icon` varchar(10) DEFAULT '👨‍👩‍👧‍👦',
  `bg_color` varchar(30) DEFAULT 'var(--gold-pale)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `fk_family_head` (`head_id`),
  CONSTRAINT `fk_family_head` FOREIGN KEY (`head_id`) REFERENCES `members` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "Families table created.\n";

// Create member_families table
$db->exec("
CREATE TABLE IF NOT EXISTS `member_families` (
  `member_id` int(10) unsigned NOT NULL,
  `family_id` int(10) unsigned NOT NULL,
  `role` varchar(50) DEFAULT 'Member',
  `enrol_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `enrolled_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`member_id`,`family_id`),
  KEY `fk_mf_family` (`family_id`),
  KEY `fk_mf_admin` (`enrolled_by`),
  CONSTRAINT `fk_mf_admin` FOREIGN KEY (`enrolled_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_mf_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mf_family` FOREIGN KEY (`family_id`) REFERENCES `families` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "Member Families table created.\n";

// Alter attendance_sessions to support family_id
try {
    $db->exec("ALTER TABLE `attendance_sessions` ADD COLUMN `family_id` int(10) unsigned NULL AFTER `ministry_id`;");
    $db->exec("ALTER TABLE `attendance_sessions` ADD CONSTRAINT `fk_session_family` FOREIGN KEY (`family_id`) REFERENCES `families` (`id`) ON DELETE CASCADE;");
    // Also drop the uniq_session index that included ministry_id, and make it include both or we just ignore the unique key.
    // Let's drop it to avoid errors if session_date is same for different families/ministries.
    // Or we recreate it.
    try {
        $db->exec("ALTER TABLE `attendance_sessions` DROP INDEX `uniq_session`;");
    } catch(Exception $e) {}

    echo "attendance_sessions updated to support family_id.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "attendance_sessions already has family_id.\n";
    } else {
        echo "Error altering attendance_sessions: " . $e->getMessage() . "\n";
    }
}

// Seed families
$families = [
    ['prudence', 'Prudence', 'Prudence Family', '🛡️', '#FEF9EC'],
    ['temperance', 'Temperance', 'Temperance Family', '🕊️', '#ECFDF5'],
    ['fortitude', 'Fortitude', 'Fortitude Family', '⚔️', '#EEF2FF'],
    ['justice', 'Justice', 'Justice Family', '⚖️', 'var(--gold-pale)']
];

$stmt = $db->prepare("INSERT INTO families (slug, name, description, icon, bg_color) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name)");
foreach ($families as $f) {
    $stmt->execute($f);
}
echo "Default families seeded successfully.\n";

echo "Done!\n";
