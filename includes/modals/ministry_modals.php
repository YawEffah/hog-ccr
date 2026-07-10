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
          <div class="form-group">
            <label class="form-label">Meeting Time</label>
            <input class="form-control" name="meeting_time" placeholder="e.g. 6:30 PM">
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
        <div class="tab" onclick="switchMTab(this, 'mAttendancePane')">Attendance</div>
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
            <div style="flex:1;background:#1E40AF;height:60%;border-radius:4px 4px 0 0;"></div>
            <div style="flex:1;background:#F87171;height:80%;border-radius:4px 4px 0 0;"></div>
            <div style="flex:1;background:#1E40AF;height:40%;border-radius:4px 4px 0 0;"></div>
            <div style="flex:1;background:#F87171;height:90%;border-radius:4px 4px 0 0;"></div>
            <div style="flex:1;background:#F87171;height:75%;border-radius:4px 4px 0 0;"></div>
            <div style="flex:1;background:#1E40AF;height:85%;border-radius:4px 4px 0 0;"></div>
          </div>
        </div>
      </div>

      <!-- Members Tab -->
      <div id="mMembers" class="tab-pane" style="display:none;">
        <div style="display:flex; justify-content:flex-end; margin-bottom:12px;">
          <button class="btn btn-primary btn-sm" onclick="openEnrolMinistryMember()"><i class="ph ph-plus"></i> Enrol Member</button>
        </div>
        <div class="table-responsive">
          <table style="width:100%;font-size:13px;">
            <thead style="background:var(--bg-light);text-align:left;">
              <tr>
                <th style="padding:8px;">Member</th>
                <th style="padding:8px;">Role</th>
                <th style="padding:8px;">Joined</th>
                <th style="padding:8px;text-align:right;">Action</th>
              </tr>
            </thead>
            <tbody id="mMembersList">
              <!-- Populated via JS -->
            </tbody>
          </table>
        </div>
      </div>

      <!-- Attendance Tab -->
      <div id="mAttendancePane" class="tab-pane" style="display:none;">
        <!-- Attendance Stats -->
        <div class="grid-3" style="gap:12px;margin-bottom:20px;">
          <div style="background:#ECFDF5;padding:14px;border-radius:10px;border:1px solid rgba(16,185,129,0.15);text-align:center;">
            <div style="font-size:10px;color:#059669;text-transform:uppercase;font-weight:600;margin-bottom:4px;">Present</div>
            <div style="font-size:22px;font-weight:700;color:#059669;" id="attPresent">0</div>
          </div>
          <div style="background:#FEF2F2;padding:14px;border-radius:10px;border:1px solid rgba(239,68,68,0.15);text-align:center;">
            <div style="font-size:10px;color:#DC2626;text-transform:uppercase;font-weight:600;margin-bottom:4px;">Absent</div>
            <div style="font-size:22px;font-weight:700;color:#DC2626;" id="attAbsent">0</div>
          </div>
          <div style="background:var(--gold-pale);padding:14px;border-radius:10px;border:1px solid rgba(200,170,110,0.2);text-align:center;">
            <div style="font-size:10px;color:var(--gold);text-transform:uppercase;font-weight:600;margin-bottom:4px;">Avg Rate</div>
            <div style="font-size:22px;font-weight:700;color:var(--gold);" id="attRate">0%</div>
          </div>
        </div>

        <!-- Record Attendance Button -->
        <button class="btn btn-primary btn-sm" id="attShowFormBtn" onclick="toggleAttRecordForm()" style="margin-bottom:16px;">
          <i class="ph ph-clipboard-text"></i> Record Attendance
        </button>

        <!-- Record Attendance Form (hidden by default) -->
        <div id="attRecordForm" style="display:none;margin-bottom:20px;">
          <form action="handlers/attendance_handler.php" method="POST" id="ministryAttForm">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="record_ministry_attendance">
            <input type="hidden" name="ministry_id" id="att_ministryId">

            <div style="background:#FAFAF8;border:1px solid var(--border);border-radius:12px;padding:16px;margin-bottom:12px;">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                <div style="font-size:14px;font-weight:600;color:var(--deep2);">Record Attendance</div>
                <button type="button" class="btn btn-outline btn-sm" onclick="toggleAttRecordForm()" style="padding:4px 10px;">
                  <i class="ph ph-x"></i>
                </button>
              </div>

              <div class="grid-3" style="gap:12px;margin-bottom:14px;">
                <div class="form-group" style="margin-bottom:0;">
                  <label class="form-label" style="font-size:12px;">Session Type</label>
                  <select class="form-control" name="session_type" required style="font-size:13px;padding:8px 10px;">
                    <option value="Ministry Meeting">Ministry Meeting</option>
                    <option value="Practice">Practice</option>
                    <option value="Bible Study">Bible Study</option>
                    <option value="Rehearsal">Rehearsal</option>
                    <option value="Special">Special</option>
                  </select>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                  <label class="form-label" style="font-size:12px;">Date</label>
                  <input type="date" class="form-control" name="session_date" value="<?= date('Y-m-d') ?>" required style="font-size:13px;padding:8px 10px;">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                  <label class="form-label" style="font-size:12px;">Time</label>
                  <input type="time" class="form-control" name="session_time" value="<?= date('H:i') ?>" style="font-size:13px;padding:8px 10px;">
                </div>
              </div>

              <div style="margin-bottom:12px;">
                <label class="form-label" style="font-size:12px;">Mark Present Members</label>
                <div class="search-wrap" style="margin-bottom:8px;">
                  <i class="ph ph-magnifying-glass"></i>
                  <input class="search-input" placeholder="Search members…" id="attMemberSearch" oninput="filterAttMembers()" style="width:100%;">
                </div>
                <label style="display:flex;align-items:center;justify-content:space-between;padding:8px 14px;background:var(--gold-pale);border-radius:8px;cursor:pointer;margin-bottom:8px;border:1px dashed var(--gold);">
                  <span style="font-size:12px;font-weight:600;color:var(--gold);">Select All</span>
                  <input type="checkbox" id="attMarkAllChk" onchange="toggleAttMarkAll(this)">
                </label>
                <div id="attMemberList" style="display:flex;flex-direction:column;gap:8px;max-height:200px;overflow-y:auto;padding-right:4px;">
                  <!-- Populated via JS -->
                </div>
              </div>

              <button type="submit" class="btn btn-primary" style="width:100%;">
                <i class="ph ph-check-circle"></i> Save Attendance
              </button>
            </div>
          </form>
        </div>

        <!-- Attendance Records -->
        <div style="font-size:13px;font-weight:600;margin-bottom:12px;">Attendance Records</div>
        
        <!-- Filters -->
        <div style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap;">
          <input type="date" id="filterAttDate" class="form-control" style="font-size:12px;padding:6px 10px;flex:1;min-width:110px;" onchange="fetchAttendanceRecords()">
          <select id="filterAttType" class="form-control" style="font-size:12px;padding:6px 10px;flex:1;min-width:130px;" onchange="fetchAttendanceRecords()">
            <option value="">All Session Types</option>
            <option value="Ministry Meeting">Ministry Meeting</option>
            <option value="Practice">Practice</option>
            <option value="Bible Study">Bible Study</option>
            <option value="Rehearsal">Rehearsal</option>
            <option value="Special">Special</option>
          </select>
          <select id="filterAttMember" class="form-control" style="font-size:12px;padding:6px 10px;flex:1;min-width:140px;" onchange="fetchAttendanceRecords()">
            <option value="">All Members</option>
            <!-- Populated via JS -->
          </select>
          <button class="btn btn-outline btn-sm" onclick="clearAttFilters()" style="padding:6px 10px;" title="Clear Filters"><i class="ph ph-x"></i></button>
        </div>

        <!-- Table -->
        <div class="table-responsive" style="max-height:300px;overflow-y:auto;border:1px solid var(--border);border-radius:8px;">
          <table style="width:100%;font-size:12px;text-align:left;border-collapse:collapse;">
            <thead style="background:#F8FAFC;position:sticky;top:0;z-index:1;">
              <tr>
                <th style="padding:10px 12px;border-bottom:1px solid var(--border);">Date</th>
                <th style="padding:10px 12px;border-bottom:1px solid var(--border);">Type</th>
                <th style="padding:10px 12px;border-bottom:1px solid var(--border);">Member</th>
                <th style="padding:10px 12px;border-bottom:1px solid var(--border);">Status</th>
              </tr>
            </thead>
            <tbody id="attRecordsTable">
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
            <div class="form-group">
              <label class="form-label">Meeting Time</label>
              <input class="form-control" name="meeting_time" id="edit_mMeetingTime" placeholder="e.g. 6:30 PM">
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
      <button class="btn btn-primary" onclick="downloadMinistryReport()">Download Report</button>
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

