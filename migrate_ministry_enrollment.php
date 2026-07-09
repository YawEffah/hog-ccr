<?php
/**
 * Migration Script: Update member_ministries table with welfare-like fields
 */
require_once 'includes/db.php';

echo "Starting member_ministries update migration...\n\n";

try {
    $db = getDB();

    $queries = [
        "ALTER TABLE member_ministries ADD COLUMN role VARCHAR(50) DEFAULT 'Member' AFTER ministry_id",
        "ALTER TABLE member_ministries ADD COLUMN enrol_date DATE NULL AFTER role",
        "ALTER TABLE member_ministries ADD COLUMN notes TEXT NULL AFTER enrol_date",
        "ALTER TABLE member_ministries ADD COLUMN enrolled_by INT UNSIGNED NULL AFTER notes",
        "ALTER TABLE member_ministries ADD CONSTRAINT fk_mm_admin FOREIGN KEY (enrolled_by) REFERENCES admins(id) ON DELETE SET NULL"
    ];

    foreach ($queries as $sql) {
        try {
            $db->exec($sql);
            echo "Executed: $sql\n";
        } catch (PDOException $e) {
            echo "Error executing: $sql\n";
            echo $e->getMessage() . "\n";
        }
    }

    echo "\nMigration completed.\n";

} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
}
