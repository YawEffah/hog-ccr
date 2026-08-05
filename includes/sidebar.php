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
  </div>

  <nav style="flex:1; overflow-y:auto; padding: 8px 0;">
    <div class="nav-section-label">Overview</div>
    <a href="dashboard.php" class="nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>">
      <i class="ph ph-house"></i>
      Dashboard
    </a>

    <?php if (hasPermission('perm_manage_members') || hasPermission('perm_manage_welfare')): ?>
    <div class="nav-section-label">Congregation</div>
    <?php if (hasPermission('perm_manage_members')): ?>
    <a href="members.php" class="nav-item <?= $activePage === 'members' ? 'active' : '' ?>">
      <i class="ph ph-users"></i>
      Members
    </a>
    <a href="ministries.php" class="nav-item <?= $activePage === 'ministries' ? 'active' : '' ?>">
      <i class="ph ph-heart"></i>
      Ministries
    </a>
    <a href="families.php" class="nav-item <?= $activePage === 'families' ? 'active' : '' ?>">
      <i class="ph ph-users-three"></i>
      Families
    </a>
    <?php endif; ?>
    <?php if (hasPermission('perm_manage_welfare')): ?>
    <a href="welfare.php" class="nav-item <?= $activePage === 'welfare' ? 'active' : '' ?>">
      <i class="ph ph-hand-heart"></i>
      Welfare
    </a>
    <?php endif; ?>
    <?php endif; ?>


    <div class="nav-section-label">Administration</div>
    <?php if (hasPermission('perm_manage_finance')): ?>
    <a href="finance.php" class="nav-item <?= $activePage === 'finance' ? 'active' : '' ?>">
      <i class="ph ph-wallet"></i>
      Finance
    </a>
    <?php endif; ?>
    <?php if (hasPermission('perm_manage_events')): ?>
    <a href="events.php" class="nav-item <?= $activePage === 'events' ? 'active' : '' ?>">
      <i class="ph ph-calendar"></i>
      Events
    </a>
    <?php endif; ?>
    <a href="reports.php" class="nav-item <?= $activePage === 'reports' ? 'active' : '' ?>">
      <i class="ph ph-chart-bar"></i>
      Reports
    </a>

    <div class="nav-section-label">System</div>
    <?php if (hasPermission('perm_manage_users')): ?>
    <a href="users.php" class="nav-item <?= $activePage === 'users' ? 'active' : '' ?>">
      <i class="ph ph-shield-check"></i>
      User Management
    </a>
    <?php endif; ?>
    <a href="logout.php" class="nav-item">
      <i class="ph ph-sign-out"></i>
      Logout
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

<?php require_once __DIR__ . '/modals/confirm_modal.php'; ?>