<!-- Enrol Ministry Member Modal -->
<div class="modal-overlay" id="enrolMinistryMemberModal">
  <div class="modal" style="max-width:500px;">
    <div class="modal-header">
      <h3>Enrol Member in Ministry</h3>
      <button class="close-btn" onclick="closeModal('enrolMinistryMemberModal')"><i class="ph ph-x"></i></button>
    </div>
    <form action="handlers/ministry_handler.php" method="POST" id="enrolMinistryMemberForm">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="enrol_ministry_member">
      <input type="hidden" name="ministry_id" id="enrol_ministryId">
      <div class="modal-body">
        <div class="form-group" style="position: relative;">
          <label class="form-label">Select Member</label>
          <input class="form-control" id="enrol_mHeadDisplay" name="member_display"
            placeholder="Search member by name or ID..." oninput="filterMHeads(this.value, 'enrol')" autocomplete="off" required>
          <input type="hidden" name="member_id" id="enrol_mHeadId" required>
          <div id="enrol_mHeadSuggestions" class="search-suggestions" style="display:none;"></div>
        </div>

        <div class="form-group">
          <label class="form-label">Role</label>
          <select class="form-control" name="role" required>
            <option value="Member">Member</option>
            <option value="Leader">Leader</option>
            <option value="Assistant Leader">Assistant Leader</option>
            <option value="Secretary">Secretary</option>
            <option value="Treasurer">Treasurer</option>
            <option value="Patron">Patron</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Notes (Optional)</label>
          <textarea class="form-control" name="notes" rows="2" placeholder="Any remarks…" style="resize:none;"></textarea>
        </div>

        <div style="background:#F1F5F9;border-radius:10px;padding:14px;display:flex;align-items:center;gap:12px;margin-top:10px;margin-bottom:14px;">
          <input type="checkbox" id="sendMinistryWelcome" name="send_welcome" checked style="width:16px;height:16px;cursor:pointer;">
          <label for="sendMinistryWelcome" style="font-size:13px;font-weight:600;cursor:pointer;color:var(--deep2);display:block;">
            Send welcome message automatically
          </label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('enrolMinistryMemberModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">
          <i class="ph ph-hand-heart"></i> Enrol Member
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Ministry Member Modal -->
<div class="modal-overlay" id="editMinistryMemberModal">
  <div class="modal" style="max-width:500px;">
    <div class="modal-header">
      <h3>Edit Enrolled Member</h3>
      <button class="close-btn" onclick="closeModal('editMinistryMemberModal')"><i class="ph ph-x"></i></button>
    </div>
    <form action="handlers/ministry_handler.php" method="POST" id="editMinistryMemberForm">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="edit_ministry_member">
      <input type="hidden" name="ministry_id" id="edit_min_ministryId">
      <input type="hidden" name="member_id" id="edit_min_memberId">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Member</label>
          <input class="form-control" id="edit_min_memberName" readonly style="background:#F1F5F9;">
        </div>

        <div class="form-group">
          <label class="form-label">Role</label>
          <select class="form-control" name="role" id="edit_min_role" required>
            <option value="Member">Member</option>
            <option value="Leader">Leader</option>
            <option value="Assistant Leader">Assistant Leader</option>
            <option value="Secretary">Secretary</option>
            <option value="Treasurer">Treasurer</option>
            <option value="Patron">Patron</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Notes (Optional)</label>
          <textarea class="form-control" name="notes" id="edit_min_notes" rows="2" placeholder="Any remarks…" style="resize:none;"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('editMinistryMemberModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">
          <i class="ph ph-floppy-disk"></i> Save Changes
        </button>
      </div>
    </form>
  </div>
</div>