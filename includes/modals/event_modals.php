<!-- Add Event Modal -->
<div class="modal-overlay" id="addEventModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Create New Event</h3>
      <button class="close-btn" onclick="closeModal('addEventModal')">✕</button>
    </div>
    <form action="" method="POST" id="addEventForm">
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Event Title</label><input class="form-control" name="title"
            placeholder="e.g. Easter Convention"></div>
        <div class="grid-2" style="gap:16px;">
          <div class="form-group"><label class="form-label">Date</label><input type="date" class="form-control" name="date" value="<?= date('Y-m-d') ?>"></div>
          <div class="form-group"><label class="form-label">Time</label><input type="time" class="form-control" name="time" value="08:00"></div>
        </div>
        <div class="form-group"><label class="form-label">Location / Venue</label><input class="form-control" name="location"
            placeholder="e.g. Main Auditorium"></div>
        <div class="grid-2" style="gap:16px;">
          <div class="form-group">
            <label class="form-label">Category</label>
            <select class="form-control" name="category">
              <option>Service</option>
              <option>Meeting</option>
              <option>Convention</option>
              <option>Retreat</option>
              <option>Special Program</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Target Group</label>
            <select class="form-control" name="target_group">
              <option>All Members</option>
              <option>Youth Wing</option>
              <option>Music Ministry</option>
              <option>Intercessory</option>
              <option>Executives</option>
              <option>Prayer Group</option>
            </select>
          </div>
        </div>
        <div class="form-group"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3"
            placeholder="Event details…" style="resize:none;"></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addEventModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Event</button>
      </div>
    </form>
  </div>
</div>
