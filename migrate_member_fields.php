<?php
/**
 * Migration: Expand members table + add member_programmes + member_sacraments_needed
 */
require_once __DIR__ . '/includes/db.php';
$db = getDB();

$steps = [
    // New columns on members table
    "ALTER TABLE members ADD COLUMN home_town VARCHAR(150) NULL AFTER address",
    "ALTER TABLE members ADD COLUMN phone2 VARCHAR(20) NULL AFTER phone",
    "ALTER TABLE members ADD COLUMN occupation VARCHAR(150) NULL AFTER email",
    "ALTER TABLE members ADD COLUMN marital_status ENUM('Single','Married','Widowed','Divorced') NULL AFTER occupation",
    "ALTER TABLE members ADD COLUMN children_count TINYINT UNSIGNED DEFAULT 0 AFTER marital_status",
    "ALTER TABLE members ADD COLUMN is_baptised TINYINT(1) DEFAULT 0 AFTER children_count",
    "ALTER TABLE members ADD COLUMN is_communicant TINYINT(1) DEFAULT 0 AFTER is_baptised",
    "ALTER TABLE members ADD COLUMN group_memberships TEXT NULL AFTER is_communicant",
    "ALTER TABLE members ADD COLUMN next_of_kin_name VARCHAR(150) NULL AFTER group_memberships",
    "ALTER TABLE members ADD COLUMN next_of_kin_relation VARCHAR(100) NULL AFTER next_of_kin_name",
    "ALTER TABLE members ADD COLUMN next_of_kin_address TEXT NULL AFTER next_of_kin_relation",
    "ALTER TABLE members ADD COLUMN next_of_kin_phone VARCHAR(20) NULL AFTER next_of_kin_address",

    // member_programmes table (Q14)
    "CREATE TABLE IF NOT EXISTS member_programmes (
        member_id  INT UNSIGNED NOT NULL,
        programme  ENUM('Life in the Spirit Seminar','Growth in the Spirit Seminar','Charisms Session','Catholic Alpha') NOT NULL,
        PRIMARY KEY (member_id, programme),
        CONSTRAINT fk_prog_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    // member_sacraments_needed table (Q13)
    "CREATE TABLE IF NOT EXISTS member_sacraments_needed (
        member_id  INT UNSIGNED NOT NULL,
        sacrament  ENUM('First Communion','Confirmation','Holy Matrimony','Holy Orders') NOT NULL,
        PRIMARY KEY (member_id, sacrament),
        CONSTRAINT fk_sacn_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",
];

$errors = [];
foreach ($steps as $sql) {
    try {
        $db->exec($sql);
        echo "✓ OK: " . substr(trim($sql), 0, 60) . "...\n";
    } catch (PDOException $e) {
        // 42S21 = column already exists, 42S01 = table already exists — safe to skip
        if (in_array($e->getCode(), ['42S21', '42S01', '42000'])) {
            echo "⚠ Skipped (already exists): " . substr(trim($sql), 0, 60) . "...\n";
        } else {
            $errors[] = $e->getMessage();
            echo "✗ ERROR: " . $e->getMessage() . "\n";
        }
    }
}

echo "\nMigration complete.\n";
if ($errors) {
    echo count($errors) . " error(s) occurred.\n";
}
