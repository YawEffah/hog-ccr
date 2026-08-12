<?php
// We assume $_SESSION['user_id'] is available (from auth.php which is usually included before this)
// And helpers.php is included.
$currentUserId = $_SESSION['user_id'] ?? 0;
$unreadNotifications = getUnreadNotifications($currentUserId, 5); // get top 5
$unreadCount = getUnreadNotificationsCount($currentUserId);
?>
<!-- Notification Panel Component -->
<div class="notif-panel" id="notifPanel">
  <div class="notif-header">
    <h4>Notifications</h4>
    <a href="#" id="markAllReadBtn" onclick="return false;">Mark all as read</a>
  </div>
  <div class="notif-list" id="notifListContainer">
    <?php if (empty($unreadNotifications)): ?>
      <div style="padding: 20px; text-align: center; color: #64748B;">No new notifications</div>
    <?php else: ?>
      <?php foreach ($unreadNotifications as $notif): ?>
        <a href="<?= htmlspecialchars($notif['link'] ?? '#') ?>" 
           class="notif-item <?= $notif['is_read'] ? '' : 'unread' ?>" 
           data-id="<?= $notif['id'] ?>">
          <div class="notif-icon" style="background:#F1F5F9; color:<?= htmlspecialchars($notif['color'] ?? '#64748B') ?>;">
            <i class="<?= htmlspecialchars($notif['icon'] ?? 'ph ph-bell') ?>"></i>
          </div>
          <div class="notif-content">
            <div class="notif-title"><?= htmlspecialchars($notif['title']) ?></div>
            <div class="notif-desc"><?= htmlspecialchars($notif['message']) ?></div>
            <div class="notif-time" data-time="<?= $notif['created_at'] ?>">
                <?php
                // Simple inline time ago
                $diff = time() - strtotime($notif['created_at']);
                if ($diff < 60) echo 'Just now';
                elseif ($diff < 3600) echo floor($diff / 60) . ' minutes ago';
                elseif ($diff < 86400) echo floor($diff / 3600) . ' hours ago';
                elseif ($diff < 172800) echo 'Yesterday';
                else echo floor($diff / 86400) . ' days ago';
                ?>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <div class="notif-footer">
    <a href="events.php">View all events</a>
  </div>
</div>
