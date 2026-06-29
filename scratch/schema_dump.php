<?php
require 'includes/db.php';
$db = getDB();
$tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) {
    echo "TABLE: $t\n";
    $cols = $db->query("DESCRIBE `$t`")->fetchAll();
    foreach ($cols as $c) {
        echo "  " . $c['Field'] . " - " . $c['Type'] . "\n";
    }
}
