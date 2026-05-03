<?php
/**
 * Member Handler — Add & Edit Members
 * POST actions: add_member | edit_member
 */
require_once '../includes/auth.php';
requireAuth();
require_once '../includes/db.php';
require_once '../includes/helpers.php';

verifyCsrf();

$action   = $_POST['action'] ?? '';
$db       = getDB();
$redirect = '../members.php';

// ── ADD MEMBER ────────────────────────────────────────────────────────────────
if ($action === 'add_member') {
    $firstName  = trim($_POST['first_name'] ?? '');
    $lastName   = trim($_POST['last_name']  ?? '');
    $gender     = $_POST['gender']     ?? 'Male';
    $phone      = trim($_POST['phone']  ?? '');
    $email      = trim($_POST['email']  ?? '');
    $dob        = $_POST['dob']         ?? null;
    $address    = trim($_POST['address'] ?? '');
    $ministryId = !empty($_POST['ministry_id']) ? (int)$_POST['ministry_id'] : null;
    $status     = $_POST['status']      ?? 'Active';
    $joined     = $_POST['joined_date'] ?? date('Y-m-d');
    $notes      = trim($_POST['notes']  ?? '');
    $sacraments = $_POST['sacraments']  ?? [];

    if (!$firstName || !$lastName || !$gender) {
        redirect($redirect . '?error=missing_fields');
    }

    $code = generateMemberCode();

    // Handle photo upload
    $photoPath = null;
    if (!empty($_FILES['photo']['name'])) {
        $photoPath = uploadMemberPhoto($_FILES['photo'], $code);
    }

    try {
        $stmt = $db->prepare(
            "INSERT INTO members
             (member_code, first_name, last_name, gender, phone, email, dob, address,
              ministry_id, status, photo_path, joined_date, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $code, $firstName, $lastName, $gender, $phone, $email ?: null,
            $dob ?: null, $address, $ministryId, $status, $photoPath, $joined, $notes ?: null
        ]);

        $memberId = (int)$db->lastInsertId();

        // Insert sacraments
        if (!empty($sacraments)) {
            $sacStmt = $db->prepare(
                "INSERT IGNORE INTO member_sacraments (member_id, sacrament) VALUES (?, ?)"
            );
            $allowed = ['Baptised','Confirmed','First Communion','Matrimony','Orders'];
            foreach ($sacraments as $s) {
                if (in_array($s, $allowed, true)) {
                    $sacStmt->execute([$memberId, $s]);
                }
            }
        }

        logActivity("Added member: {$firstName} {$lastName} ({$code})", 'members');
        redirect($redirect . '?success=member_added');

    } catch (PDOException $e) {
        error_log('add_member error: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

// ── EDIT MEMBER ───────────────────────────────────────────────────────────────
if ($action === 'edit_member') {
    $memberId   = (int)($_POST['member_id'] ?? 0);
    $firstName  = trim($_POST['first_name'] ?? '');
    $lastName   = trim($_POST['last_name']  ?? '');
    $gender     = $_POST['gender']     ?? 'Male';
    $phone      = trim($_POST['phone']  ?? '');
    $email      = trim($_POST['email']  ?? '');
    $dob        = $_POST['dob']         ?? null;
    $address    = trim($_POST['address'] ?? '');
    $ministryId = !empty($_POST['ministry_id']) ? (int)$_POST['ministry_id'] : null;
    $status     = $_POST['status']      ?? 'Active';
    $notes      = trim($_POST['notes']  ?? '');
    $sacraments = $_POST['sacraments']  ?? [];

    if (!$memberId || !$firstName || !$lastName) {
        redirect($redirect . '?error=missing_fields');
    }

    // Fetch current code for photo naming
    $codeRow = $db->prepare("SELECT member_code FROM members WHERE id = ?");
    $codeRow->execute([$memberId]);
    $code = $codeRow->fetchColumn();

    if (!$code) {
        redirect($redirect . '?error=not_found');
    }

    // Handle photo upload
    $photoPath = null;
    if (!empty($_FILES['photo']['name'])) {
        $photoPath = uploadMemberPhoto($_FILES['photo'], $code);
    }

    try {
        if ($photoPath) {
            $stmt = $db->prepare(
                "UPDATE members SET first_name=?, last_name=?, gender=?, phone=?, email=?,
                 dob=?, address=?, ministry_id=?, status=?, notes=?, photo_path=?
                 WHERE id=?"
            );
            $stmt->execute([
                $firstName, $lastName, $gender, $phone, $email ?: null,
                $dob ?: null, $address, $ministryId, $status, $notes ?: null, $photoPath, $memberId
            ]);
        } else {
            $stmt = $db->prepare(
                "UPDATE members SET first_name=?, last_name=?, gender=?, phone=?, email=?,
                 dob=?, address=?, ministry_id=?, status=?, notes=?
                 WHERE id=?"
            );
            $stmt->execute([
                $firstName, $lastName, $gender, $phone, $email ?: null,
                $dob ?: null, $address, $ministryId, $status, $notes ?: null, $memberId
            ]);
        }

        // Sync sacraments — delete all then re-insert
        $db->prepare("DELETE FROM member_sacraments WHERE member_id = ?")->execute([$memberId]);
        if (!empty($sacraments)) {
            $sacStmt = $db->prepare(
                "INSERT IGNORE INTO member_sacraments (member_id, sacrament) VALUES (?, ?)"
            );
            $allowed = ['Baptised','Confirmed','First Communion','Matrimony','Orders'];
            foreach ($sacraments as $s) {
                if (in_array($s, $allowed, true)) {
                    $sacStmt->execute([$memberId, $s]);
                }
            }
        }

        logActivity("Updated member: {$firstName} {$lastName} ({$code})", 'members');
        redirect($redirect . '?success=member_updated');

    } catch (PDOException $e) {
        error_log('edit_member error: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

// ── DELETE MEMBER (soft delete) ──────────────────────────────────────────────
if ($action === 'delete_member') {
    $memberId = (int)($_POST['member_id'] ?? 0);

    if (!$memberId) {
        redirect($redirect . '?error=missing_fields');
    }

    try {
        $nameRow = $db->prepare("SELECT CONCAT(first_name,' ',last_name) FROM members WHERE id=?");
        $nameRow->execute([$memberId]);
        $fullName = $nameRow->fetchColumn() ?: 'Unknown';

        $db->prepare("UPDATE members SET status='Inactive' WHERE id=?")->execute([$memberId]);

        logActivity("Deactivated member: {$fullName}", 'members');
        redirect($redirect . '?success=member_deleted');

    } catch (PDOException $e) {
        error_log('delete_member error: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

redirect($redirect . '?error=unknown_action');
