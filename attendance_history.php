<?php
/**
 * Attendance History Page
 */
require_once 'includes/auth.php';
requireAuth();
require_once 'includes/db.php';
require_once 'includes/helpers.php';

$pageTitle  = 'Attendance History';
$activePage = 'attendance';

$db = getDB();

// ── Fetch Filter Options ─────────────────────────────────────────────────────
$ministries = $db->query("SELECT id, name FROM ministries ORDER BY name ASC")->fetchAll();
$sessionTypes = $db->query("SELECT DISTINCT session_type FROM attendance_sessions ORDER BY session_type ASC")->fetchAll(PDO::FETCH_COLUMN);

// ── Process Filters ──────────────────────────────────────────────────────────
$whereClauses = [];
$params = [];

$search      = trim($_GET['search'] ?? '');
$sessionType = trim($_GET['session_type'] ?? '');
$fromDate    = trim($_GET['from_date'] ?? '');
$toDate      = trim($_GET['to_date'] ?? '');
$ministryId  = trim($_GET['ministry_id'] ?? '');
$status      = trim($_GET['status'] ?? '');

if ($search) {
    $whereClauses[] = "(m.first_name LIKE ? OR m.last_name LIKE ? OR m.member_code LIKE ?)";
    $searchWildcard = "%{$search}%";
    array_push($params, $searchWildcard, $searchWildcard, $searchWildcard);
}

if ($sessionType) {
    $whereClauses[] = "s.session_type = ?";
    $params[] = $sessionType;
}

if ($fromDate) {
    $whereClauses[] = "s.session_date >= ?";
    $params[] = $fromDate;
}

if ($toDate) {
    $whereClauses[] = "s.session_date <= ?";
    $params[] = $toDate;
}

if ($ministryId) {
    $whereClauses[] = "m.ministry_id = ?";
    $params[] = $ministryId;
}

if ($status) {
    $whereClauses[] = "ar.status = ?";
    $params[] = $status;
}

$whereSql = '';
if (!empty($whereClauses)) {
    $whereSql = "WHERE " . implode(' AND ', $whereClauses);
}

// Pagination logic (optional but good practice, let's limit to 100 for now if no pagination)
$limit = 100;

// ── Query Records ────────────────────────────────────────────────────────────
$query = "
    SELECT 
        ar.id,
        ar.status,
        ar.check_in_time,
        m.first_name, 
        m.last_name, 
        m.member_code, 
        min.name AS ministry,
        s.session_type,
        s.session_date,
        s.session_time
    FROM attendance_records ar
    JOIN members m ON ar.member_id = m.id
    JOIN attendance_sessions s ON ar.session_id = s.id
    LEFT JOIN ministries min ON m.ministry_id = min.id
    $whereSql
    ORDER BY s.session_date DESC, s.session_time DESC, m.first_name ASC
    LIMIT $limit
";

$stmt = $db->prepare($query);
$stmt->execute($params);
$records = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">

<?php require_once 'includes/head.php'; ?>

