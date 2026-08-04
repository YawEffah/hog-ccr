<?php
/**
 * User Management Page
 */
require_once 'includes/auth.php';
requireAuth();
require_once 'includes/db.php';
require_once 'includes/helpers.php';

$pageTitle  = 'User Management';
$activePage = 'users';

// Only administrators can access this page
if (!hasPermission('perm_manage_users')) {
    redirect('index.php');
}

$successMsg = flash('success');
$errorMsg   = flash('error');

// Handle success/error query params
if (!$successMsg && !$errorMsg) {
    $successLabels = [
        'user_added'   => 'Administrative account created.',
        'user_updated' => 'Administrator profile updated.',
        'user_deleted' => 'Administrator account removed.',
        'role_added'   => 'System role created successfully.',
        'role_updated' => 'System role updated successfully.',
        'role_deleted' => 'System role removed successfully.',
    ];
    $errorLabels = [
        'missing_fields' => 'Please fill in all required fields.',
        'db_error'       => 'A database error occurred.',
        'duplicate_entry'=> 'Username, email, or role name already exists.',
        'invalid_action' => 'You cannot perform this action.',
        'role_in_use'    => 'Cannot delete role because it is currently assigned to one or more users.',
    ];
    $successMsg = $successLabels[$_GET['success'] ?? ''] ?? '';
    $errorMsg   = $errorLabels[$_GET['error']   ?? ''] ?? '';
}

$db = getDB();

// Fetch all administrators
$stmt = $db->query("SELECT id, name, username, email, phone, role, initials, created_at FROM admins ORDER BY name ASC");
$users = $stmt->fetchAll();

// Fetch all roles
$rolesStmt = $db->query("SELECT * FROM system_roles ORDER BY id ASC");
$roles = $rolesStmt->fetchAll();?>
<!DOCTYPE html>
<html lang="en">

<?php require_once 'includes/head.php'; ?>

