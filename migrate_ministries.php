<?php
require_once __DIR__ . '/includes/db.php';
try {
    $db = getDB();

    // 1. Create member_ministries table
    $db->exec("
        CREATE TABLE IF NOT EXISTS member_ministries (
            member_id INT UNSIGNED NOT NULL,
            ministry_id INT UNSIGNED NOT NULL,
            PRIMARY KEY (member_id, ministry_id),
            CONSTRAINT fk_mm_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
            CONSTRAINT fk_mm_ministry FOREIGN KEY (ministry_id) REFERENCES ministries(id) ON DELETE CASCADE
        )
    ");
    echo "Created table member_ministries.\n";

    // 2. Migrate existing data
    $stmt = $db->query("SELECT id, ministry_id FROM members WHERE ministry_id IS NOT NULL");
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $migrated = 0;
    
    $insertStmt = $db->prepare("INSERT IGNORE INTO member_ministries (member_id, ministry_id) VALUES (?, ?)");
    foreach ($members as $m) {
        $insertStmt->execute([$m['id'], $m['ministry_id']]);
        $migrated++;
    }
    echo "Migrated {$migrated} member-ministry relationships.\n";

    // 3. Drop foreign key constraint
    // Try to drop the standard constraint name 'fk_member_ministry'
    try {
        $db->exec("ALTER TABLE members DROP FOREIGN KEY fk_member_ministry");
        echo "Dropped fk_member_ministry constraint.\n";
    } catch (PDOException $e) {
        // Ignore if constraint doesn't exist
        echo "Notice: fk_member_ministry might not exist or already dropped.\n";
    }

    // 4. Drop column ministry_id
    try {
        $db->exec("ALTER TABLE members DROP COLUMN ministry_id");
        echo "Dropped ministry_id column.\n";
    } catch (PDOException $e) {
        echo "Notice: ministry_id column might already be dropped.\n";
    }

    echo "Migration complete.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
