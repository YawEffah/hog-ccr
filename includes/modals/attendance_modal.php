<!-- Record Attendance Modal -->
<div class="modal-overlay" id="recordAttModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Record Attendance</h3>
      <button class="close-btn" onclick="closeModal('recordAttModal')"><i class="ph ph-x"></i></button>
    </div>
    <form action="handlers/attendance_handler.php" method="POST" id="recordAttForm">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="record_attendance">
      <div class="modal-body">
        <div class="grid-2" style="gap:16px;">
          <div class="form-group">
            <label class="form-label">Session Type</label>
            <select class="form-control" name="session_type" required>
              <option value="Sunday Service">Sunday Service</option>
              <option value="Midweek Prayer">Midweek Prayer</option>
              <option value="Youth Meeting">Youth Meeting</option>
              <option value="Special Program">Special Program</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Date</label>
            <input type="date" class="form-control" name="session_date" value="<?= date('Y-m-d') ?>" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Time</label>
          <input type="time" class="form-control" name="session_time" value="<?= date('H:i') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Mark Attendance (Present Members)</label>
          <div class="search-wrap" style="margin-bottom:8px;">
            <i class="ph ph-magnifying-glass"></i>
            <input class="search-input" placeholder="Search members…" id="attSearch" oninput="filterAttendance()"
              style="width:100%;">
          </div>
          <label
            style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:var(--gold-pale);border-radius:8px;cursor:pointer;margin-bottom:10px;border:1px dashed var(--gold);">
            <span style="font-size:13px;font-weight:600;color:var(--gold);">Select All</span>
            <input type="checkbox" id="markAllChk" onchange="toggleMarkAll(this)">
          </label>
          <div id="attList"
            style="display:flex;flex-direction:column;gap:10px;max-height:250px;overflow-y:auto;padding-right:4px;">
            <?php foreach ($allMembers as $m): ?>
            <label class="att-row"
              style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:#F1F5F9;border-radius:8px;cursor:pointer;">
              <span style="font-size:13px;font-weight:500;"><?= htmlspecialchars($m['first_name'] . ' ' . $m['last_name']) ?> (<?= $m['member_code'] ?>)</span>
              <input type="checkbox" name="present_members[]" value="<?= $m['id'] ?>" class="att-member">
            </label>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Notes</label>
          <textarea class="form-control" name="notes" rows="2"
            placeholder="Optional notes…" style="resize:none;"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('recordAttModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Attendance</button>
      </div>
    </form>
  </div>
</div>
