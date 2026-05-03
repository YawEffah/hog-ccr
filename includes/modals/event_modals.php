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
          <textarea class="form-control" name="description" rows="3"
            placeholder="Event details…" style="resize:none;"></textarea>
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
          <textarea class="form-control" name="description" id="editEventDesc" rows="3"
            style="resize:none;"></textarea>
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
          <textarea class="form-control" name="description" rows="5"
            placeholder="Write the announcement details…" required style="resize:none;"></textarea>
        </div>
        <div class="form-group" style="display:flex;align-items:center;gap:10px;margin-top:10px;">
          <input type="checkbox" name="pinned" id="annPinned" value="1"
            style="width:16px;height:16px;accent-color:var(--gold);">
          <label for="annPinned" style="font-size:14px;cursor:pointer;">Pin this announcement to top</label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addAnnounceModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Post Announcement</button>
      </div>
    </form>
  </div>
</div>
