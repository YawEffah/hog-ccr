<?php
/**
 * Bulk Member Import Handler
 * POST action: bulk_import_members
 * Accepts .xlsx or .csv, validates rows, inserts valid members in a transaction.
 */
require_once '../includes/auth.php';
requireAuth();
require_once '../includes/db.php';
require_once '../includes/helpers.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

verifyCsrf();

$action   = $_POST['action'] ?? '';
$redirect = '../members.php';

if ($action !== 'bulk_import_members') {
    redirect($redirect . '?error=unknown_action');
}

// ── Validate uploaded file ─────────────────────────────────────────────────────
if (empty($_FILES['import_file']['name']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
    redirect($redirect . '?error=no_file');
}

$uploadedFile = $_FILES['import_file'];
$ext          = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));

if (!in_array($ext, ['xlsx', 'csv'], true)) {
    redirect($redirect . '?error=invalid_file_type');
}

if ($uploadedFile['size'] > 10 * 1024 * 1024) { // 10 MB max
    redirect($redirect . '?error=file_too_large');
}

// ── Read rows from file ────────────────────────────────────────────────────────
$rows = [];

try {
    if ($ext === 'xlsx') {
        $spreadsheet = IOFactory::load($uploadedFile['tmp_name']);
        $sheet       = $spreadsheet->getActiveSheet();
        $data        = $sheet->toArray(null, true, true, false);

        // Row 0 = headers, skip it; also skip any example row that starts with 'Kwame' (the template example)
        array_shift($data); // remove header row
        foreach ($data as $row) {
            // Skip completely empty rows
            if (empty(array_filter($row, fn($v) => trim((string)$v) !== ''))) continue;
            $rows[] = array_values($row);
        }
    } else {
        // CSV
        $handle = fopen($uploadedFile['tmp_name'], 'r');
        // Detect and strip UTF-8 BOM
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") rewind($handle);

        $headerSkipped = false;
        while (($row = fgetcsv($handle)) !== false) {
            if (!$headerSkipped) { $headerSkipped = true; continue; }
            if (empty(array_filter($row, fn($v) => trim((string)$v) !== ''))) continue;
            $rows[] = $row;
        }
        fclose($handle);
    }
} catch (Throwable $e) {
    error_log('bulk_import read error: ' . $e->getMessage());
    redirect($redirect . '?error=parse_error');
}

if (empty($rows)) {
    redirect($redirect . '?error=empty_file');
}

// ── Lookup tables ──────────────────────────────────────────────────────────────
$db = getDB();

// Pre-load all ministry names → id (case-insensitive key)
$ministryMap = [];
foreach ($db->query("SELECT id, name FROM ministries")->fetchAll() as $m) {
    $ministryMap[mb_strtolower(trim($m['name']))] = (int)$m['id'];
}

