<!-- Record Attendance Modal -->
<div class="modal-overlay" id="recordAttModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Record Attendance</h3>
      <button class="close-btn" onclick="closeModal('recordAttModal')">✕</button>
    </div>
    <form action="" method="POST" id="recordAttForm">
      <div class="modal-body">
        <div class="grid-2" style="gap:16px;">
          <div class="form-group">
            <label class="form-label">Session Type</label>
            <select class="form-control" name="session_type">
              <option>Sunday Service</option>
              <option>Midweek Prayer</option>
              <option>Youth Meeting</option>
              <option>Special Program</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Date</label>
            <input type="date" class="form-control" name="date" value="<?= date('Y-m-d') ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Time</label>
          <input type="time" class="form-control" name="time" value="08:00">
        </div>
        <div class="form-group">
          <label class="form-label">Mark Attendance</label>
          <div class="search-wrap" style="margin-bottom:8px;">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input class="search-input" placeholder="Search members…" id="attSearch" oninput="filterAttendance()"
              style="width:100%;">
          </div>
          <label
            style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:var(--gold-pale);border-radius:8px;cursor:pointer;margin-bottom:10px;border:1px dashed var(--gold);">
            <span style="font-size:13px;font-weight:600;color:var(--gold);">Select All</span>
            <input type="checkbox" id="markAllChk" onchange="toggleMarkAll(this)">
          </label>
          <div id="attList"
            style="display:flex;flex-direction:column;gap:10px;max-height:200px;overflow-y:auto;padding-right:4px;">
            <?php /* TODO: Backend team — render member rows here from your database */ ?>
            <!-- Static placeholder rows -->
            <label class="att-row"
              style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:#F1F5F9;border-radius:8px;cursor:pointer;">
              <span style="font-size:13px;font-weight:500;">Abena Kusi</span>
              <input type="checkbox" name="attendance[]" value="CCR-001" class="att-member" checked>
            </label>
            <label class="att-row"
              style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:#F1F5F9;border-radius:8px;cursor:pointer;">
              <span style="font-size:13px;font-weight:500;">Kwame Ofori</span>
              <input type="checkbox" name="attendance[]" value="CCR-002" class="att-member" checked>
            </label>
            <label class="att-row"
              style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:#F1F5F9;border-radius:8px;cursor:pointer;">
              <span style="font-size:13px;font-weight:500;">Serwa Acheampong</span>
              <input type="checkbox" name="attendance[]" value="CCR-003" class="att-member">
            </label>
            <label class="att-row"
              style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:#F1F5F9;border-radius:8px;cursor:pointer;">
              <span style="font-size:13px;font-weight:500;">Michael Boateng</span>
              <input type="checkbox" name="attendance[]" value="CCR-004" class="att-member" checked>
            </label>
            <label class="att-row"
              style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:#F1F5F9;border-radius:8px;cursor:pointer;">
              <span style="font-size:13px;font-weight:500;">Efua Asare</span>
              <input type="checkbox" name="attendance[]" value="CCR-005" class="att-member">
            </label>
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
