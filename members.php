<?php
/**
 * Members Management Page
 */
require_once 'includes/auth.php';
requireAuth();
require_once 'includes/db.php';
require_once 'includes/helpers.php';

$pageTitle  = 'Members';
$activePage = 'members';

// Flash messages
$successMsg = flash('success');
$errorMsg   = flash('error');

// Decode ?success= / ?error= query params (redirect-based pattern)
if (!$successMsg && !$errorMsg) {
    $successLabels = [
        'member_added'   => 'Member registered successfully.',
        'member_updated' => 'Member profile updated.',
        'member_deleted' => 'Member deactivated successfully.',
    ];
    $errorLabels = [
        'missing_fields' => 'Please fill in all required fields.',
        'db_error'       => 'A database error occurred. Please try again.',
        'not_found'      => 'Member not found.',
    ];
    $successMsg = $successLabels[$_GET['success'] ?? ''] ?? '';
    $errorMsg   = $errorLabels[$_GET['error']   ?? ''] ?? '';
}

// ── Live DB queries ───────────────────────────────────────────────────────────
$db      = getDB();
$page    = currentPage();
$perPage = 20;
$offset  = paginationOffset($page, $perPage);

// Search + filter
$search    = trim($_GET['q']      ?? '');
$statusFilter = $_GET['status']   ?? '';

$where  = "WHERE 1=1";
$params = [];

if ($search) {
    $where .= " AND (m.member_code LIKE ? OR m.first_name LIKE ? OR m.last_name LIKE ?
                     OR m.phone LIKE ? OR min.name LIKE ?)";
    $s = "%{$search}%";
    $params = array_merge($params, [$s, $s, $s, $s, $s]);
}
if ($statusFilter && in_array($statusFilter, ['Active','Inactive','Visitor'], true)) {
    $where    .= " AND m.status = ?";
    $params[]  = $statusFilter;
}

// Total count
$countStmt = $db->prepare("SELECT COUNT(*) FROM members m LEFT JOIN ministries min ON m.ministry_id = min.id {$where}");
$countStmt->execute($params);
$total_members = (int)$countStmt->fetchColumn();