<body>

  <?php require_once 'includes/sidebar.php'; ?>

  <!-- MAIN CONTENT -->
  <main id="main">

    <div id="page-users" class="page">
      <div class="topbar">
        <div style="display:flex;align-items:center;">
          <button class="mobile-toggle" onclick="toggleSidebar()">
            <i class="ph ph-list"></i>
          </button>
          <div class="topbar-title">User Management</div>
        </div>
        <div class="topbar-actions">

          <button class="btn btn-primary btn-sm" onclick="openModal('addUserModal')">+ Add New Admin</button>
        </div>
      </div>

      <div class="page-content" style="padding: 24px;">
        <?php renderToastAlerts($successMsg, $errorMsg); ?>

        <div class="card">
          <div class="card-header" style="padding: 20px; border-bottom: 1px solid #EDE8DF;">
            <h3 style="margin:0; font-family:'Cormorant Garamond', serif; font-size:22px;">Administrative Staff</h3>
            <p style="font-size:12px; color:var(--muted); margin-top:4px;">Manage system access and roles for church leadership.</p>
          </div>
          <div class="table-responsive">
            <table>
              <thead>
                <tr>
                  <th>Admin Name</th>
                  <th>Username</th>
                  <th>Role</th>
                  <th>Email Address</th>
                  <th>Created</th>
                  <th style="text-align:right;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                  <td>
                    <div style="display:flex; align-items:center; gap:12px;">
                      <div class="user-avatar" style="width:32px; height:32px; font-size:12px; background:var(--deep-pale); color:var(--deep); border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:600;">
                        <?= htmlspecialchars($u['initials']) ?>
                      </div>
                      <span style="font-weight:600; color:var(--deep2);"><?= htmlspecialchars($u['name']) ?></span>
                    </div>
                  </td>
                  <td style="color:var(--muted); font-size:13px;">@<?= htmlspecialchars($u['username']) ?></td>
                  <td>
                    <span class="badge <?= $u['role'] === 'Administrator' ? 'badge-blue' : ($u['role'] === 'Head Pastor' ? 'badge-yellow' : ($u['role'] === 'Finance Secretary' ? 'badge-green' : 'badge-purple')) ?>">
                      <?= $u['role'] ?>
                    </span>
                  </td>
                  <td style="color:var(--muted); font-size:13px;"><?= htmlspecialchars($u['email']) ?></td>
                  <td style="color:var(--muted); font-size:13px;"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                  <td style="text-align:right;">
                    <div style="display:flex; justify-content:flex-end; gap:6px;">
                      <button class="btn btn-outline btn-sm" onclick='editUser(<?= json_encode($u) ?>)'>
                        <i class="ph ph-pencil-simple"></i>
                      </button>
                      <?php if ($u['id'] != $currentUser['id']): ?>
                      <button class="btn btn-danger-soft btn-sm" 
                        onclick="confirmDeleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['name'])) ?>')">
                        <i class="ph ph-trash"></i>
                      </button>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card" style="margin-top: 24px;">
          <div class="card-header" style="padding: 20px; border-bottom: 1px solid #EDE8DF; display:flex; justify-content:space-between; align-items:center;">
            <div>
              <h3 style="margin:0; font-family:'Cormorant Garamond', serif; font-size:22px;">System Roles</h3>
              <p style="font-size:12px; color:var(--muted); margin-top:4px;">Manage custom roles and configure access permissions.</p>
            </div>
            <button class="btn btn-outline btn-sm" onclick="openModal('addRoleModal')">+ Add New Role</button>
          </div>
          <div class="table-responsive">
            <table>
              <thead>
                <tr>
                  <th>Role Name</th>
                  <th>Manage Users</th>
                  <th>Manage Finance</th>
                  <th>Manage Welfare</th>
                  <th>Manage Members</th>
                  <th>Manage Events</th>
                  <th style="text-align:right;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($roles as $r): ?>
                <tr>
                  <td><span style="font-weight:600; color:var(--deep2);"><?= htmlspecialchars($r['name']) ?></span></td>
                  <td>
                    <?php if ($r['perm_manage_users']): ?><span class="badge badge-green"><i class="ph ph-check"></i> Yes</span><?php else: ?><span class="badge badge-gray"><i class="ph ph-x"></i> No</span><?php endif; ?>
                  </td>
                  <td>
                    <?php if ($r['perm_manage_finance']): ?><span class="badge badge-green"><i class="ph ph-check"></i> Yes</span><?php else: ?><span class="badge badge-gray"><i class="ph ph-x"></i> No</span><?php endif; ?>
                  </td>
                  <td>
                    <?php if ($r['perm_manage_welfare']): ?><span class="badge badge-green"><i class="ph ph-check"></i> Yes</span><?php else: ?><span class="badge badge-gray"><i class="ph ph-x"></i> No</span><?php endif; ?>
                  </td>
                  <td>
                    <?php if ($r['perm_manage_members']): ?><span class="badge badge-green"><i class="ph ph-check"></i> Yes</span><?php else: ?><span class="badge badge-gray"><i class="ph ph-x"></i> No</span><?php endif; ?>
                  </td>
                  <td>
                    <?php if ($r['perm_manage_events']): ?><span class="badge badge-green"><i class="ph ph-check"></i> Yes</span><?php else: ?><span class="badge badge-gray"><i class="ph ph-x"></i> No</span><?php endif; ?>
                  </td>
                  <td style="text-align:right;">
                    <div style="display:flex; justify-content:flex-end; gap:6px;">
                      <button class="btn btn-outline btn-sm" onclick='editRole(<?= json_encode($r) ?>)' title="Edit Role">
                        <i class="ph ph-pencil-simple"></i>
                      </button>
                      <button class="btn btn-danger-soft btn-sm" 
                        onclick="confirmDeleteRole(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['name'])) ?>')" title="Delete Role">
                        <i class="ph ph-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </main>

  <?php include 'includes/modals/user_modals.php'; ?>

  <form method="POST" action="handlers/user_handler.php" id="deleteUserForm" style="display:none;">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="delete_user">
    <input type="hidden" name="user_id" id="deleteUserId">
  </form>

  <form method="POST" action="handlers/user_handler.php" id="deleteRoleForm" style="display:none;">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="delete_role">
    <input type="hidden" name="role_id" id="deleteRoleId">
  </form>

  <script src="assets/js/main.js"></script>
  <script>
    function editUser(u) {
      document.getElementById('edit_userId').value = u.id;
      document.getElementById('edit_name').value = u.name;
      document.getElementById('edit_username').value = u.username;
      document.getElementById('edit_email').value = u.email;
      document.getElementById('edit_phone').value = u.phone || '';
      document.getElementById('edit_role').value = u.role;
      document.getElementById('edit_initials').value = u.initials;
      
      openModal('editUserModal');
    }

    function confirmDeleteUser(id, name) {
      showConfirmModal(
        'Delete Administrator',
        'Are you sure you want to remove "' + name + '"? This user will no longer be able to log in to the system.',
        'Delete Admin',
        function() {
          document.getElementById('deleteUserId').value = id;
          document.getElementById('deleteUserForm').submit();
        },
        'danger'
      );
    }

    function editRole(r) {
      document.getElementById('edit_roleId').value = r.id;
      document.getElementById('edit_role_name').value = r.name;
      document.getElementById('edit_perm_manage_users').checked = r.perm_manage_users == 1;
      document.getElementById('edit_perm_manage_finance').checked = r.perm_manage_finance == 1;
      document.getElementById('edit_perm_manage_welfare').checked = r.perm_manage_welfare == 1;
      document.getElementById('edit_perm_manage_members').checked = r.perm_manage_members == 1;
      document.getElementById('edit_perm_manage_events').checked = r.perm_manage_events == 1;
      
      openModal('editRoleModal');
    }

    function confirmDeleteRole(id, name) {
      showConfirmModal(
        'Delete Role',
        'Are you sure you want to delete the role "' + name + '"? Users assigned to this role may lose access.',
        'Delete Role',
        function() {
          document.getElementById('deleteRoleId').value = id;
          document.getElementById('deleteRoleForm').submit();
        },
        'danger'
      );
    }
  </script>
</body>
</html>
