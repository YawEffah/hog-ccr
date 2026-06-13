<?php
require_once 'includes/db.php';

try {
    $db = getDB();
    echo "Starting finance migration...\n";

    // 1. Add week_number column
    echo "Adding week_number column...\n";
    $db->exec("ALTER TABLE finance_transactions ADD COLUMN week_number ENUM('Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5') DEFAULT 'Week 1' AFTER id");

    // 2. Backfill existing data
    echo "Backfilling week_number based on transaction_date...\n";
    $stmt = $db->query("SELECT id, transaction_date FROM finance_transactions");
    $updateStmt = $db->prepare("UPDATE finance_transactions SET week_number = ? WHERE id = ?");
    
    $rows = $stmt->fetchAll();
    foreach ($rows as $row) {
        $day = (int)date('j', strtotime($row['transaction_date']));
        $weekNum = ceil($day / 7);
        if ($weekNum > 5) $weekNum = 5;
        $weekStr = 'Week ' . $weekNum;
        $updateStmt->execute([$weekStr, $row['id']]);
    }

    // 3. Drop foreign key
    echo "Dropping fk_txn_member...\n";
    try {
        $db->exec("ALTER TABLE finance_transactions DROP FOREIGN KEY fk_txn_member");
    } catch (Exception $e) {
        echo "FK drop failed or already dropped: " . $e->getMessage() . "\n";
    }

    // 4. Drop unneeded columns
    echo "Dropping member_id, member_name, phone, email...\n";
    $db->exec("ALTER TABLE finance_transactions DROP COLUMN member_id, DROP COLUMN member_name, DROP COLUMN phone, DROP COLUMN email");

    echo "Migration completed successfully!\n";

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
