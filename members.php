<?php
/**
 * Members Management Page
 */
require_once 'includes/auth.php';
requireAuth();

$pageTitle = 'Members';
$activePage = 'members';


// Mock data for initial refactor (Backend team will replace these)
$members = $members ?? [
    ['id' => 'CCR-001', 'first_name' => 'Abena', 'last_name' => 'Kusi', 'initials' => 'AK', 'phone' => '0244-123-456', 'email' => 'abena@email.com', 'ministry' => 'Music', 'status' => 'Active', 'status_class' => 'badge-green', 'ministry_class' => 'badge-purple', 'joined' => 'Jan 2023', 'avatar_bg' => 'var(--gold-pale)', 'avatar_color' => 'var(--gold)'],
    ['id' => 'CCR-002', 'first_name' => 'Kwame', 'last_name' => 'Ofori', 'initials' => 'KO', 'phone' => '0200-987-654', 'email' => 'kwame@email.com', 'ministry' => 'Youth', 'status' => 'Active', 'status_class' => 'badge-green', 'ministry_class' => 'badge-blue', 'joined' => 'Mar 2023', 'avatar_bg' => '#EEF2FF', 'avatar_color' => 'var(--deep)'],
    ['id' => 'CCR-003', 'first_name' => 'Serwa', 'last_name' => 'Acheampong', 'initials' => 'SA', 'phone' => '0555-234-789', 'email' => 'serwa@email.com', 'ministry' => 'None', 'status' => 'Visitor', 'status_class' => 'badge-yellow', 'ministry_class' => 'badge-gray', 'joined' => 'Apr 2026', 'avatar_bg' => 'var(--gold-pale)', 'avatar_color' => 'var(--gold)'],
    ['id' => 'CCR-004', 'first_name' => 'Michael', 'last_name' => 'Boateng', 'initials' => 'MB', 'phone' => '0277-456-123', 'email' => 'michael@email.com', 'ministry' => 'Evangelism', 'status' => 'Active', 'status_class' => 'badge-green', 'ministry_class' => 'badge-green', 'joined' => 'Feb 2024', 'avatar_bg' => '#ECFDF5', 'avatar_color' => 'var(--success)'],
    ['id' => 'CCR-005', 'first_name' => 'Efua', 'last_name' => 'Asare', 'initials' => 'EA', 'phone' => '0244-678-901', 'email' => 'efua@email.com', 'ministry' => 'Intercessory', 'status' => 'Inactive', 'status_class' => 'badge-red', 'ministry_class' => 'badge-yellow', 'joined' => 'Sep 2022', 'avatar_bg' => 'var(--gold-pale)', 'avatar_color' => 'var(--gold)'],
    ['id' => 'CCR-006', 'first_name' => 'Pastor', 'last_name' => 'Adu', 'initials' => 'PA', 'phone' => '0201-000-001', 'email' => 'pastor@ccrhog.org', 'ministry' => 'Executives', 'status' => 'Active', 'status_class' => 'badge-green', 'ministry_class' => 'badge-purple', 'joined' => 'Jan 2015', 'avatar_bg' => '#EEF2FF', 'avatar_color' => 'var(--deep)']
];

$total_members = $total_members ?? 487;
$members_shown = count($members);

