<?php
/**
 * Event & Announcement Modals
 * Included by events.php
 *
 * Modals:
 *  - addEventModal     (Add new event)
 *  - editEventModal    (Edit existing event — populated by JS openEditEvent())
 *  - addAnnounceModal  (Post new announcement)
 *
 * Field alignment fix:
 *  - name="category" maps to the 'type' DB column (handler reads $_POST['category'])
 *  - name="location" maps to 'venue' (handler reads $_POST['location'])
 */
?>

<!-- ── ADD EVENT MODAL ─────────────────────────────────────────────────────── -->
<div class="modal-overlay" id="addEventModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Create New Event</h3>
      <button class="close-btn" onclick="closeModal('addEventModal')"><i class="ph ph-x"></i></button>
    </div>
    <form action="handlers/event_handler.php" method="POST" id="addEventForm">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="add_event">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Event Title</label>
          <input class="form-control" name="title" placeholder="e.g. Easter Convention" required>
        </div>
        <div class="grid-2" style="gap:16px;">
          <div class="form-group">
            <label class="form-label">Date</label>
            <input type="date" class="form-control" name="date" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Time</label>
            <input type="time" class="form-control" name="time" value="08:00">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Location / Venue</label>
          <input class="form-control" name="location" placeholder="e.g. Main Auditorium">
        </div>
        <div class="grid-2" style="gap:16px;">
          <div class="form-group">
            <label class="form-label">Category</label>
            <!-- name="category" is read by handler as the 'type' column -->
            <select class="form-control" name="category" required>
              <option value="Weekly">Weekly</option>
              <option value="Monthly">Monthly</option>
              <option value="Annual">Annual</option>
              <option value="Special">Special</option>
              <option value="Service">Service</option>
              <option value="Meeting">Meeting</option>
              <option value="Convention">Convention</option>
              <option value="Retreat">Retreat</option>
              <option value="Special Program">Special Program</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Target Group</label>
            <select class="form-control" name="target_group">
              <option value="All Members">All Members</option>
              <option value="Youth Wing">Youth Wing</option>
              <option value="Music Ministry">Music Ministry</option>
              <option value="Intercessory">Intercessory</option>
              <option value="Executives">Executives</option>
              <option value="Prayer Group">Prayer Group</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea class="form-control" name="description" rows="3" placeholder="Event details…"
            style="resize:none;"></textarea>
        </div>
        <div
          style="background:#F1F5F9;border-radius:10px;padding:14px;display:flex;align-items:center;gap:12px;margin-top:10px;">
          <input type="checkbox" name="notify_members" id="eventNotify" value="1"
            style="width:16px;height:16px;cursor:pointer;">
          <div>
            <label for="eventNotify"
              style="font-size:13px;font-weight:600;cursor:pointer;color:var(--deep2);display:block;">Notify all
              members</label>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addEventModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Event</button>
      </div>
    </form>
  </div>
</div>

