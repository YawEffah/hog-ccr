<?php
/**
 * Members Management Page
 * 
 * BACKEND CONTRACT:
 * Expected variables:
 * @var array $members [{ id, first_name, last_name, initials, phone, email, ministry, status, status_class, ministry_class, joined }]
 * @var int $total_members
 * @var int $members_shown
 */

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
            <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
          <div class="topbar-title">Members</div>
        </div>
        <div class="topbar-actions">
          <div class="search-wrap">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input class="search-input" placeholder="Search members…" id="memberSearch" oninput="filterMembers()"
              style="width:220px;">
          </div>
          <select class="form-control" style="width:140px;padding:9px 14px;" onchange="filterMembers()">
            <option>All Status</option>
            <option>Active</option>
            <option>Inactive</option>
            <option>Visitor</option>
          </select>
          <button class="btn btn-primary btn-sm" onclick="openModal('addMemberModal')">+ Add Member</button>
        </div>
      </div>
      <div class="content">
        <div class="table-wrap">
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
                    <button class="btn btn-outline btn-sm" onclick="viewMember('<?= $m['id'] ?>')">View</button>
                    <button class="btn btn-outline btn-sm" onclick="editMember('<?= $m['id'] ?>')">Edit</button>
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
            <button class="btn btn-outline btn-sm">← Prev</button>
            <button class="btn btn-primary btn-sm">1</button>
            <button class="btn btn-outline btn-sm">2</button>
            <button class="btn btn-outline btn-sm">3</button>
            <button class="btn btn-outline btn-sm">Next →</button>
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
      document.querySelectorAll('#membersTbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
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
