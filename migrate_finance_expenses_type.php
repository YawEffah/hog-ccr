<?php
require 'includes/db.php';
$db = getDB();

try {
    $db->beginTransaction();

    // 1. Drop foreign key and column from finance_expenses
    try {
        $db->exec("ALTER TABLE finance_expenses DROP FOREIGN KEY fk_fexp_category;");
    } catch (PDOException $e) {
        // FK might not exist or already dropped
    }
    
    try {
        $db->exec("ALTER TABLE finance_expenses DROP COLUMN category_id;");
    } catch (PDOException $e) {
        // Column might not exist
    }

    try {
        $db->exec("ALTER TABLE finance_expenses ADD COLUMN type VARCHAR(100) NOT NULL AFTER amount;");
    } catch (PDOException $e) {
        // Column might already exist
    }

    // 2. Seed standard General Expense accounts
    $accounts = [
        ['code' => '5200', 'name' => 'Utilities', 'type' => 'Expense', 'fund' => 'General'],
        ['code' => '5300', 'name' => 'Transportation', 'type' => 'Expense', 'fund' => 'General'],
        ['code' => '5400', 'name' => 'Stationery', 'type' => 'Expense', 'fund' => 'General'],
        ['code' => '5500', 'name' => 'Honorarium', 'type' => 'Expense', 'fund' => 'General'],
        ['code' => '5600', 'name' => 'Maintenance', 'type' => 'Expense', 'fund' => 'General'],
        ['code' => '5900', 'name' => 'Miscellaneous', 'type' => 'Expense', 'fund' => 'General']
    ];

    $stmt = $db->prepare("INSERT IGNORE INTO finance_accounts (code, name, type, fund) VALUES (?, ?, ?, ?)");
    foreach ($accounts as $acc) {
        $stmt->execute([$acc['code'], $acc['name'], $acc['type'], $acc['fund']]);
    }

    $db->commit();
    echo "Migration completed successfully.\n";

} catch (Exception $e) {
    $db->rollBack();
    echo "Migration failed: " . $e->getMessage() . "\n";
}
