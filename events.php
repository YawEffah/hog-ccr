<?php
/**
 * Events & Announcements Page
 * 
 * BACKEND CONTRACT:
 * Expected variables:
 * @var array $upcoming_events [{ day, month, title, time, venue, description, badge_class, badge_label }]
 * @var array $announcements [{ title, description, badge_label, posted_by, date }]
 */

$pageTitle = 'Events';
$activePage = 'events';

// Mock data for initial refactor (Backend team will replace these)
$upcoming_events = $upcoming_events ?? [
    ['day' => 6, 'month' => 'Apr', 'title' => 'Sunday Service', 'time' => '8:00am', 'venue' => 'Main Auditorium', 'description' => 'Regular weekly service with Holy Communion', 'badge_class' => 'badge-green', 'badge_label' => 'All Members'],
    ['day' => 9, 'month' => 'Apr', 'title' => 'Midweek Prayer', 'time' => '6:30pm', 'venue' => 'Fellowship Hall', 'description' => 'Intercessory prayer and Bible study', 'badge_class' => 'badge-blue', 'badge_label' => 'Prayer Group'],
    ['day' => 19, 'month' => 'Apr', 'title' => 'Easter Convention', 'time' => 'All Day', 'venue' => 'Church Grounds', 'description' => 'Annual Easter celebration with guest speakers', 'badge_class' => 'badge-yellow', 'badge_label' => 'Special']
];

$announcements = $announcements ?? [
    ['title' => 'New Building Project Fund', 'description' => 'The leadership has approved the commencement of the new multipurpose hall construction. Pledges and donations are being collected. Target: GH₵ 500,000.', 'badge_label' => 'Pinned', 'posted_by' => 'Pastor Adu', 'date' => 'Apr 1']
];
?>
<!DOCTYPE html>
<html lang="en">

<?php require_once 'includes/head.php'; ?>

<body>

  <?php require_once 'includes/sidebar.php'; ?>

  <!-- MAIN CONTENT -->
  <main id="main">

    <div id="page-events" class="page">
      <div class="topbar">
        <div style="display:flex;align-items:center;">
          <button class="mobile-toggle" onclick="toggleSidebar()">
            <i class="ph ph-list"></i>
          </button>
          <div class="topbar-title">Events & Announcements</div>
        </div>
        <div class="topbar-actions">
          <div class="tabs">
            <button class="tab active">Events</button>
            <button class="tab">Announcements</button>
          </div>
          <button class="btn btn-primary btn-sm" onclick="openModal('addEventModal')">+ New Event</button>
        </div>
      </div>
      <div class="content">
        <div class="grid-2" style="gap:24px;">
          <div>
            <div
              style="font-family:'Cormorant Garamond',serif;font-size:16px;font-weight:600;color:var(--muted);margin-bottom:14px;letter-spacing:1px;text-transform:uppercase;">
              Upcoming</div>
            <div style="display:flex;flex-direction:column;gap:12px;">
              <?php foreach ($upcoming_events as $event): ?>
              <div class="event-card">
                <div class="event-date">
                  <div class="day"><?= $event['day'] ?></div>
                  <div class="month"><?= $event['month'] ?></div>
                </div>
                <div style="flex:1;">
                  <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <div style="font-size:14px;font-weight:600;color:var(--deep2);"><?= htmlspecialchars($event['title']) ?></div>
                    <span class="badge <?= $event['badge_class'] ?>"><?= htmlspecialchars($event['badge_label']) ?></span>
                  </div>
                  <div style="font-size:12px;color:var(--muted);margin-top:3px;"><?= $event['time'] ?> · <?= htmlspecialchars($event['venue']) ?></div>
                  <div style="font-size:12px;color:var(--mid);margin-top:6px;"><?= htmlspecialchars($event['description']) ?></div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div>
            <div
              style="font-family:'Cormorant Garamond',serif;font-size:16px;font-weight:600;color:var(--muted);margin-bottom:14px;letter-spacing:1px;text-transform:uppercase;">
              Announcements</div>
            <div style="display:flex;flex-direction:column;gap:12px;">
              <?php foreach ($announcements as $announce): ?>
              <div style="background:white;border-radius:12px;border:1px solid #EDE8DF;padding:16px;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
                  <div style="font-size:14px;font-weight:600;color:var(--deep2);"><?= htmlspecialchars($announce['title']) ?></div>
                  <span class="badge badge-purple"><?= htmlspecialchars($announce['badge_label']) ?></span>
                </div>
                <div style="font-size:13px;color:var(--mid);line-height:1.6;"><?= htmlspecialchars($announce['description']) ?></div>
                <div style="font-size:11px;color:var(--muted);margin-top:10px;">Posted by <?= htmlspecialchars($announce['posted_by']) ?> · <?= htmlspecialchars($announce['date']) ?></div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

  </main>

  <?php require_once 'includes/modals/event_modals.php'; ?>

  <script src="assets/js/main.js"></script>
</body>

</html>