<!-- ── EDIT EVENT MODAL ────────────────────────────────────────────────────── -->
<div class="modal-overlay" id="editEventModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Edit Event</h3>
      <button class="close-btn" onclick="closeModal('editEventModal')"><i class="ph ph-x"></i></button>
    </div>
    <form action="handlers/event_handler.php" method="POST" id="editEventForm">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="edit_event">
      <input type="hidden" name="event_id" id="editEventId">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Event Title</label>
          <input class="form-control" name="title" id="editEventTitle" placeholder="e.g. Easter Convention" required>
        </div>
        <div class="grid-2" style="gap:16px;">
          <div class="form-group">
            <label class="form-label">Date</label>
            <input type="date" class="form-control" name="date" id="editEventDate" required>
          </div>
          <div class="form-group">
            <label class="form-label">Time</label>
            <input type="time" class="form-control" name="time" id="editEventTime">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Location / Venue</label>
          <input class="form-control" name="location" id="editEventVenue" placeholder="e.g. Main Auditorium">
        </div>
        <div class="grid-2" style="gap:16px;">
          <div class="form-group">
            <label class="form-label">Category</label>
            <select class="form-control" name="category" id="editEventCategory" required>
              <option value="Weekly">Weekly</option>
              <option value="Monthly">Monthly</option>
              <option value="Annual">Annual</option>
              <option value="Special">Special</option>
              <option value="Service">Service</option>
              <option value="Meeting">Meeting</option>
              <option value="Convention">Convention</option>
              <option value="Retreat">Retreat</option>
              <option value="Special Program">Special Program</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Target Group</label>
            <select class="form-control" name="target_group" id="editEventTargetGroup">
              <option value="All Members">All Members</option>
              <option value="Youth Wing">Youth Wing</option>
              <option value="Music Ministry">Music Ministry</option>
              <option value="Intercessory">Intercessory</option>
              <option value="Executives">Executives</option>
              <option value="Prayer Group">Prayer Group</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea class="form-control" name="description" id="editEventDesc" rows="3" style="resize:none;"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('editEventModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- ── ADD ANNOUNCEMENT MODAL ─────────────────────────────────────────────── -->
<div class="modal-overlay" id="addAnnounceModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Post New Announcement</h3>
      <button class="close-btn" onclick="closeModal('addAnnounceModal')"><i class="ph ph-x"></i></button>
    </div>
    <form action="handlers/event_handler.php" method="POST" id="addAnnounceForm">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="add_announcement">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Announcement Title</label>
          <input class="form-control" name="title" placeholder="e.g. Monthly All-Night Service" required>
        </div>
        <div class="form-group">
          <label class="form-label">Content / Description</label>
          <textarea class="form-control" name="description" rows="5" placeholder="Write the announcement details…"
            required style="resize:none;"></textarea>
        </div>
        <div class="form-group" style="display:flex;align-items:center;gap:10px;margin-top:10px;">
          <input type="checkbox" name="pinned" id="annPinned" value="1"
            style="width:16px;height:16px;accent-color:var(--gold);">
          <label for="annPinned" style="font-size:14px;cursor:pointer;">Pin this announcement to top</label>
        </div>
        <div
          style="background:#F1F5F9;border-radius:10px;padding:14px;display:flex;align-items:center;gap:12px;margin-top:10px;">
          <input type="checkbox" name="notify_members" id="annNotify" value="1"
            style="width:16px;height:16px;cursor:pointer;">
          <div>
            <label for="annNotify"
              style="font-size:13px;font-weight:600;cursor:pointer;color:var(--deep2);display:block;">Notify all
              members</label>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addAnnounceModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Post Announcement</button>
      </div>
    </form>
  </div>
</div>

<!-- ── EDIT ANNOUNCEMENT MODAL ────────────────────────────────────────────── -->
<div class="modal-overlay" id="editAnnounceModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Edit Announcement</h3>
      <button class="close-btn" onclick="closeModal('editAnnounceModal')"><i class="ph ph-x"></i></button>
    </div>
    <form action="handlers/event_handler.php" method="POST" id="editAnnounceForm">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="edit_announcement">
      <input type="hidden" name="announcement_id" id="editAnnounceId">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Announcement Title</label>
          <input class="form-control" name="title" id="editAnnounceTitle" placeholder="e.g. Monthly All-Night Service" required>
        </div>
        <div class="form-group">
          <label class="form-label">Content / Description</label>
          <textarea class="form-control" name="description" id="editAnnounceDesc" rows="5" placeholder="Write the announcement details…"
            required style="resize:none;"></textarea>
        </div>
        <div class="form-group" style="display:flex;align-items:center;gap:10px;margin-top:10px;">
          <input type="checkbox" name="pinned" id="editAnnPinned" value="1"
            style="width:16px;height:16px;accent-color:var(--gold);">
          <label for="editAnnPinned" style="font-size:14px;cursor:pointer;">Pin this announcement to top</label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('editAnnounceModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- ── VIEW EVENT MODAL ───────────────────────────────────────────────────── -->
<div class="modal-overlay" id="viewEventModal">
  <div class="modal" style="max-width:560px;">
    <div class="modal-header">
      <h3 id="viewEventTitle" style="color:var(--deep);font-size:20px;line-height:1.3;">Event Title</h3>
      <button class="close-btn" onclick="closeModal('viewEventModal')"><i class="ph ph-x"></i></button>
    </div>
    <div class="modal-body">
      <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:16px;margin-bottom:20px;display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div>
          <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">Date & Time</div>
          <div style="font-size:14px;font-weight:500;color:var(--deep2);" id="viewEventDateTime">Jan 01, 2026 · 10:00 AM</div>
        </div>
        <div>
          <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">Venue</div>
          <div style="font-size:14px;font-weight:500;color:var(--deep2);" id="viewEventVenue">Main Auditorium</div>
        </div>
        <div>
          <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">Type</div>
          <div id="viewEventType"><span class="badge badge-blue">Service</span></div>
        </div>
        <div>
          <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">Target Group</div>
          <div style="font-size:14px;font-weight:500;color:var(--deep2);" id="viewEventTarget">All Members</div>
        </div>
      </div>
      <div>
        <div style="font-size:12px;font-weight:600;color:var(--muted);text-transform:uppercase;margin-bottom:8px;">Description</div>
        <div id="viewEventDesc" style="font-size:14px;line-height:1.6;color:#334155;white-space:pre-wrap;background:#fff;padding:12px;border:1px solid #E2E8F0;border-radius:8px;min-height:80px;">
          No description provided.
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-outline" style="width:100%;" onclick="closeModal('viewEventModal')">Close</button>
    </div>
  </div>
</div>

<!-- ── VIEW ANNOUNCEMENT MODAL ────────────────────────────────────────────── -->
<div class="modal-overlay" id="viewAnnounceModal">
  <div class="modal" style="max-width:560px;">
    <div class="modal-header">
      <div style="flex:1;">
        <h3 id="viewAnnounceTitle" style="color:var(--deep);font-size:20px;line-height:1.3;margin-bottom:4px;">Announcement Title</h3>
        <div id="viewAnnounceMeta" style="font-size:12px;color:var(--muted);">Posted by Admin on Jan 01, 2026</div>
      </div>
      <button class="close-btn" onclick="closeModal('viewAnnounceModal')"><i class="ph ph-x"></i></button>
    </div>
    <div class="modal-body">
      <div style="margin-bottom:16px;" id="viewAnnounceStatus"></div>
      <div>
        <div id="viewAnnounceDesc" style="font-size:14.5px;line-height:1.7;color:#334155;white-space:pre-wrap;background:#F8FAFC;padding:20px;border:1px solid #E2E8F0;border-radius:8px;min-height:120px;">
          Announcement content goes here.
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-outline" style="width:100%;" onclick="closeModal('viewAnnounceModal')">Close</button>
    </div>
  </div>
</div>