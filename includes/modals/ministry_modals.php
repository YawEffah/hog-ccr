<!-- Add Ministry Modal -->
<div class="modal-overlay" id="addMinistryModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Create New Ministry</h3>
      <button class="close-btn" onclick="closeModal('addMinistryModal')"><i class="ph ph-x"></i></button>
    </div>
    <form action="handlers/ministry_handler.php" method="POST" id="addMinistryForm">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="add_ministry">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Ministry Name</label>
          <input class="form-control" name="name" placeholder="e.g. Media Ministry" required>
        </div>
        <div class="form-group">
          <label class="form-label">Purpose/Description</label>
          <textarea class="form-control" name="description" rows="3" placeholder="Describe the goal..."></textarea>
        </div>
        <div class="grid-2" style="gap:16px;">
          <div class="form-group" style="position: relative;">
            <label class="form-label">Ministry Head</label>
            <input class="form-control" id="add_mHeadDisplay" name="head_display"
              placeholder="Search member by name or ID..." oninput="filterMHeads(this.value, 'add')" autocomplete="off">
            <input type="hidden" name="head_id" id="add_mHeadId">
            <div id="add_mHeadSuggestions" class="search-suggestions" style="display:none;"></div>
          </div>
          <div class="form-group">
            <label class="form-label">Meeting Day</label>
            <select class="form-control" name="meeting_day">
              <option value="Saturdays">Saturdays</option>
              <option value="Fridays">Fridays</option>
              <option value="Sundays">Sundays</option>
              <option value="Wednesdays">Wednesdays</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addMinistryModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Ministry</button>
      </div>
    </form>
  </div>
</div>

<!-- Manage Ministry Modal -->
<div class="modal-overlay" id="manageMinistryModal">
  <div class="modal" style="max-width:700px;">
    <div class="modal-header">
      <div style="display:flex;align-items:center;gap:12px;">
        <div class="ministry-icon" id="mIcon" style="width:40px;height:40px;font-size:20px;margin-bottom:0;">🎵</div>
        <div>
          <h3 id="mTitle">Music Ministry</h3>
          <div style="font-size:12px;color:var(--muted);" id="mSubtitle">Worship & praise team</div>
        </div>
      </div>
      <button class="close-btn" onclick="closeModal('manageMinistryModal')"><i class="ph ph-x"></i></button>
    </div>
    <div class="modal-body" style="padding-top:0;">
      <div class="tabs" style="margin-bottom:20px;border-bottom:1px solid var(--border);">
        <div class="tab active" onclick="switchMTab(this, 'mOverview')">Overview</div>
        <div class="tab" onclick="switchMTab(this, 'mMembers')">Members</div>
        <div class="tab" onclick="switchMTab(this, 'mHistory')">History</div>
        <div class="tab" onclick="switchMTab(this, 'mEdit')">Edit Info</div>
      </div>

      <!-- Overview Tab -->
      <div id="mOverview" class="tab-pane active">
        <div class="grid-3" style="gap:16px;margin-bottom:24px;">
          <div
            style="background:var(--deep-pale);padding:16px;border-radius:12px;border:1px solid rgba(46,45,123,0.1);">
            <div style="font-size:11px;color:var(--deep);text-transform:uppercase;margin-bottom:4px;">Total Members
            </div>
            <div style="font-size:24px;font-weight:700;color:var(--deep);" id="mCount">28</div>
          </div>
          <div
            style="background:var(--primary-pale);padding:16px;border-radius:12px;border:1px solid rgba(220,38,26,0.1);">
            <div style="font-size:11px;color:var(--primary);text-transform:uppercase;margin-bottom:4px;">Avg. Attendance
            </div>
            <div style="font-size:24px;font-weight:700;color:var(--primary);" id="mAttendance">78%</div>
          </div>
          <div style="background:#F3F4F6;padding:16px;border-radius:12px;border:1px solid var(--border);">
            <div style="font-size:11px;color:var(--muted);text-transform:uppercase;margin-bottom:4px;">Sessions</div>
            <div style="font-size:24px;font-weight:700;color:var(--deep3);" id="mSessions">12</div>
          </div>
        </div>

        <div style="margin-bottom:20px;">
          <div style="font-size:13px;font-weight:600;margin-bottom:12px;">Attendance Trend</div>
          <div style="height:100px;display:flex;align-items:flex-end;gap:8px;padding-bottom:20px;" id="mChart">
            <div style="flex:1;background:var(--primary);height:60%;border-radius:4px 4px 0 0;"></div>
            <div style="flex:1;background:var(--primary);height:80%;border-radius:4px 4px 0 0;"></div>
            <div style="flex:1;background:var(--primary);height:40%;border-radius:4px 4px 0 0;"></div>
            <div style="flex:1;background:var(--primary);height:90%;border-radius:4px 4px 0 0;"></div>
            <div style="flex:1;background:var(--primary);height:75%;border-radius:4px 4px 0 0;"></div>
            <div style="flex:1;background:var(--deep);height:85%;border-radius:4px 4px 0 0;"></div>
          </div>
        </div>
      </div>

      <!-- Members Tab -->
      <div id="mMembers" class="tab-pane" style="display:none;">
        <div class="table-responsive">
          <table style="width:100%;font-size:13px;">
            <thead style="background:var(--bg-light);text-align:left;">
              <tr>
                <th style="padding:8px;">Member</th>
                <th style="padding:8px;">Role</th>
                <th style="padding:8px;">Joined</th>
              </tr>
            </thead>
            <tbody id="mMembersList">
              <!-- Populated via JS -->
            </tbody>
          </table>
        </div>
      </div>

      <!-- History Tab -->
      <div id="mHistory" class="tab-pane" style="display:none;">
        <div style="display:flex;flex-direction:column;gap:12px;" id="mTimeline">
          <!-- Populated via JS -->
        </div>
      </div>

      <!-- Edit Tab -->
      <div id="mEdit" class="tab-pane" style="display:none;">
        <form action="handlers/ministry_handler.php" method="POST" id="editMinistryForm">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="edit_ministry">
          <input type="hidden" name="ministry_id" id="edit_mId">
          <div class="form-group">
            <label class="form-label">Ministry Name</label>
            <input class="form-control" name="name" id="edit_mName" required>
          </div>
          <div class="form-group">
            <label class="form-label">Purpose/Description</label>
            <textarea class="form-control" name="description" id="edit_mDesc" rows="3"></textarea>
          </div>
          <div class="grid-2" style="gap:16px;">
            <div class="form-group" style="position: relative;">
              <label class="form-label">Ministry Head</label>
              <input class="form-control" id="edit_mHeadDisplay" name="head_display"
                placeholder="Search member by name or ID..." oninput="filterMHeads(this.value, 'edit')"
                autocomplete="off">
              <input type="hidden" name="head_id" id="edit_mHeadId">
              <div id="edit_mHeadSuggestions" class="search-suggestions" style="display:none;"></div>
            </div>
            <div class="form-group">
              <label class="form-label">Meeting Day</label>
              <select class="form-control" name="meeting_day" id="edit_mDay">
                <option value="Saturdays">Saturdays</option>
                <option value="Fridays">Fridays</option>
                <option value="Sundays">Sundays</option>
                <option value="Wednesdays">Wednesdays</option>
              </select>
            </div>
          </div>
          <button type="submit" class="btn btn-primary" style="width:100%;margin-top:10px;">Save Changes</button>
        </form>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('manageMinistryModal')">Close</button>
      <button class="btn btn-primary">Download Report</button>
    </div>
  </div>