$member_stats = $member_stats ?? [
    'total' => 487,
    'active' => 412,
    'male' => 210,
    'female' => 277
];
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
      <div class="content">
        <!-- Member Stats -->
        <div class="grid-4" style="margin-bottom:24px;">
          <div class="stat-card">
            <div class="accent-bar" style="background: var(--gold);"></div>
            <div class="label">Total Members</div>
            <div class="value"><?= $member_stats['total'] ?></div>
            <div class="change" style="color:var(--success);">↑ 12 this month</div>
            <div class="icon-bg" style="background:var(--gold-pale);">
              <i class="ph ph-users" style="color:var(--gold); font-size: 20px;"></i>
            </div>
          </div>
          <div class="stat-card">
            <div class="accent-bar" style="background:var(--success);"></div>
            <div class="label">Active Members</div>
            <div class="value"><?= $member_stats['active'] ?></div>
            <div class="change" style="color:var(--success);">85% of total</div>
            <div class="icon-bg" style="background:#ECFDF5;">
              <i class="ph ph-user-check" style="color:var(--success); font-size: 20px;"></i>
            </div>
          </div>
          <div class="stat-card">
            <div class="accent-bar" style="background:var(--deep);"></div>
            <div class="label">Male Members</div>
            <div class="value"><?= $member_stats['male'] ?></div>
            <div class="change" style="color:var(--deep);"><?= round(($member_stats['male']/$member_stats['total'])*100) ?>% of total</div>
            <div class="icon-bg" style="background:#EEF2FF;">
              <i class="ph ph-gender-male" style="color:var(--deep); font-size: 20px;"></i>
            </div>
          </div>
          <div class="stat-card">
            <div class="accent-bar" style="background:var(--deep3);"></div>
            <div class="label">Female Members</div>
            <div class="value"><?= $member_stats['female'] ?></div>
            <div class="change" style="color:var(--deep3);"><?= round(($member_stats['female']/$member_stats['total'])*100) ?>% of total</div>
            <div class="icon-bg" style="background:#F5F3FF;">
              <i class="ph ph-gender-female" style="color:var(--deep3); font-size: 20px;"></i>
            </div>
          </div>
        </div>

        <div class="table-wrap">
          <div style="padding: 16px 20px; border-bottom: 1px solid #EDE8DF; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 12px; flex: 1; min-width: 300px;">
              <div class="search-wrap" style="flex: 1; max-width: 320px;">
                <i class="ph ph-magnifying-glass"></i>
                <input class="search-input" placeholder="Search by name, ID, or ministry…" id="memberSearch" oninput="filterMembers()" style="width: 100%;">
              </div>
              <select class="form-control" id="statusFilter" style="width:140px;padding:9px 14px;" onchange="filterMembers()">
                <option value="All Status">All Status</option>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
                <option value="Visitor">Visitor</option>
              </select>
            </div>
            <div style="font-size: 13px; color: var(--muted);">
              Total Members: <strong id="membersCount"><?= $members_shown ?></strong>
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
                    <div class="avatar" style="background:<?= $m['avatar_bg'] ?>;color:<?= $m['avatar_color'] ?>;"><?= $m['initials'] ?></div>
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
                    <button class="btn-icon" onclick="viewMember('<?= $m['id'] ?>')" title="View details">
                      <i class="ph ph-eye"></i>
                    </button>
                    <button class="btn-icon" onclick="editMember('<?= $m['id'] ?>')" title="Edit member">
                      <i class="ph ph-pencil"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
            </table>
          </div>
        </div>
        <div
          style="display:flex;justify-content:space-between;align-items:center;margin-top:16px;font-size:13px;color:var(--muted);">
          <span>Showing <?= $members_shown ?> of <?= $total_members ?> members</span>
          <div style="display:flex;gap:6px;">
            <button class="btn btn-outline btn-sm"><i class="ph ph-caret-left"></i></button>
            <button class="btn btn-primary btn-sm">1</button>
            <button class="btn btn-outline btn-sm">2</button>
            <button class="btn btn-outline btn-sm">3</button>
            <button class="btn btn-outline btn-sm"><i class="ph ph-caret-right"></i></button>
          </div>
        </div>
      </div>
    </div>

  </main>

  <?php require_once 'includes/modals/member_modals.php'; ?>

  <script src="assets/js/main.js"></script>
  <script>
    // Mock data for members (TODO: Backend should ideally populate this via JSON or API)
    const membersData = <?php echo json_encode(array_column($members, null, 'id')); ?>;

    /**
     * Note: In a real backend implementation, membersData would likely be 
     * simplified or the modals would fetch data via AJAX.
     * For now, we keep the JS behavior compatible with the PHP rendering.
     */
     // Adapt the mock data structure for common JS functions if needed
     Object.keys(membersData).forEach(id => {
       const m = membersData[id];
       membersData[id] = {
         fn: m.first_name,
         ln: m.last_name,
         phone: m.phone,
         email: m.email,
         ministry: m.ministry === 'Music' ? 'Music Ministry' : (m.ministry === 'Youth' ? 'Youth Wing' : m.ministry),
         status: m.status,
         dob: m.dob || '1990-01-01', // Fallback for mock
         joined: m.joined,
         address: m.address || 'Street Name, City',
         sacraments: m.sacraments || [],
         photo: m.photo || null
       };
     });

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

    function filterMembers() {
      const q = document.getElementById('memberSearch').value.toLowerCase();
      const status = document.getElementById('statusFilter').value;
      let visibleCount = 0;

      document.querySelectorAll('#membersTbody tr').forEach(row => {
        const text = row.textContent.toLowerCase();
        const rowStatus = row.querySelector('td:nth-child(4) .badge').textContent.trim();
        
        const matchesQuery = text.includes(q);
        const matchesStatus = status === 'All Status' || rowStatus === status;

        if (matchesQuery && matchesStatus) {
          row.style.display = '';
          visibleCount++;
        } else {
          row.style.display = 'none';
        }
      });
      
      document.getElementById('membersCount').textContent = visibleCount;
    }

    function viewMember(id) {
      const m = membersData[id];
      if (!m) return;

      // Photo handling
      const photoBox = document.getElementById('viewPhotoContainer');
      const photoImg = document.getElementById('viewPhoto');
      const avatarBox = document.getElementById('viewAvatar');

      if (m.photo) {
        photoImg.src = m.photo;
        photoBox.style.display = 'block';
        avatarBox.style.display = 'none';
      } else {
        avatarBox.textContent = m.fn[0] + m.ln[0];
        avatarBox.style.display = 'flex';
        photoBox.style.display = 'none';
      }
      document.getElementById('viewName').textContent = m.fn + ' ' + m.ln;
      document.getElementById('viewId').textContent = id;
      document.getElementById('viewPhone').textContent = m.phone;
      document.getElementById('viewEmail').textContent = m.email;
      document.getElementById('viewDob').textContent = m.dob;
      document.getElementById('viewJoined').textContent = m.joined;
      document.getElementById('viewAddress').textContent = m.address;

      // Status Badge
      const statusClass = m.status === 'Active' ? 'badge-green' : (m.status === 'Visitor' ? 'badge-yellow' : 'badge-red');
      document.getElementById('viewStatus').innerHTML = `<span class="badge ${statusClass}">${m.status}</span>`;

      // Ministry Badge
      document.getElementById('viewMinistry').innerHTML = `<span class="badge badge-blue">${m.ministry}</span>`;

      // Sacraments
      const sacDiv = document.getElementById('viewSacraments');
      sacDiv.innerHTML = '';
      ['Baptised', 'Confirmed', 'First Communion'].forEach(s => {
        const has = m.sacraments.includes(s);
        sacDiv.innerHTML += `<span class="badge ${has ? 'badge-green' : 'badge-gray'}">${s}</span>`;
      });

      document.getElementById('viewEditBtn').onclick = () => {
        closeModal('viewMemberModal');
        editMember(id);
      };

      openModal('viewMemberModal');
    }

    function editMember(id) {
      const m = membersData[id];
      if (!m) return;

      // Photo handling
      const editPreview = document.getElementById('editPhotoPreview');
      if (m.photo) {
        editPreview.src = m.photo;
        editPreview.style.display = 'block';
      } else {
        editPreview.style.display = 'none';
      }

      document.getElementById('editMemberId').value = id;
      document.getElementById('editFn').value = m.fn;
      document.getElementById('editLn').value = m.ln;
      document.getElementById('editPhone').value = m.phone;
      document.getElementById('editEmail').value = m.email;
      document.getElementById('editDob').value = m.dob;
      document.getElementById('editMinistry').value = m.ministry;
      document.getElementById('editStatus').value = m.status;
      document.getElementById('editAddress').value = m.address;

      document.getElementById('editBaptised').checked = m.sacraments.includes('Baptised');
      document.getElementById('editConfirmed').checked = m.sacraments.includes('Confirmed');
      document.getElementById('editCommunion').checked = m.sacraments.includes('First Communion');

      openModal('editMemberModal');
    }
  </script>
</body>

</html>