// Paged members - including sacraments group_concat
$stmt = $db->prepare(
    "SELECT m.*, min.name AS ministry_name,
            (SELECT GROUP_CONCAT(sacrament) FROM member_sacraments WHERE member_id = m.id) as sacraments
     FROM members m
     LEFT JOIN ministries min ON m.ministry_id = min.id
     {$where}
     ORDER BY m.created_at DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$rawMembers = $stmt->fetchAll();

// Map to display format
$avatarPalette = [
    ['bg' => 'var(--gold-pale)', 'color' => 'var(--gold)'],
    ['bg' => '#EEF2FF',          'color' => 'var(--deep)'],
    ['bg' => '#ECFDF5',          'color' => '#2E7D57'],
    ['bg' => '#F5F3FF',          'color' => 'var(--deep3)'],
];
$statusBadge   = ['Active' => 'badge-green', 'Inactive' => 'badge-red', 'Visitor' => 'badge-yellow'];
$ministryBadge = ['Music Ministry'=>'badge-purple','Youth Wing'=>'badge-blue','Evangelism'=>'badge-green',
                  'Intercessory'=>'badge-yellow','Prayer Group'=>'badge-gray','Executives'=>'badge-purple'];

$members = array_map(function($m, $i) use ($avatarPalette, $statusBadge, $ministryBadge) {
    $pal      = $avatarPalette[$i % count($avatarPalette)];
    $ministry = $m['ministry_name'] ?? 'None';
    return [
        'id'            => $m['member_code'],
        'db_id'         => $m['id'],
        'first_name'    => $m['first_name'],
        'last_name'     => $m['last_name'],
        'gender'        => $m['gender'] ?? 'Male',
        'initials'      => strtoupper(substr($m['first_name'],0,1) . substr($m['last_name'],0,1)),
        'phone'         => $m['phone'] ?? '—',
        'email'         => $m['email'] ?? '—',
        'ministry'      => $ministry,
        'ministry_id'   => $m['ministry_id'],
        'status'        => $m['status'],
        'status_class'  => $statusBadge[$m['status']] ?? 'badge-gray',
        'ministry_class'=> $ministryBadge[$ministry]  ?? 'badge-gray',
        'joined'        => $m['joined_date'] ? date('M Y', strtotime($m['joined_date'])) : '—',
        'joined_raw'    => $m['joined_date'],
        'dob'           => $m['dob'],
        'address'       => $m['address'],
        'avatar_bg'     => $pal['bg'],
        'avatar_color'  => $pal['color'],
        'photo_path'    => $m['photo_path'],
        'sacraments'    => $m['sacraments'] ? explode(',', $m['sacraments']) : [],
        'notes'         => $m['notes'] ?? '',
    ];
}, $rawMembers, array_keys($rawMembers));

$members_shown = count($members);

// Member Stats
$member_stats = [
    'total'  => (int)$db->query("SELECT COUNT(*) FROM members")->fetchColumn(),
    'active' => (int)$db->query("SELECT COUNT(*) FROM members WHERE status='Active'")->fetchColumn(),
    'male'   => (int)$db->query("SELECT COUNT(*) FROM members WHERE gender='Male'")->fetchColumn(),
    'female' => (int)$db->query("SELECT COUNT(*) FROM members WHERE gender='Female'")->fetchColumn(),
];

// Ministries for dropdown
$ministries = $db->query("SELECT id, name FROM ministries ORDER BY name")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">

<?php require_once 'includes/head.php'; ?>

<body>

  <?php require_once 'includes/sidebar.php'; ?>

  <!-- MAIN CONTENT -->
  <main id="main">

    <div id="page-members" class="page">
      <div class="topbar">
        <div style="display:flex;align-items:center;">
          <button class="mobile-toggle" onclick="toggleSidebar()">
            <i class="ph ph-list"></i>
          </button>
          <div class="topbar-title">Members</div>
        </div>
        <div class="topbar-actions">
          <button class="btn btn-outline btn-sm" id="notifBtn" onclick="toggleNotifications()">
            <i class="ph ph-bell"></i>
          </button>
          <?php include 'includes/notifications.php'; ?>
          <button class="btn btn-primary btn-sm" onclick="openModal('addMemberModal')">+ Add Member</button>
        </div>
      </div>

      <?php if ($successMsg): ?>
      <div class="alert alert-success" style="margin: 20px;"><?= $successMsg ?></div>
      <?php endif; ?>
      <?php if ($errorMsg): ?>
      <div class="alert alert-error" style="margin: 20px;"><?= $errorMsg ?></div>
      <?php endif; ?>

      <div class="content">
        <!-- Member Stats -->
        <div class="grid-4" style="margin-bottom:24px;">
          <div class="stat-card">
            <div class="accent-bar" style="background: var(--gold);"></div>
            <div class="label">Total Members</div>
            <div class="value"><?= $member_stats['total'] ?></div>
            <div class="icon-bg" style="background:var(--gold-pale);">
              <i class="ph ph-users" style="color:var(--gold); font-size: 20px;"></i>
            </div>
          </div>
          <div class="stat-card">
            <div class="accent-bar" style="background:var(--success);"></div>
            <div class="label">Active Members</div>
            <div class="value"><?= $member_stats['active'] ?></div>
            <div class="icon-bg" style="background:#ECFDF5;">
              <i class="ph ph-user-check" style="color:var(--success); font-size: 20px;"></i>
            </div>
          </div>
          <div class="stat-card">
            <div class="accent-bar" style="background:var(--deep);"></div>
            <div class="label">Male Members</div>
            <div class="value"><?= $member_stats['male'] ?></div>
            <div class="icon-bg" style="background:#EEF2FF;">
              <i class="ph ph-gender-male" style="color:var(--deep); font-size: 20px;"></i>
            </div>
          </div>
          <div class="stat-card">
            <div class="accent-bar" style="background:var(--deep3);"></div>
            <div class="label">Female Members</div>
            <div class="value"><?= $member_stats['female'] ?></div>
            <div class="icon-bg" style="background:#F5F3FF;">
              <i class="ph ph-gender-female" style="color:var(--deep3); font-size: 20px;"></i>
            </div>
          </div>
        </div>

        <div class="table-wrap">
          <div style="padding: 16px 20px; border-bottom: 1px solid #EDE8DF; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
            <form action="members.php" method="GET" style="display: flex; align-items: center; gap: 12px; flex: 1; min-width: 300px;">
              <div class="search-wrap" style="flex: 1; max-width: 320px;">
                <i class="ph ph-magnifying-glass"></i>
                <input class="search-input" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name, ID, or ministry…" style="width: 100%;">
              </div>
              <select class="form-control" name="status" style="width:140px;padding:9px 14px;" onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="Active" <?= $statusFilter === 'Active' ? 'selected' : '' ?>>Active</option>
                <option value="Inactive" <?= $statusFilter === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                <option value="Visitor" <?= $statusFilter === 'Visitor' ? 'selected' : '' ?>>Visitor</option>
              </select>
            </form>
            <div style="font-size: 13px; color: var(--muted);">
              Showing <strong id="membersCount"><?= $members_shown ?></strong> of <?= $total_members ?>
            </div>
          </div>
          <div class="table-responsive">
            <table id="membersTable">
              <thead>
              <tr>
                <th>Member</th>
                <th>Contact</th>
                <th>Ministry</th>
                <th>Status</th>
                <th>Joined</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="membersTbody">
              <?php foreach ($members as $m): ?>
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:12px;">
                    <?php if ($m['photo_path']): ?>
                      <div class="avatar" style="border:1px solid var(--border); overflow:hidden;">
                        <img src="<?= $m['photo_path'] ?>" style="width:100%; height:100%; object-fit:cover;">
                      </div>
                    <?php else: ?>
                      <div class="avatar" style="background:<?= $m['avatar_bg'] ?>;color:<?= $m['avatar_color'] ?>;"><?= $m['initials'] ?></div>
                    <?php endif; ?>
                    <div>
                      <div style="font-weight:500;"><?= htmlspecialchars($m['first_name'] . ' ' . $m['last_name']) ?></div>
                      <div style="font-size:11px;color:var(--muted);"><?= $m['id'] ?></div>
                    </div>
                  </div>
                </td>
                <td>
                  <div style="font-size:13px;"><?= $m['phone'] ?></div>
                  <div style="font-size:11px;color:var(--muted);"><?= htmlspecialchars($m['email']) ?></div>
                </td>
                <td><span class="badge <?= $m['ministry_class'] ?>"><?= htmlspecialchars($m['ministry']) ?></span></td>
                <td><span class="badge <?= $m['status_class'] ?>"><?= $m['status'] ?></span></td>
                <td style="font-size:12px;color:var(--muted);"><?= $m['joined'] ?></td>
                <td>
                   <div style="display:flex;gap:6px;">
                    <button class="btn-icon" onclick="editMember('<?= $m['id'] ?>')" title="View / Edit Profile">
                      <i class="ph ph-address-book"></i>
                    </button>
                    <button class="btn-icon" onclick="confirmDeleteMember(<?= $m['db_id'] ?>, '<?= htmlspecialchars(addslashes($m['first_name'] . ' ' . $m['last_name'])) ?>')" title="Deactivate member" style="color:#DC2626;">
                      <i class="ph ph-user-minus"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($members)): ?>
              <tr>
                <td colspan="6" style="text-align:center; padding: 40px; color: var(--muted);">No members found matching your search.</td>
              </tr>
              <?php endif; ?>
            </tbody>
            </table>
          </div>
        </div>

        <!-- Pagination -->
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:16px;font-size:13px;color:var(--muted);">
          <span>Page <?= $page ?> of <?= ceil($total_members / $perPage) ?: 1 ?></span>
          <div style="display:flex;gap:6px;">
            <?php if ($page > 1): ?>
              <a href="?page=<?= $page-1 ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>" class="btn btn-outline btn-sm"><i class="ph ph-caret-left"></i></a>
            <?php endif; ?>
            
            <button class="btn btn-primary btn-sm"><?= $page ?></button>
            
            <?php if ($total_members > ($page * $perPage)): ?>
              <a href="?page=<?= $page+1 ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>" class="btn btn-outline btn-sm"><i class="ph ph-caret-right"></i></a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

  </main>

  <?php require_once 'includes/modals/member_modals.php'; ?>

  <!-- Hidden delete-member form -->
  <form method="POST" action="handlers/member_handler.php" id="deleteMemberForm" style="display:none;">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="delete_member">
    <input type="hidden" name="member_id" id="deleteMemberId">
  </form>

  <script src="assets/js/main.js"></script>
  <script>
    const membersData = <?php echo json_encode(array_column($members, null, 'id')); ?>;

    function handlePreview(input, previewId, placeholderId) {
      if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
          const preview = document.getElementById(previewId);
          preview.src = e.target.result;
          preview.style.display = 'block';
          if (placeholderId) document.getElementById(placeholderId).style.opacity = '0';
        };
        reader.readAsDataURL(input.files[0]);
      }
    }


    function editMember(id) {
      const m = membersData[id];
      if (!m) return;

      const editPreview = document.getElementById('editPhotoPreview');
      if (m.photo_path) {
        editPreview.src = m.photo_path;
        editPreview.style.display = 'block';
        document.getElementById('editPhotoPlaceholder').style.opacity = '0';
      } else {
        editPreview.style.display = 'none';
        document.getElementById('editPhotoPlaceholder').style.opacity = '1';
      }

      document.getElementById('editMemberId').value = m.db_id;
      document.getElementById('editFirstName').value = m.first_name;
      document.getElementById('editLastName').value = m.last_name;
      document.getElementById('editGender').value = m.gender;
      document.getElementById('editPhone').value = m.phone !== '—' ? m.phone : '';
      document.getElementById('editEmail').value = m.email !== '—' ? m.email : '';
      document.getElementById('editDob').value = m.dob || '';
      document.getElementById('editMinistry').value = m.ministry_id || '';
      document.getElementById('editStatus').value = m.status;
      document.getElementById('editAddress').value = m.address || '';
      document.getElementById('editNotes').value   = m.notes  || '';

      // Checkboxes
      const sacs = ['baptised', 'confirmed', 'communion', 'matrimony', 'orders'];
      const sacMap = {
        'baptised': 'Baptised',
        'confirmed': 'Confirmed',
        'communion': 'First Communion',
        'matrimony': 'Matrimony',
        'orders': 'Orders'
      };
      
      sacs.forEach(s => {
        const el = document.getElementById('sac_' + s);
        if (el) el.checked = m.sacraments.includes(sacMap[s]);
      });

      openModal('editMemberModal');
    }

    function confirmDeleteMember(id, name) {
      showConfirmModal(
        'Deactivate Member',
        'Are you sure you want to deactivate ' + name + '? Their record will be preserved but their status set to Inactive.',
        'Deactivate',
        function() {
          document.getElementById('deleteMemberId').value = id;
          document.getElementById('deleteMemberForm').submit();
        },
        'danger'
      );
    }
  </script>
</body>
</html>
