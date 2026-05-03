<?php
/**
 * Events & Announcements Page
 */
require_once 'includes/auth.php';
requireAuth();
require_once 'includes/db.php';
require_once 'includes/helpers.php';

$pageTitle  = 'Events';
$activePage = 'events';

// Flash messages (from redirect pattern)
$successMsg = flash('success');
$errorMsg   = flash('error');

// Decode ?success= / ?error= query params too (fallback for old redirects)
if (!$successMsg && !$errorMsg) {
    $qSuccess = $_GET['success'] ?? '';
    $qError   = $_GET['error']   ?? '';
    $successLabels = [
        'event_added'          => 'Event created successfully.',
        'event_updated'        => 'Event updated successfully.',
        'event_deleted'        => 'Event deleted.',
        'announcement_posted'  => 'Announcement posted successfully.',
        'announcement_deleted' => 'Announcement deleted.',
    ];
    $errorLabels = [
        'missing_fields'  => 'Please fill in all required fields.',
        'db_error'        => 'A database error occurred. Please try again.',
        'unknown_action'  => 'Unknown action requested.',
    ];
    $successMsg = $successLabels[$qSuccess] ?? '';
    $errorMsg   = $errorLabels[$qError]    ?? '';
}

$db = getDB();

// ── Upcoming Events ──────────────────────────────────────────────────────────
$eventsStmt = $db->query(
    "SELECT * FROM events
     WHERE event_date >= CURRENT_DATE
     ORDER BY event_date ASC, event_time ASC"
);
$rawEvents = $eventsStmt->fetchAll();

$typeBadges = [
    'Weekly'         => 'badge-blue',
    'Monthly'        => 'badge-red',
    'Annual'         => 'badge-purple',
    'Special'        => 'badge-green',
    'Service'        => 'badge-blue',
    'Meeting'        => 'badge-gray',
    'Convention'     => 'badge-purple',
    'Retreat'        => 'badge-green',
    'Special Program'=> 'badge-yellow',
];

$upcoming_events = array_map(function($e) use ($typeBadges) {
    return [
        'id'          => $e['id'],
        'day'         => date('j', strtotime($e['event_date'])),
        'month'       => date('M', strtotime($e['event_date'])),
        'date_val'    => $e['event_date'],
        'time_val'    => $e['event_time'] ? substr($e['event_time'], 0, 5) : '',
        'title'       => $e['title'],
        'time'        => $e['event_time'] ? date('g:ia', strtotime($e['event_time'])) : '—',
        'venue'       => $e['venue'] ?: 'TBA',
        'venue_raw'   => $e['venue'] ?? '',
        'badge_class' => $typeBadges[$e['type']] ?? 'badge-blue',
        'badge_label' => $e['type'],
        'type'        => $e['type'],
        'target_group'=> $e['target_group'] ?? 'All Members',
        'description' => $e['description'] ?? '',
    ];
}, $rawEvents);

// ── Announcements ────────────────────────────────────────────────────────────
$annStmt = $db->query(
    "SELECT a.*, adm.name as poster_name
     FROM announcements a
     LEFT JOIN admins adm ON a.posted_by = adm.id
     ORDER BY a.pinned DESC, a.created_at DESC
     LIMIT 10"
);
$rawAnnouncements = $annStmt->fetchAll();

