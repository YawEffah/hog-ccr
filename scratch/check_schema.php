<?php
require_once 'includes/db.php';
$db = getDB();
$stmt = $db->query("DESCRIBE ministries");
print_r($stmt->fetchAll());