<body>

  <?php require_once 'includes/sidebar.php'; ?>

  <!-- MAIN CONTENT -->
  <main id="main">

    <div id="page-attendance-history" class="page">
      <div class="topbar">
        <div style="display:flex;align-items:center;">
          <button class="mobile-toggle" onclick="toggleSidebar()">
            <i class="ph ph-list"></i>
          </button>
          <div class="topbar-title">Attendance History</div>
        </div>
        <div class="topbar-actions">
          <a href="attendance.php" class="btn btn-outline btn-sm">
            <i class="ph ph-arrow-left"></i> Back to Dashboard
          </a>

        </div>
      </div>

      <div class="content">
        <div class="card" style="margin-bottom: 24px;">
          <div class="card-header" style="cursor: pointer; display: flex; justify-content: space-between; align-items: center;" onclick="toggleFilters()">
            <h3>Filter Records</h3>
            <button class="btn btn-outline btn-sm" style="border: none; padding: 4px;" id="filterToggleBtn">
              <i class="ph ph-caret-down"></i>
            </button>
          </div>
          <div class="card-body" id="filterCardBody" style="display: none;">
            <form method="GET" action="attendance_history.php" class="grid-4" style="gap:16px;">
              
              <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Search Member</label>
                <input type="text" name="search" class="form-control" placeholder="Name or ID..." value="<?= htmlspecialchars($search) ?>">
              </div>

              <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Session Type</label>
                <select name="session_type" class="form-control">
                  <option value="">All Sessions</option>
                  <?php foreach ($sessionTypes as $type): ?>
                  <option value="<?= htmlspecialchars($type) ?>" <?= $sessionType === $type ? 'selected' : '' ?>><?= htmlspecialchars($type) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Ministry</label>
                <select name="ministry_id" class="form-control">
                  <option value="">All Ministries</option>
                  <?php foreach ($ministries as $min): ?>
                  <option value="<?= $min['id'] ?>" <?= (string)$ministryId === (string)$min['id'] ? 'selected' : '' ?>><?= htmlspecialchars($min['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                  <option value="">All Statuses</option>
                  <option value="Present" <?= $status === 'Present' ? 'selected' : '' ?>>Present</option>
                  <option value="Absent" <?= $status === 'Absent' ? 'selected' : '' ?>>Absent</option>
                  <option value="Visitor" <?= $status === 'Visitor' ? 'selected' : '' ?>>Visitor</option>
                </select>
              </div>

              <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">From Date</label>
                <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($fromDate) ?>">
              </div>

              <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">To Date</label>
                <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($toDate) ?>">
              </div>

              <div class="form-group" style="margin-bottom:0; display:flex; align-items:flex-end;">
                <button type="submit" class="btn btn-primary" style="width:100%;">
                  <i class="ph ph-funnel"></i> Apply Filters
                </button>
              </div>

              <div class="form-group" style="margin-bottom:0; display:flex; align-items:flex-end;">
                <a href="attendance_history.php" class="btn btn-outline" style="width:100%; text-align:center;">
                  Clear
                </a>
              </div>
            </form>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <h3>Attendance Records</h3>
            <div style="font-size:13px; color:var(--muted);">
              Showing <?= count($records) ?> result(s) <?= count($records) === $limit ? '(Limit reached)' : '' ?>
            </div>
          </div>
          <div class="table-responsive">
            <table>
              <thead>
                <tr>
                  <th>Date & Session</th>
                  <th>Member</th>
                  <th>Ministry</th>
                  <th>Status</th>
                  <th>Check-In Time</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($records)): ?>
                <tr>
                  <td colspan="5" style="text-align:center; padding: 40px; color:var(--muted);">
                    No attendance records found matching your criteria.
                  </td>
                </tr>
                <?php else: ?>
                  <?php foreach ($records as $r): ?>
                  <tr>
                    <td>
                      <div style="font-weight:500; color:var(--deep);"><?= date('M j, Y', strtotime($r['session_date'])) ?></div>
                      <div style="font-size:12px; color:var(--muted);"><?= htmlspecialchars($r['session_type']) ?></div>
                    </td>
                    <td>
                      <div style="font-weight:500;"><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></div>
                      <div style="font-size:12px; color:var(--muted);"><?= htmlspecialchars($r['member_code']) ?></div>
                    </td>
                    <td>
                      <span class="badge badge-gray"><?= htmlspecialchars($r['ministry'] ?? 'None') ?></span>
                    </td>
                    <td>
                      <?php
                        if ($r['status'] === 'Present') $sBadge = 'badge-green';
                        elseif ($r['status'] === 'Absent') $sBadge = 'badge-red';
                        else $sBadge = 'badge-yellow';
                      ?>
                      <span class="badge <?= $sBadge ?>"><?= $r['status'] ?></span>
                    </td>
                    <td style="font-size:13px; color:var(--muted);">
                      <?= $r['check_in_time'] ? date('g:ia', strtotime($r['check_in_time'])) : '—' ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>

  </main>

  <script src="assets/js/main.js"></script>
  <script>
    function toggleFilters() {
      const body = document.getElementById('filterCardBody');
      const btn = document.getElementById('filterToggleBtn');
      const icon = btn.querySelector('i');
      
      if (body.style.display === 'none') {
        body.style.display = 'block';
        icon.classList.remove('ph-caret-down');
        icon.classList.add('ph-caret-up');
      } else {
        body.style.display = 'none';
        icon.classList.remove('ph-caret-up');
        icon.classList.add('ph-caret-down');
      }
    }
  </script>
</body>
</html>
