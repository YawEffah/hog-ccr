<?php
require_once __DIR__ . '/includes/db.php';
try {
    $db = getDB();
    $db->exec("ALTER TABLE admins ADD COLUMN phone VARCHAR(20) NULL AFTER email");
    echo "Successfully added phone column to admins table.\n";
} catch (PDOException $e) {
    if ($e->getCode() == '42S21') {
        echo "Column 'phone' already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
