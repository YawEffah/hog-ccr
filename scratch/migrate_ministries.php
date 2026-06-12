<?php
require_once 'includes/db.php';
$db = getDB();

try {
    // Add head_id column if not exists
    $db->exec("ALTER TABLE ministries ADD COLUMN head_id INT UNSIGNED NULL AFTER description");
    
    // Add foreign key constraint
    $db->exec("ALTER TABLE ministries ADD CONSTRAINT fk_ministry_head FOREIGN KEY (head_id) REFERENCES members(id) ON DELETE SET NULL ON UPDATE CASCADE");
    
    echo "Migration successful: head_id added to ministries table.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Migration skipped: Column already exists.\n";
    } else {
        echo "Migration failed: " . $e->getMessage() . "\n";
    }
}
