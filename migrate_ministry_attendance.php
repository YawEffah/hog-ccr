<?php
/**
 * Migration: Add ministry_id to attendance_sessions
 * Run once to link attendance sessions to specific ministries.
 */
require_once 'includes/db.php';

$db = getDB();

try {
    // 1. Add ministry_id column
    $db->exec("ALTER TABLE attendance_sessions ADD COLUMN ministry_id INT UNSIGNED NULL AFTER session_type");
    echo "✓ Added ministry_id column to attendance_sessions.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "– ministry_id column already exists, skipping.\n";
    } else {
        echo "✗ Error adding column: " . $e->getMessage() . "\n";
        exit(1);
    }
}

try {
    // 2. Add foreign key constraint
    $db->exec("ALTER TABLE attendance_sessions ADD CONSTRAINT fk_session_ministry FOREIGN KEY (ministry_id) REFERENCES ministries(id) ON DELETE CASCADE");
    echo "✓ Added foreign key constraint fk_session_ministry.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'already exists') !== false) {
        echo "– Foreign key fk_session_ministry already exists, skipping.\n";
    } else {
        echo "✗ Error adding foreign key: " . $e->getMessage() . "\n";
    }
}

try {
    // 3. Drop the old unique key and create a new one that includes ministry_id
    $db->exec("ALTER TABLE attendance_sessions DROP INDEX uniq_session");
    echo "✓ Dropped old uniq_session index.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "check that it exists") !== false || strpos($e->getMessage(), "Can't DROP") !== false) {
        echo "– Old uniq_session index already dropped, skipping.\n";
    } else {
        echo "✗ Error dropping index: " . $e->getMessage() . "\n";
    }
}

try {
    $db->exec("ALTER TABLE attendance_sessions ADD UNIQUE KEY uniq_session (session_type, session_date, ministry_id)");
    echo "✓ Created new uniq_session index (session_type, session_date, ministry_id).\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate') !== false) {
        echo "– New uniq_session index already exists, skipping.\n";
    } else {
        echo "✗ Error creating index: " . $e->getMessage() . "\n";
    }
}

echo "\n✅ Migration complete.\n";
