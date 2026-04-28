<!-- Add Ministry Modal -->
<div class="modal-overlay" id="addMinistryModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Create New Ministry</h3>
      <button class="close-btn" onclick="closeModal('addMinistryModal')"><i class="ph ph-x"></i></button>
    </div>
    <form action="" method="POST" id="addMinistryForm">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Ministry Name</label>
          <input class="form-control" name="ministry_name" placeholder="e.g. Media Ministry">
        </div>
        <div class="form-group">
          <label class="form-label">Purpose/Description</label>
          <textarea class="form-control" name="description" rows="3" placeholder="Describe the goal..."></textarea>
        </div>
        <div class="grid-2" style="gap:16px;">
          <div class="form-group">
            <label class="form-label">Ministry Head</label>
            <input class="form-control" name="head_name" list="heads_list" placeholder="Enter name or select...">
            <datalist id="heads_list">
              <option value="Elder Asante">
              <option value="Pastor Adu">
              <option value="Brother Kwame">
            </datalist>
            <div style="font-size:11px;color:var(--muted);margin-top:4px;">
              You can type a new name or select from the list.
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Meeting Day</label>
            <select class="form-control" name="meeting_day">
              <option>Saturdays</option>
              <option>Fridays</option>
              <option>Sundays</option>
              <option>Wednesdays</option>
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
            <div style="font-size:11px;color:var(--deep);text-transform:uppercase;margin-bottom:4px;">Total Members</div>
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
        <form id="editMinistryForm">
          <div class="form-group">
            <label class="form-label">Ministry Name</label>
            <input class="form-control" id="edit_mName" value="Music Ministry">
          </div>
          <div class="form-group">
            <label class="form-label">Purpose/Description</label>
            <textarea class="form-control" id="edit_mDesc" rows="3">Worship & praise team</textarea>
          </div>
          <div class="grid-2" style="gap:16px;">
            <div class="form-group">
              <label class="form-label">Ministry Head</label>
              <input class="form-control" id="edit_mHead" list="heads_list" value="Elder Asante">
            </div>
            <div class="form-group">
              <label class="form-label">Meeting Day</label>
              <select class="form-control" id="edit_mDay">
                <option selected>Saturdays</option>
                <option>Fridays</option>
                <option>Sundays</option>
                <option>Wednesdays</option>
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
