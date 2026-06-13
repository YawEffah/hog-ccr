<?php
require_once __DIR__ . '/includes/db.php';
try {
    $db = getDB();
    $db->exec("ALTER TABLE admins MODIFY COLUMN role ENUM('Administrator','Secretary','Finance Secretary','Head Pastor') DEFAULT 'Secretary'");
    echo "Successfully updated role ENUM in admins table.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