</div>
<style>
  .search-suggestions {
    background: white;
    border: 1px solid var(--border);
    border-radius: 8px;
    max-height: 200px;
    overflow-y: auto;
    position: absolute;
    width: 100%;
    z-index: 100;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    margin-top: 4px;
  }

  .suggestion-item {
    padding: 10px 14px;
    cursor: pointer;
    font-size: 13px;
    border-bottom: 1px solid #F4F0EA;
  }

  .suggestion-item:hover {
    background: var(--gold-pale);
  }

  .suggestion-item .sub {
    color: var(--muted);
    font-size: 11px;
    margin-left: 6px;
  }
</style>

<script>
  function filterMHeads(q, type) {
    const box = document.getElementById(type + '_mHeadSuggestions');
    const allMembers = allMembersData;

    if (!q) { box.style.display = 'none'; return; }

    const filtered = allMembers.filter(m =>
      m.name.toLowerCase().includes(q.toLowerCase()) ||
      m.member_code.toLowerCase().includes(q.toLowerCase())
    );

    if (!filtered.length) { box.style.display = 'none'; return; }

    box.innerHTML = filtered.map(m => `
    <div class="suggestion-item" onclick="selectMHead('${m.id}', '${m.name} (${m.member_code})', '${type}')">
      ${m.name} <span class="sub">${m.member_code}</span>
    </div>
  `).join('');
    box.style.display = 'block';
  }

  function selectMHead(id, display, type) {
    document.getElementById(type + '_mHeadId').value = id;
    document.getElementById(type + '_mHeadDisplay').value = display;
    document.getElementById(type + '_mHeadSuggestions').style.display = 'none';
  }

  // Close suggestions on outside click
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.form-group')) {
      document.querySelectorAll('.search-suggestions').forEach(s => s.style.display = 'none');
    }
  });
</script>

<!-- Send Ministry Message Modal -->
<div class="modal-overlay" id="sendMinistryMessageModal">
  <div class="modal" style="max-width:540px;">
    <div class="modal-header">
      <h3 id="bulkMsgTitle">Send Ministry Message</h3>
      <button class="close-btn" onclick="closeModal('sendMinistryMessageModal')"><i class="ph ph-x"></i></button>
    </div>
    <form action="handlers/ministry_handler.php" method="POST" id="sendMinistryBulkForm">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="send_ministry_bulk_message">
      <input type="hidden" name="ministry_id" id="bulkMsgMinId">
      
      <div class="modal-body">
        <div style="background:var(--deep-pale); border-radius:12px; padding:16px; display:flex; align-items:center; gap:14px; border:1px solid rgba(46,45,123,0.1); margin-bottom:20px;">
          <div id="bulkMsgIcon" style="font-size:24px; width:48px; height:48px; display:flex; align-items:center; justify-content:center; border-radius:10px; background:white;">✝️</div>
          <div>
            <div id="bulkMsgMinName" style="font-weight:700; color:var(--deep); font-size:16px;">Ministry Name</div>
            <div style="font-size:12px; color:var(--muted);"><span id="bulkMsgCount">0</span> active members will receive this message</div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Message Subject (for Email)</label>
          <input class="form-control" name="subject" id="bulkMsgSubject" placeholder="e.g. Upcoming Meeting Reminder">
        </div>

        <div class="form-group">
          <label class="form-label">Message Body</label>
          <textarea class="form-control" name="message" id="bulkMsgBody" rows="5" placeholder="Type your message here..." required style="resize:none;"></textarea>
        </div>

        <div class="form-group">
          <label class="form-label">Communication Channel</label>
          <select class="form-control" name="channel">
            <option value="both">Both (Email + SMS)</option>
            <option value="email">Email Only</option>
            <option value="sms">SMS Only</option>
          </select>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('sendMinistryMessageModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">
          <i class="ph ph-paper-plane-tilt"></i> Send Broadcast
        </button>
      </div>
    </form>
  </div>
</div>