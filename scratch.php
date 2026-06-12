<?php
require_once 'includes/db.php';
try {
    $db = getDB();
    $db->exec("ALTER TABLE members MODIFY COLUMN status ENUM('Active', 'Inactive', 'Visitor', 'Affiliate Community Member') DEFAULT 'Active'");
    echo "Success\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