$announcements = array_map(function($a) {
    return [
        'id'          => $a['id'],
        'title'       => $a['title'],
        'description' => $a['description'],
        'pinned'      => $a['pinned'],
        'badge_label' => $a['pinned'] ? 'Pinned' : 'Latest',
        'posted_by'   => $a['poster_name'] ?: 'Admin',
        'date'        => date('M j', strtotime($a['created_at']))
    ];
}, $rawAnnouncements);

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
          <div class="topbar-title">Events &amp; Announcements</div>
        </div>
        <div class="topbar-actions">
          <button class="btn btn-outline btn-sm" onclick="openModal('addAnnounceModal')">+ New Announcement</button>
          <button class="btn btn-primary btn-sm" onclick="openModal('addEventModal')">+ New Event</button>
        </div>
      </div>

      <?php if ($successMsg): ?>
      <div class="alert alert-success" style="margin:20px 20px 0;">
        <i class="ph ph-check-circle"></i> <?= htmlspecialchars($successMsg) ?>
      </div>
      <?php endif; ?>
      <?php if ($errorMsg): ?>
      <div class="alert alert-error" style="margin:20px 20px 0;">
        <i class="ph ph-warning-circle"></i> <?= htmlspecialchars($errorMsg) ?>
      </div>
      <?php endif; ?>

      <div class="content">
        <div class="grid-2" style="gap:24px;">

          <!-- ── EVENTS COLUMN ── -->
          <div>
            <div style="font-family:'Cormorant Garamond',serif;font-size:16px;font-weight:600;color:var(--muted);margin-bottom:14px;letter-spacing:1px;text-transform:uppercase;">
              Upcoming Events</div>

            <?php if (empty($upcoming_events)): ?>
              <div style="text-align:center;padding:40px;color:var(--muted);font-size:13px;">
                <i class="ph ph-calendar-blank" style="font-size:32px;display:block;margin-bottom:10px;"></i>
                No upcoming events scheduled.
              </div>
            <?php endif; ?>

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
                  <?php if ($event['description']): ?>
                  <div style="font-size:12px;color:var(--mid);margin-top:6px;"><?= htmlspecialchars($event['description']) ?></div>
                  <?php endif; ?>
                  <div style="display:flex;gap:8px;margin-top:10px;">
                    <button class="btn btn-outline btn-sm"
                      onclick="openEditEvent(<?= htmlspecialchars(json_encode($event)) ?>)"
                      style="font-size:11px;padding:4px 10px;">
                      <i class="ph ph-pencil"></i> Edit
                    </button>
                    <button class="btn btn-sm" style="font-size:11px;padding:4px 10px;background:#FEF2F2;color:#DC2626;border:1px solid #FECACA;"
                      onclick="confirmDeleteEvent(<?= $event['id'] ?>, '<?= htmlspecialchars(addslashes($event['title'])) ?>')">
                      <i class="ph ph-trash"></i> Delete
                    </button>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- ── ANNOUNCEMENTS COLUMN ── -->
          <div>
            <div style="font-family:'Cormorant Garamond',serif;font-size:16px;font-weight:600;color:var(--muted);margin-bottom:14px;letter-spacing:1px;text-transform:uppercase;">
              Announcements</div>

            <?php if (empty($announcements)): ?>
              <div style="text-align:center;padding:40px;color:var(--muted);font-size:13px;">
                <i class="ph ph-megaphone" style="font-size:32px;display:block;margin-bottom:10px;"></i>
                No announcements yet.
              </div>
            <?php endif; ?>

            <div style="display:flex;flex-direction:column;gap:12px;">
              <?php foreach ($announcements as $announce): ?>
              <div style="background:white;border-radius:12px;border:1px solid #EDE8DF;padding:16px;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
                  <div style="font-size:14px;font-weight:600;color:var(--deep2);"><?= htmlspecialchars($announce['title']) ?></div>
                  <span class="badge <?= $announce['pinned'] ? 'badge-red' : 'badge-purple' ?>"><?= htmlspecialchars($announce['badge_label']) ?></span>
                </div>
                <div style="font-size:13px;color:var(--mid);line-height:1.6;"><?= htmlspecialchars($announce['description']) ?></div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;">
                  <div style="font-size:11px;color:var(--muted);">Posted by <?= htmlspecialchars($announce['posted_by']) ?> · <?= htmlspecialchars($announce['date']) ?></div>
                  <form method="POST" action="handlers/event_handler.php" style="margin:0;" id="deleteAnnounceForm_<?= $announce['id'] ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="delete_announcement">
                    <input type="hidden" name="announcement_id" value="<?= $announce['id'] ?>">
                    <button type="button" class="btn btn-sm" style="font-size:11px;padding:4px 10px;background:#FEF2F2;color:#DC2626;border:1px solid #FECACA;" onclick="confirmDeleteAnnounce(<?= $announce['id'] ?>)">
                      <i class="ph ph-trash"></i> Delete
                    </button>
                  </form>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

        </div>
      </div>
    </div>

  </main>

  <?php require_once 'includes/modals/event_modals.php'; ?>

  <!-- Hidden delete-event form (submitted by JS) -->
  <form method="POST" action="handlers/event_handler.php" id="deleteEventForm" style="display:none;">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="delete_event">
    <input type="hidden" name="event_id" id="deleteEventId">
  </form>

  <script src="assets/js/main.js"></script>
  <script>
    function openEditEvent(event) {
      document.getElementById('editEventId').value    = event.id;
      document.getElementById('editEventTitle').value = event.title;
      document.getElementById('editEventDate').value  = event.date_val;
      document.getElementById('editEventTime').value  = event.time_val;
      document.getElementById('editEventVenue').value = event.venue_raw;
      document.getElementById('editEventDesc').value  = event.description;

      // Set category (type) select
      var catSel = document.getElementById('editEventCategory');
      for (var i = 0; i < catSel.options.length; i++) {
        if (catSel.options[i].value === event.type) { catSel.selectedIndex = i; break; }
      }

      // Set target group select
      var tgSel = document.getElementById('editEventTargetGroup');
      for (var i = 0; i < tgSel.options.length; i++) {
        if (tgSel.options[i].value === event.target_group) { tgSel.selectedIndex = i; break; }
      }

      openModal('editEventModal');
    }

    function confirmDeleteEvent(id, title) {
      showConfirmModal(
        'Delete Event',
        'Delete event "' + title + '"? This cannot be undone.',
        'Delete',
        function() {
          document.getElementById('deleteEventId').value = id;
          document.getElementById('deleteEventForm').submit();
        },
        'danger'
      );
    }

    function confirmDeleteAnnounce(id) {
      showConfirmModal(
        'Delete Announcement',
        'Delete this announcement? This cannot be undone.',
        'Delete',
        function() {
          document.getElementById('deleteAnnounceForm_' + id).submit();
        },
        'danger'
      );
    }
  </script>
</body>

</html>
