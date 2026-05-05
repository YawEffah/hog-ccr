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

      <?php renderToastAlerts($successMsg, $errorMsg); ?>

      <div class="content">
        <!-- Tab Navigation -->
        <div class="tabs" id="eventTabs" style="margin-bottom:20px;background:white;border:1px solid #EDE8DF;border-radius:10px;padding:4px;display:inline-flex;">
          <button class="tab active" id="tabEventsBtn" onclick="switchEventTab('events')" style="padding:7px 20px;font-size:13px;">
            <i class="ph ph-calendar"></i> Upcoming Events
          </button>
          <button class="tab" id="tabAnnounceBtn" onclick="switchEventTab('announcements')" style="padding:7px 20px;font-size:13px;">
            <i class="ph ph-megaphone"></i> Announcements
          </button>
        </div>

        <!-- ── EVENTS TAB ── -->
        <div id="eventsTab">
          <div class="table-wrap">
            <div style="padding:16px 20px;border-bottom:1px solid #EDE8DF;display:flex;align-items:center;justify-content:space-between;">
              <h3 style="font-size:15px;margin:0;">Upcoming Events</h3>
            </div>
            <div class="table-responsive">
              <table id="eventsTable">
                <thead>
                  <tr>
                    <th>Date & Time</th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Target Group</th>
                    <th>Venue</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($upcoming_events)): ?>
                  <tr>
                    <td colspan="6" style="text-align:center;padding:30px;color:var(--muted);">No upcoming events scheduled.</td>
                  </tr>
                  <?php endif; ?>
                  <?php foreach ($upcoming_events as $event): ?>
                  <tr>
                    <td>
                      <div style="font-weight:600;color:var(--deep2);"><?= date('M j, Y', strtotime($event['date_val'])) ?></div>
                      <div style="font-size:11px;color:var(--muted);"><?= $event['time'] ?></div>
                    </td>
                    <td style="font-weight:500;"><?= htmlspecialchars($event['title']) ?></td>
                    <td><span class="badge <?= $event['badge_class'] ?>"><?= htmlspecialchars($event['badge_label']) ?></span></td>
                    <td style="font-size:13px;color:var(--mid);"><?= htmlspecialchars($event['target_group']) ?></td>
                    <td style="font-size:13px;"><?= htmlspecialchars($event['venue']) ?></td>
                    <td>
                      <div style="display:flex;gap:6px;">
                        <button class="btn-icon" onclick='viewEventDetails(<?= htmlspecialchars(json_encode($event), ENT_QUOTES, "UTF-8") ?>)' title="View Details">
                          <i class="ph ph-eye"></i>
                        </button>
                        <button class="btn-icon" onclick='openEditEvent(<?= htmlspecialchars(json_encode($event), ENT_QUOTES, "UTF-8") ?>)' title="Edit Event" style="background:var(--gold-pale);color:var(--gold);">
                          <i class="ph ph-pencil"></i>
                        </button>
                        <button class="btn-icon" onclick="confirmDeleteEvent(<?= $event['id'] ?>, '<?= htmlspecialchars(addslashes($event['title'])) ?>')" title="Delete Event" style="background:#FEF2F2;color:#DC2626;">
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

        <!-- ── ANNOUNCEMENTS TAB ── -->
        <div id="announcementsTab" style="display:none;">
          <div class="table-wrap">
            <div style="padding:16px 20px;border-bottom:1px solid #EDE8DF;display:flex;align-items:center;justify-content:space-between;">
              <h3 style="font-size:15px;margin:0;">Announcements</h3>
            </div>
            <div class="table-responsive">
              <table id="announcementsTable">
                <thead>
                  <tr>
                    <th>Date Posted</th>
                    <th>Title</th>
                    <th>Posted By</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($announcements)): ?>
                  <tr>
                    <td colspan="5" style="text-align:center;padding:30px;color:var(--muted);">No announcements yet.</td>
                  </tr>
                  <?php endif; ?>
                  <?php foreach ($announcements as $announce): ?>
                  <tr>
                    <td style="font-size:13px;color:var(--muted);"><?= htmlspecialchars($announce['date']) ?></td>
                    <td style="font-weight:500;"><?= htmlspecialchars($announce['title']) ?></td>
                    <td style="font-size:13px;"><?= htmlspecialchars($announce['posted_by']) ?></td>
                    <td><span class="badge <?= $announce['pinned'] ? 'badge-red' : 'badge-purple' ?>"><?= htmlspecialchars($announce['badge_label']) ?></span></td>
                    <td>
                      <div style="display:flex;gap:6px;">
                        <button class="btn-icon" onclick='viewAnnounceDetails(<?= htmlspecialchars(json_encode($announce), ENT_QUOTES, "UTF-8") ?>)' title="View Announcement">
                          <i class="ph ph-eye"></i>
                        </button>
                        <button class="btn-icon" onclick='openEditAnnouncement(<?= htmlspecialchars(json_encode($announce), ENT_QUOTES, "UTF-8") ?>)' title="Edit Announcement" style="background:var(--gold-pale);color:var(--gold);">
                          <i class="ph ph-pencil"></i>
                        </button>
                        <form method="POST" action="handlers/event_handler.php" style="margin:0;display:inline-block;" id="deleteAnnounceForm_<?= $announce['id'] ?>">
                          <?= csrfField() ?>
                          <input type="hidden" name="action" value="delete_announcement">
                          <input type="hidden" name="announcement_id" value="<?= $announce['id'] ?>">
                          <button type="button" class="btn-icon" style="background:#FEF2F2;color:#DC2626;" onclick="confirmDeleteAnnounce(<?= $announce['id'] ?>)" title="Delete Announcement">
                            <i class="ph ph-trash"></i>
                          </button>
                        </form>
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

    function openEditAnnouncement(ann) {
      document.getElementById('editAnnounceId').value    = ann.id;
      document.getElementById('editAnnounceTitle').value = ann.title;
      document.getElementById('editAnnounceDesc').value  = ann.description;
      document.getElementById('editAnnPinned').checked   = parseInt(ann.pinned) === 1;
      
      openModal('editAnnounceModal');
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

    function switchEventTab(tab) {
      document.getElementById('eventsTab').style.display = tab === 'events' ? 'block' : 'none';
      document.getElementById('announcementsTab').style.display = tab === 'announcements' ? 'block' : 'none';
      
      document.getElementById('tabEventsBtn').classList.toggle('active', tab === 'events');
      document.getElementById('tabAnnounceBtn').classList.toggle('active', tab === 'announcements');
    }

    function viewEventDetails(event) {
      document.getElementById('viewEventTitle').textContent    = event.title;
      document.getElementById('viewEventDateTime').textContent = `${new Date(event.date_val).toLocaleDateString('en-GB', {day: 'numeric', month: 'short', year: 'numeric'})} · ${event.time}`;
      document.getElementById('viewEventVenue').textContent    = event.venue || 'TBA';
      document.getElementById('viewEventType').innerHTML       = `<span class="badge ${event.badge_class}">${event.badge_label}</span>`;
      document.getElementById('viewEventTarget').textContent   = event.target_group;
      document.getElementById('viewEventDesc').textContent     = event.description || 'No description provided.';
      openModal('viewEventModal');
    }

    function viewAnnounceDetails(announce) {
      document.getElementById('viewAnnounceTitle').textContent = announce.title;
      document.getElementById('viewAnnounceMeta').textContent  = `Posted by ${announce.posted_by} on ${announce.date}`;
      
      const badgeClass = parseInt(announce.pinned) === 1 ? 'badge-red' : 'badge-purple';
      document.getElementById('viewAnnounceStatus').innerHTML  = `<span class="badge ${badgeClass}">${announce.badge_label}</span>`;
      
      document.getElementById('viewAnnounceDesc').textContent  = announce.description || 'No content provided.';
      openModal('viewAnnounceModal');
    }
  </script>
</body>

</html>
