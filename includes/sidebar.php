<?php
/**
 * Shared sidebar component
 * @var string $activePage The slug of the current active page
 * @var array $currentUser Optional current user data
 */
$currentUser = $currentUser ?? [
  'initials' => 'EA',
  'name' => 'Elder Asante',
  'role' => 'Administrator'
];
?>
<!-- SIDEBAR -->
<aside id="sidebar" class="no-print">
  <div class="sidebar-logo">
    <div class="logo-wrap">
      <img src="assets/images/logo.png" alt="Logo" class="logo">
    </div>
    <span>
      <h1>Adom Fie CCR</h1>
      <p>Community</p>
    </span>
    <!-- Shown when expanded -->
    <button class="sidebar-collapse-btn no-print" onclick="toggleSidebarCollapse()" title="Collapse sidebar">
      <i class="ph ph-sidebar-simple" id="sidebarCollapseIcon"></i>
    </button>
  </div>

  <nav style="flex:1; overflow-y:auto; padding: 8px 0;">
    <div class="nav-section-label">Overview</div>
    <a href="dashboard.php" class="nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>" data-tooltip="Dashboard">
      <i class="ph ph-house"></i>
      <span class="nav-item-text">Dashboard</span>
    </a>

    <?php if (hasPermission('perm_manage_members') || hasPermission('perm_manage_welfare')): ?>
    <div class="nav-section-label">Congregation</div>
    <?php if (hasPermission('perm_manage_members')): ?>
    <a href="members.php" class="nav-item <?= $activePage === 'members' ? 'active' : '' ?>" data-tooltip="Members">
      <i class="ph ph-users"></i>
      <span class="nav-item-text">Members</span>
    </a>
    <a href="ministries.php" class="nav-item <?= $activePage === 'ministries' ? 'active' : '' ?>" data-tooltip="Ministries">
      <i class="ph ph-heart"></i>
      <span class="nav-item-text">Ministries</span>
    </a>
    <a href="families.php" class="nav-item <?= $activePage === 'families' ? 'active' : '' ?>" data-tooltip="Families">
      <i class="ph ph-users-three"></i>
      <span class="nav-item-text">Families</span>
    </a>
    <?php endif; ?>
    <?php if (hasPermission('perm_manage_welfare')): ?>
    <a href="welfare.php" class="nav-item <?= $activePage === 'welfare' ? 'active' : '' ?>" data-tooltip="Welfare">
      <i class="ph ph-hand-heart"></i>
      <span class="nav-item-text">Welfare</span>
    </a>
    <?php endif; ?>
    <?php endif; ?>


    <div class="nav-section-label">Administration</div>
    <?php if (hasPermission('perm_manage_finance')): ?>
    <a href="finance.php" class="nav-item <?= $activePage === 'finance' ? 'active' : '' ?>" data-tooltip="Finance">
      <i class="ph ph-wallet"></i>
      <span class="nav-item-text">Finance</span>
    </a>
    <?php endif; ?>
    <?php if (hasPermission('perm_manage_events')): ?>
    <a href="events.php" class="nav-item <?= $activePage === 'events' ? 'active' : '' ?>" data-tooltip="Events">
      <i class="ph ph-calendar"></i>
      <span class="nav-item-text">Events</span>
    </a>
    <?php endif; ?>
    <a href="reports.php" class="nav-item <?= $activePage === 'reports' ? 'active' : '' ?>" data-tooltip="Reports">
      <i class="ph ph-chart-bar"></i>
      <span class="nav-item-text">Reports</span>
    </a>

    <div class="nav-section-label">System</div>
    <?php if (hasPermission('perm_manage_users')): ?>
    <a href="users.php" class="nav-item <?= $activePage === 'users' ? 'active' : '' ?>" data-tooltip="User Management">
      <i class="ph ph-shield-check"></i>
      <span class="nav-item-text">User Management</span>
    </a>
    <?php endif; ?>
    <a href="logout.php" class="nav-item" data-tooltip="Logout">
      <i class="ph ph-sign-out"></i>
      <span class="nav-item-text">Logout</span>
    </a>
  </nav>


  <div class="sidebar-footer">
    <div class="user-chip">
      <div class="user-avatar"><?= htmlspecialchars($currentUser['initials']) ?></div>
      <div class="user-info">
        <p><?= htmlspecialchars($currentUser['name']) ?></p>
        <span><?= htmlspecialchars($currentUser['role']) ?></span>
      </div>
      <div class="user-actions">
        <a href="profile.php" class="action-btn" title="Settings">
          <i class="ph ph-gear-six"></i>
        </a>
        <a href="logout.php" class="action-btn logout" title="Logout">
          <i class="ph ph-sign-out"></i>
        </a>
      </div>
    </div>
  </div>
</aside>

<div id="sidebar-overlay" class="sidebar-overlay" onclick="closeSidebar()"></div>

<script>
  // Sidebar collapse (desktop only) with localStorage persistence
  (function () {
    if (localStorage.getItem('sidebarCollapsed') === 'true') {
      document.addEventListener('DOMContentLoaded', function () {
        var sidebar = document.getElementById('sidebar');
        var main    = document.getElementById('main');
        if (sidebar) sidebar.classList.add('collapsed');
        if (main)    main.classList.add('sidebar-collapsed');
      });
    }
  })();

  function toggleSidebarCollapse() {
    var sidebar = document.getElementById('sidebar');
    var main    = document.getElementById('main');
    if (!sidebar) return;
    var isCollapsed = sidebar.classList.toggle('collapsed');
    if (main) main.classList.toggle('sidebar-collapsed', isCollapsed);
    localStorage.setItem('sidebarCollapsed', isCollapsed);
  }
</script>

<?php require_once __DIR__ . '/modals/confirm_modal.php'; ?>
<?php require_once __DIR__ . '/modals/idle_timeout_modal.php'; ?>