// Pre-load existing phone numbers (to detect duplicates)
$existingPhones = [];
foreach ($db->query("SELECT phone FROM members WHERE phone IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN) as $p) {
    $existingPhones[trim($p)] = true;
}

// Allowed enum values
$allowedGenders    = ['male' => 'Male', 'female' => 'Female'];
$allowedStatuses   = ['active' => 'Active', 'inactive' => 'Inactive', 'affiliate community member' => 'Affiliate Community Member'];
$allowedMarital    = ['single' => 'Single', 'married' => 'Married', 'widowed' => 'Widowed', 'divorced' => 'Divorced'];
$allowedSacNeeded  = ['First Communion', 'Confirmation', 'Holy Matrimony', 'Holy Orders'];
$allowedProgs      = ['Life in the Spirit Seminar', 'Growth in the Spirit Seminar', 'Charisms Session', 'Catholic Alpha'];

// ── Process & validate each row ────────────────────────────────────────────────
$validMembers  = [];
$errorRows     = [];
$phonesThisBatch = []; // track phones within this upload for intra-file duplicates

foreach ($rows as $idx => $row) {
    $rowNum  = $idx + 2; // +2 because row 1 = header
    $errors  = [];

    // Map columns by position (matches template order)
    [$firstName, $lastName, $gender, $phone,
     $phone2, $email, $dob, $joinedDate,
     $status, $address, $homeTown, $occupation,
     $maritalStatus, $childrenCount, $baptised, $communicant,
     $groupMemberships, $nokName, $nokRelation, $nokAddress,
     $nokPhone, $ministriesRaw, $sacramentsNeededRaw, $programmesRaw] = array_pad(array_map('trim', array_map('strval', $row)), 24, '');

    // ── Required fields ────────────────────────────────
    if ($firstName === '') $errors[] = 'First Name is required';
    if ($lastName  === '') $errors[] = 'Last Name is required';

    // ── Gender ─────────────────────────────────────────
    $genderNorm = $allowedGenders[mb_strtolower($gender)] ?? null;
    if ($gender === '' ) {
        $genderNorm = 'Male'; // default
    } elseif ($genderNorm === null) {
        $errors[] = "Invalid Gender \"$gender\" — use Male or Female";
    }

    // ── Phone ──────────────────────────────────────────
    if ($phone === '') {
        $errors[] = 'Phone is required';
    } elseif (isset($existingPhones[$phone])) {
        $errors[] = "Phone $phone already exists in the system (duplicate)";
    } elseif (isset($phonesThisBatch[$phone])) {
        $errors[] = "Phone $phone appears more than once in this file (duplicate)";
    }

    // ── Dates ──────────────────────────────────────────
    $dobClean     = null;
    $joinedClean  = date('Y-m-d');

    if ($dob !== '') {
        $parsed = date_create_from_format('Y-m-d', $dob);
        if ($parsed && $parsed->format('Y-m-d') === $dob) {
            $dobClean = $dob;
        } else {
            $errors[] = "Invalid Date of Birth \"$dob\" — use YYYY-MM-DD";
        }
    }

    if ($joinedDate !== '') {
        $parsed = date_create_from_format('Y-m-d', $joinedDate);
        if ($parsed && $parsed->format('Y-m-d') === $joinedDate) {
            $joinedClean = $joinedDate;
        } else {
            $errors[] = "Invalid Joined Date \"$joinedDate\" — use YYYY-MM-DD";
        }
    }

    // ── Status ─────────────────────────────────────────
    $statusNorm = $allowedStatuses[mb_strtolower($status)] ?? 'Active';

    // ── Marital Status ─────────────────────────────────
    $maritalNorm = $allowedMarital[mb_strtolower($maritalStatus)] ?? null;

    // ── Children count ─────────────────────────────────
    $childrenCountInt = max(0, (int)$childrenCount);

    // ── Boolean fields ─────────────────────────────────
    $isBaptised   = (mb_strtolower($baptised)   === 'yes') ? 1 : 0;
    $isCommunicant = (mb_strtolower($communicant) === 'yes') ? 1 : 0;

    // ── Ministries ─────────────────────────────────────
    $ministryIds   = [];
    $ministryWarns = [];
    if ($ministriesRaw !== '') {
        foreach (explode(',', $ministriesRaw) as $mName) {
            $mKey = mb_strtolower(trim($mName));
            if (isset($ministryMap[$mKey])) {
                $ministryIds[] = $ministryMap[$mKey];
            } else {
                $ministryWarns[] = "Ministry \"" . trim($mName) . "\" not found — skipped";
            }
        }
    }

    // ── Sacraments needed ──────────────────────────────
    $sacramentsNeeded = [];
    if ($sacramentsNeededRaw !== '') {
        foreach (explode(',', $sacramentsNeededRaw) as $s) {
            $s = trim($s);
            if (in_array($s, $allowedSacNeeded, true)) {
                $sacramentsNeeded[] = $s;
            }
        }
    }

    // ── Programmes ─────────────────────────────────────
    $programmes = [];
    if ($programmesRaw !== '') {
        foreach (explode(',', $programmesRaw) as $p) {
            $p = trim($p);
            if (in_array($p, $allowedProgs, true)) {
                $programmes[] = $p;
            }
        }
    }

    // ── Collect or discard ─────────────────────────────
    if (!empty($errors)) {
        $errorRows[] = [
            'row'     => $rowNum,
            'name'    => trim("$firstName $lastName") ?: '—',
            'phone'   => $phone,
            'errors'  => implode('; ', $errors),
        ];
        continue;
    }

    // Track phone so intra-file duplicates on later rows are caught
    if ($phone !== '') $phonesThisBatch[$phone] = true;

    $validMembers[] = [
        'first_name'           => $firstName,
        'last_name'            => $lastName,
        'gender'               => $genderNorm,
        'phone'                => $phone,
        'phone2'               => $phone2  ?: null,
        'email'                => $email   ?: null,
        'dob'                  => $dobClean,
        'joined_date'          => $joinedClean,
        'status'               => $statusNorm,
        'address'              => $address       ?: null,
        'home_town'            => $homeTown      ?: null,
        'occupation'           => $occupation    ?: null,
        'marital_status'       => $maritalNorm,
        'children_count'       => $childrenCountInt,
        'is_baptised'          => $isBaptised,
        'is_communicant'       => $isCommunicant,
        'group_memberships'    => $groupMemberships ?: null,
        'next_of_kin_name'     => $nokName       ?: null,
        'next_of_kin_relation' => $nokRelation   ?: null,
        'next_of_kin_address'  => $nokAddress    ?: null,
        'next_of_kin_phone'    => $nokPhone      ?: null,
        'ministry_ids'         => $ministryIds,
        'sacraments_needed'    => $sacramentsNeeded,
        'programmes'           => $programmes,
        'ministry_warnings'    => $ministryWarns,
    ];
}

$totalRows    = count($rows);
$importedCount = 0;

// ── Batch insert in transaction ────────────────────────────────────────────────
if (!empty($validMembers)) {
    try {
        $db->beginTransaction();

        $insertStmt = $db->prepare(
            "INSERT INTO members
             (member_code, first_name, last_name, gender, phone, phone2, email, dob,
              address, home_town, occupation, marital_status, children_count,
              is_baptised, is_communicant, group_memberships,
              next_of_kin_name, next_of_kin_relation, next_of_kin_address, next_of_kin_phone,
              status, joined_date)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        $minStmt   = $db->prepare("INSERT IGNORE INTO member_ministries (member_id, ministry_id) VALUES (?,?)");
        $snStmt    = $db->prepare("INSERT IGNORE INTO member_sacraments_needed (member_id, sacrament) VALUES (?,?)");
        $progStmt  = $db->prepare("INSERT IGNORE INTO member_programmes (member_id, programme) VALUES (?,?)");

        foreach ($validMembers as $m) {
            $code = generateMemberCode();

            $insertStmt->execute([
                $code,
                $m['first_name'], $m['last_name'], $m['gender'],
                $m['phone'], $m['phone2'], $m['email'], $m['dob'],
                $m['address'], $m['home_town'], $m['occupation'],
                $m['marital_status'], $m['children_count'],
                $m['is_baptised'], $m['is_communicant'], $m['group_memberships'],
                $m['next_of_kin_name'], $m['next_of_kin_relation'],
                $m['next_of_kin_address'], $m['next_of_kin_phone'],
                $m['status'], $m['joined_date'],
            ]);

            $memberId = (int)$db->lastInsertId();

            foreach ($m['ministry_ids']      as $mId) $minStmt->execute([$memberId, $mId]);
            foreach ($m['sacraments_needed'] as $s)   $snStmt->execute([$memberId, $s]);
            foreach ($m['programmes']        as $p)   $progStmt->execute([$memberId, $p]);

            $importedCount++;
        }

        $db->commit();
        logActivity("Bulk imported {$importedCount} members from Excel/CSV upload", 'members');

    } catch (PDOException $e) {
        $db->rollBack();
        error_log('bulk_import DB error: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

// ── Store result in session for display ───────────────────────────────────────
$_SESSION['bulk_import_result'] = [
    'total'    => $totalRows,
    'imported' => $importedCount,
    'errors'   => $errorRows,
];

redirect($redirect . '?success=bulk_imported');
