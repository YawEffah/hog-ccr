<?php
require_once __DIR__ . '/includes/db.php';
try {
    $db = getDB();
    $db->exec("ALTER TABLE welfare_members ADD COLUMN family_group ENUM('Prudence','Temperance','Fortitude','Justice') NULL AFTER enrol_date");
    echo "Successfully added family_group to welfare_members table.\n";
} catch (PDOException $e) {
    if ($e->getCode() == '42S21') {
        echo "Column 'family_group' already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
