<?php
/**
 * Background script to process message queue.
 * This can be triggered via AJAX invisibly.
 */

// Ignore user abort and remove time limits
ignore_user_abort(true);
set_time_limit(0);

// Start and immediately close session to avoid blocking
session_start();
session_write_close();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

// Avoid outputting errors to the response to keep it clean
ini_set('display_errors', 0);

header('Content-Type: application/json');

try {
    $db = getDB();

    // ── Atomically claim a batch of pending messages ──────────────────────────
    // BEGIN TRANSACTION + SELECT FOR UPDATE ensures that if multiple concurrent
    // requests (tabs, auto-triggers) hit this file simultaneously, only ONE of
    // them will lock and claim these rows. All others block until the COMMIT,
    // then see status = 'processing' and skip, preventing duplicate sends.
    $db->beginTransaction();

    $stmt = $db->prepare(
        "SELECT id, type, recipient, recipient_name, subject, body
         FROM message_queue
         WHERE status = 'pending'
         LIMIT 50
         FOR UPDATE"
    );
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$messages) {
        $db->commit();
        echo json_encode(['status' => 'empty']);
        exit;
    }

    // Immediately mark the fetched rows as 'processing' and release the lock
    $ids          = array_column($messages, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $db->prepare("UPDATE message_queue SET status = 'processing' WHERE id IN ($placeholders)")->execute($ids);

    // Commit releases the FOR UPDATE lock — other processes can now proceed
    // safely and will find no 'pending' rows in this batch.
    $db->commit();

    // ── Process the claimed messages outside the lock ─────────────────────────
    $sentCount   = 0;
    $failedCount = 0;

    foreach ($messages as $msg) {
        $success = false;

        if ($msg['type'] === 'email') {
            $success = executeEmailSend(
                $msg['recipient'],
                $msg['recipient_name'] ?? '',
                $msg['subject'] ?? 'Notification',
                $msg['body']
            );
        } elseif ($msg['type'] === 'sms') {
            $success = executeSmsSend($msg['recipient'], $msg['body']);
        }

        if ($success) {
            $db->prepare("UPDATE message_queue SET status = 'sent', error_log = NULL WHERE id = ?")->execute([$msg['id']]);
            $sentCount++;
        } else {
            $db->prepare("UPDATE message_queue SET status = 'failed', error_log = 'Failed to send' WHERE id = ?")->execute([$msg['id']]);
            $failedCount++;
        }
    }

    echo json_encode(['status' => 'done', 'sent' => $sentCount, 'failed' => $failedCount]);
} catch (Exception $e) {
    // Roll back any open transaction so locks aren't held indefinitely
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("process_queue error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
