<?php
require_once 'includes/db.php';
require_once 'includes/helpers.php';

$db = getDB();
echo "Checking notifications table...\n";
try {
    $stmt = $db->query("SELECT * FROM notifications");
    $notifs = $stmt->fetchAll();
    echo "Total notifications in DB: " . count($notifs) . "\n";
    print_r($notifs);
} catch (PDOException $e) {
    echo "Error querying notifications: " . $e->getMessage() . "\n";
}

echo "Testing notifyRoles manually...\n";
$res = notifyRoles(['Administrator', 'Secretary'], 'test', 'Test Title', 'Test Message');
echo "notifyRoles result: " . ($res ? 'true' : 'false') . "\n";
