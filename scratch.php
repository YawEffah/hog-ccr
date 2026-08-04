<?php
require_once 'includes/db.php';
$db = getDB();

try {
    // Add column
    $db->exec("ALTER TABLE system_roles ADD COLUMN perm_manage_events BOOLEAN DEFAULT 0 AFTER perm_manage_members");
    
    // Update default roles
    // Secretary and Administrator get events
    $db->exec("UPDATE system_roles SET perm_manage_events = 1 WHERE name IN ('Administrator', 'Secretary')");
    
    echo "Database updated successfully.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
