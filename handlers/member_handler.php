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
    $firstName      = trim($_POST['first_name']      ?? '');
    $lastName       = trim($_POST['last_name']       ?? '');
    $gender         = $_POST['gender']               ?? 'Male';
    $phone          = trim($_POST['phone']           ?? '');
    $phone2         = trim($_POST['phone2']          ?? '');
    $email          = trim($_POST['email']           ?? '');
    $dob            = $_POST['dob']                  ?? null;
    $address        = trim($_POST['address']         ?? '');
    $homeTown       = trim($_POST['home_town']       ?? '');
    $occupation     = trim($_POST['occupation']      ?? '');
    $maritalStatus  = $_POST['marital_status']       ?? null;
    $childrenCount  = (int)($_POST['children_count'] ?? 0);
    $isBaptised     = (int)($_POST['is_baptised']    ?? 0);
    $isCommunicant  = (int)($_POST['is_communicant'] ?? 0);
    $groupMemberships = trim($_POST['group_memberships'] ?? '');
    $nokName        = trim($_POST['next_of_kin_name']     ?? '');
    $nokRelation    = trim($_POST['next_of_kin_relation'] ?? '');
    $nokAddress     = trim($_POST['next_of_kin_address']  ?? '');
    $nokPhone       = trim($_POST['next_of_kin_phone']    ?? '');
    $ministries     = $_POST['ministries']           ?? [];
    $status         = $_POST['status']               ?? 'Active';
    $joined         = $_POST['joined_date']          ?? date('Y-m-d');
    $sacramentsNeeded = $_POST['sacraments_needed']  ?? [];
    $programmes     = $_POST['programmes']           ?? [];

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
             (member_code, first_name, last_name, gender, phone, phone2, email, dob,
              address, home_town, occupation, marital_status, children_count,
              is_baptised, is_communicant, group_memberships,
              next_of_kin_name, next_of_kin_relation, next_of_kin_address, next_of_kin_phone,
              status, photo_path, joined_date)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $code, $firstName, $lastName, $gender, $phone, $phone2 ?: null,
            $email ?: null, $dob ?: null, $address, $homeTown ?: null,
            $occupation ?: null, $maritalStatus ?: null, $childrenCount,
            $isBaptised, $isCommunicant, $groupMemberships ?: null,
            $nokName ?: null, $nokRelation ?: null, $nokAddress ?: null, $nokPhone ?: null,
            $status, $photoPath, $joined
        ]);

        $memberId = (int)$db->lastInsertId();

        // Insert ministries
        if (!empty($ministries)) {
            $minStmt = $db->prepare("INSERT IGNORE INTO member_ministries (member_id, ministry_id, role, enrol_date) VALUES (?, ?, 'Member', CURRENT_DATE)");
            foreach ($ministries as $mId) {
                if ((int)$mId > 0) $minStmt->execute([$memberId, (int)$mId]);
            }
        }

        // Insert sacraments needed
        $allowedSacNeeded = ['First Communion','Confirmation','Holy Matrimony','Holy Orders'];
        if (!empty($sacramentsNeeded)) {
            $snStmt = $db->prepare("INSERT IGNORE INTO member_sacraments_needed (member_id, sacrament) VALUES (?, ?)");
            foreach ($sacramentsNeeded as $s) {
                if (in_array($s, $allowedSacNeeded, true)) $snStmt->execute([$memberId, $s]);
            }
        }

        // Insert programmes attended
        $allowedProgs = ['Life in the Spirit Seminar','Growth in the Spirit Seminar','Charisms Session','Catholic Alpha'];
        if (!empty($programmes)) {
            $progStmt = $db->prepare("INSERT IGNORE INTO member_programmes (member_id, programme) VALUES (?, ?)");
            foreach ($programmes as $p) {
                if (in_array($p, $allowedProgs, true)) $progStmt->execute([$memberId, $p]);
            }
        }

        // Send Welcome Message
        if (isset($_POST['send_welcome'])) {
            sendWelcomeMessage([
                'name'  => "$firstName $lastName",
                'email' => $email,
                'phone' => $phone,
                'code'  => $code
            ]);
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
    $memberId       = (int)($_POST['member_id']      ?? 0);
    $firstName      = trim($_POST['first_name']      ?? '');
    $lastName       = trim($_POST['last_name']       ?? '');
    $gender         = $_POST['gender']               ?? 'Male';
    $phone          = trim($_POST['phone']           ?? '');
    $phone2         = trim($_POST['phone2']          ?? '');
    $email          = trim($_POST['email']           ?? '');
    $dob            = $_POST['dob']                  ?? null;
    $address        = trim($_POST['address']         ?? '');
    $homeTown       = trim($_POST['home_town']       ?? '');
    $occupation     = trim($_POST['occupation']      ?? '');
    $maritalStatus  = $_POST['marital_status']       ?? null;
    $childrenCount  = (int)($_POST['children_count'] ?? 0);
    $isBaptised     = (int)($_POST['is_baptised']    ?? 0);
    $isCommunicant  = (int)($_POST['is_communicant'] ?? 0);
    $groupMemberships = trim($_POST['group_memberships'] ?? '');
    $nokName        = trim($_POST['next_of_kin_name']     ?? '');
    $nokRelation    = trim($_POST['next_of_kin_relation'] ?? '');
    $nokAddress     = trim($_POST['next_of_kin_address']  ?? '');
    $nokPhone       = trim($_POST['next_of_kin_phone']    ?? '');
    $ministries     = $_POST['ministries']           ?? [];
    $status         = $_POST['status']               ?? 'Active';
    $sacramentsNeeded = $_POST['sacraments_needed']  ?? [];
    $programmes     = $_POST['programmes']           ?? [];

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
        $photoSql  = $photoPath ? ', photo_path=?' : '';
        $photoArgs = $photoPath ? [$photoPath] : [];

        $stmt = $db->prepare(
            "UPDATE members SET
             first_name=?, last_name=?, gender=?, phone=?, phone2=?, email=?, dob=?,
             address=?, home_town=?, occupation=?, marital_status=?, children_count=?,
             is_baptised=?, is_communicant=?, group_memberships=?,
             next_of_kin_name=?, next_of_kin_relation=?, next_of_kin_address=?, next_of_kin_phone=?,
             status=? {$photoSql}
             WHERE id=?"
        );
        $stmt->execute(array_merge([
            $firstName, $lastName, $gender, $phone, $phone2 ?: null,
            $email ?: null, $dob ?: null, $address, $homeTown ?: null,
            $occupation ?: null, $maritalStatus ?: null, $childrenCount,
            $isBaptised, $isCommunicant, $groupMemberships ?: null,
            $nokName ?: null, $nokRelation ?: null, $nokAddress ?: null, $nokPhone ?: null,
            $status
        ], $photoArgs, [$memberId]));

        // Sync ministries safely
        $currentMinsStmt = $db->prepare("SELECT ministry_id FROM member_ministries WHERE member_id = ?");
        $currentMinsStmt->execute([$memberId]);
        $existingMins = $currentMinsStmt->fetchAll(PDO::FETCH_COLUMN);
        $newMins = array_map('intval', $ministries);
        
        $toAddMins = array_diff($newMins, $existingMins);
        $toRemoveMins = array_diff($existingMins, $newMins);

        if (!empty($toRemoveMins)) {
            $placeholders = implode(',', array_fill(0, count($toRemoveMins), '?'));
            $delParams = array_merge([$memberId], $toRemoveMins);
            $db->prepare("DELETE FROM member_ministries WHERE member_id = ? AND ministry_id IN ($placeholders)")->execute($delParams);
        }

        if (!empty($toAddMins)) {
            $minStmt = $db->prepare("INSERT IGNORE INTO member_ministries (member_id, ministry_id, role, enrol_date) VALUES (?, ?, 'Member', CURRENT_DATE)");
            foreach ($toAddMins as $mId) {
                if ($mId > 0) $minStmt->execute([$memberId, $mId]);
            }
        }

        // Sync sacraments needed
        $db->prepare("DELETE FROM member_sacraments_needed WHERE member_id = ?")->execute([$memberId]);
        $allowedSacNeeded = ['First Communion','Confirmation','Holy Matrimony','Holy Orders'];
        if (!empty($sacramentsNeeded)) {
            $snStmt = $db->prepare("INSERT IGNORE INTO member_sacraments_needed (member_id, sacrament) VALUES (?, ?)");
            foreach ($sacramentsNeeded as $s) {
                if (in_array($s, $allowedSacNeeded, true)) $snStmt->execute([$memberId, $s]);
            }
        }

        // Sync programmes
        $db->prepare("DELETE FROM member_programmes WHERE member_id = ?")->execute([$memberId]);
        $allowedProgs = ['Life in the Spirit Seminar','Growth in the Spirit Seminar','Charisms Session','Catholic Alpha'];
        if (!empty($programmes)) {
            $progStmt = $db->prepare("INSERT IGNORE INTO member_programmes (member_id, programme) VALUES (?, ?)");
            foreach ($programmes as $p) {
                if (in_array($p, $allowedProgs, true)) $progStmt->execute([$memberId, $p]);
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
