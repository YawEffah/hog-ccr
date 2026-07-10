<!-- ================================================================
  MEMBER MODALS
  1. addMemberModal  – Register new member (2-step wizard)
  2. editMemberModal – Edit member profile (2-step wizard)
================================================================ -->

<!-- ─── Shared Step-Wizard Styles ─────────────────────────────────── -->
<style>
  .step-indicator {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0;
    margin-bottom: 24px;
  }
  .step-dot {
    width: 32px; height: 32px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 700;
    background: #EDE8DF; color: var(--muted);
    transition: all .25s;
    flex-shrink: 0;
  }
  .step-dot.active  { background: var(--gold);  color: #fff; }
  .step-dot.done    { background: var(--success); color: #fff; }
  .step-line {
    flex: 1; height: 2px; background: #EDE8DF;
    max-width: 80px; transition: background .25s;
  }
  .step-line.done { background: var(--success); }
  .step-label { font-size: 11px; color: var(--muted); margin-top: 4px; text-align: center; }
  .member-step { display: none; }
  .member-step.active { display: block; }
  .section-heading {
    font-size: 11px; font-weight: 700; letter-spacing: .8px;
    text-transform: uppercase; color: var(--muted);
    border-bottom: 1px solid #EDE8DF;
    padding-bottom: 6px; margin: 20px 0 14px;
  }
  .section-heading:first-child { margin-top: 0; }
  .yn-group { display: flex; gap: 10px; margin-top: 6px; }
  .yn-option {
    display: flex; align-items: center; gap: 6px;
    font-size: 13px; cursor: pointer;
    background: #F8FAFC; border: 1.5px solid #EDE8DF;
    border-radius: 8px; padding: 6px 16px;
    transition: all .15s;
  }
  .yn-option input { display: none; }
  .yn-option.selected { border-color: var(--gold); background: var(--gold-pale); color: var(--deep); font-weight: 600; }
</style>

<!-- ─── 1. ADD MEMBER MODAL ─────────────────────────────────────────── -->
<div class="modal-overlay" id="addMemberModal">
  <div class="modal" style="max-width:620px;">
    <div class="modal-header" style="justify-content:space-between;align-items:center;">
      <h3>Register New Member</h3>
      <span style="width:1px;height:28px;background:#DDD5C8;display:inline-block;margin:0 14px;"></span>
      <div class="step-indicator" style="margin-bottom:0;gap:6px;flex:1;justify-content:flex-start;">
        <div style="display:flex;flex-direction:column;align-items:center;">
          <div class="step-dot active" id="addDot1" style="width:26px;height:26px;font-size:11px;">1</div>
          <div class="step-label" style="font-size:10px;">Personal</div>
        </div>
        <div class="step-line" id="addLine1" style="max-width:32px;"></div>
        <div style="display:flex;flex-direction:column;align-items:center;">
          <div class="step-dot" id="addDot2" style="width:26px;height:26px;font-size:11px;">2</div>
          <div class="step-label" style="font-size:10px;">Spiritual</div>
        </div>
      </div>
      <button class="close-btn" onclick="closeModal('addMemberModal')"><i class="ph ph-x"></i></button>
    </div>
    <form action="handlers/member_handler.php" method="POST" id="addMemberForm" enctype="multipart/form-data">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="add_member">
      <div class="modal-body">

        <!-- ── STEP 1: Personal Information ────────────────────── -->
        <div class="member-step active" id="addStep1">
            
            <!-- Photo + Basic Identity inline row -->
            <div class="photo-identity-row">
              <div class="photo-upload-container">
                <div class="photo-upload-circle" onclick="document.getElementById('addMemberPhoto').click()">
                  <img id="addPhotoPreview" src="" style="display:none;">
                  <div class="photo-upload-overlay" id="addPhotoPlaceholder"><i class="ph ph-camera"></i></div>
                </div>
                <label class="photo-upload-label" onclick="document.getElementById('addMemberPhoto').click()">Upload Photo</label>
                <input type="file" id="addMemberPhoto" name="photo" hidden accept="image/*"
                  onchange="handlePreview(this,'addPhotoPreview','addPhotoPlaceholder')">
              </div>
              <div class="fields-block">
                <div class="grid-2" style="gap:12px;margin-bottom:12px;">
                  <div class="form-group" style="margin-bottom:0;"><label class="form-label">First Name</label>
                    <input class="form-control" name="first_name" placeholder="e.g. Abena" required></div>
                  <div class="form-group" style="margin-bottom:0;"><label class="form-label">Last Name</label>
                    <input class="form-control" name="last_name" placeholder="e.g. Kusi" required></div>
                </div>
                <div class="grid-2" style="gap:12px;">
                  <div class="form-group" style="margin-bottom:0;"><label class="form-label">Gender</label>
                    <select class="form-control" name="gender" required>
                      <option value="Male">Male</option>
                      <option value="Female">Female</option>
                    </select></div>
                  <div class="form-group" style="margin-bottom:0;"><label class="form-label">Date of Birth</label>
                    <input type="date" name="dob" class="form-control"></div>
                </div>
              </div>
            </div>

            <div class="section-heading">Contact & Location</div>
            <div class="grid-2" style="gap:16px;">
              <div class="form-group"><label class="form-label">Phone Number</label>
                <input class="form-control" name="phone" placeholder="0244-000-000"></div>
              <div class="form-group"><label class="form-label">Second Phone (Optional)</label>
                <input class="form-control" name="phone2" placeholder="0200-000-000"></div>
            </div>
            <div class="form-group"><label class="form-label">Email Address</label>
              <input class="form-control" type="email" name="email" placeholder="member@email.com"></div>
            <div class="grid-2" style="gap:16px;">
              <div class="form-group"><label class="form-label">Residential Address</label>
                <input class="form-control" name="address" placeholder="House / Street / Area"></div>
              <div class="form-group"><label class="form-label">Home Town</label>
                <input class="form-control" name="home_town" placeholder="e.g. Kumasi"></div>
            </div>

            <div class="section-heading">Personal Status</div>
            <div class="grid-2" style="gap:16px;">
              <div class="form-group"><label class="form-label">Occupation</label>
                <input class="form-control" name="occupation" placeholder="e.g. Teacher"></div>
              <div class="form-group"><label class="form-label">Marital Status</label>
                <select class="form-control" name="marital_status">
                  <option value="">Select…</option>
                  <option value="Single">Single</option>
                  <option value="Married">Married</option>
                  <option value="Widowed">Widowed</option>
                  <option value="Divorced">Divorced</option>
                </select></div>
            </div>
            <div class="grid-2" style="gap:16px;">
              <div class="form-group"><label class="form-label">Number of Children</label>
                <input type="number" min="0" class="form-control" name="children_count" value="0"></div>
              <div class="form-group"><label class="form-label">Member Status</label>
                <select class="form-control" name="status">
                  <option value="Active">Active</option>
                  <option value="Inactive">Inactive</option>
                  <option value="Affiliate Community Member">Affiliate Community Member</option>
                </select></div>
            </div>

            <div class="section-heading">Sacramental Status</div>
            <div class="grid-2" style="gap:16px;">
              <div class="form-group">
                <label class="form-label">Baptised?</label>
                <div class="yn-group">
                  <label class="yn-option" onclick="selectYN(this,'add_is_baptised','1')">
                    <input type="radio" name="is_baptised" value="1"> Yes
                  </label>
                  <label class="yn-option" onclick="selectYN(this,'add_is_baptised','0')">
                    <input type="radio" name="is_baptised" value="0"> No
                  </label>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Communicant?</label>
                <div class="yn-group">
                  <label class="yn-option" onclick="selectYN(this,'add_is_communicant','1')">
                    <input type="radio" name="is_communicant" value="1"> Yes
                  </label>
                  <label class="yn-option" onclick="selectYN(this,'add_is_communicant','0')">
                    <input type="radio" name="is_communicant" value="0"> No
                  </label>
                </div>
              </div>
            </div>
          </div><!-- /addStep1 -->

          <!-- ── STEP 2: Religious / Spiritual Information ──────── -->
          <div class="member-step" id="addStep2">

            <div class="section-heading">Sacraments Still Needed</div>
            <div style="display:flex;flex-wrap:wrap;gap:12px;">
              <?php foreach (['First Communion','Confirmation','Holy Matrimony','Holy Orders'] as $sac): ?>
                <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                  <input type="checkbox" name="sacraments_needed[]" value="<?= $sac ?>"> <?= $sac ?>
                </label>
              <?php endforeach; ?>
            </div>

            <div class="section-heading">Programmes Attended</div>
            <div style="display:flex;flex-wrap:wrap;gap:12px;">
              <?php foreach (['Life in the Spirit Seminar','Growth in the Spirit Seminar','Charisms Session','Catholic Alpha'] as $prog): ?>
                <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                  <input type="checkbox" name="programmes[]" value="<?= $prog ?>"> <?= $prog ?>
                </label>
              <?php endforeach; ?>
            </div>

            <div class="section-heading">Group Memberships</div>
            <div class="form-group">
              <label class="form-label">Church Ministries / Groups</label>
              <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:4px;">
                <?php foreach ($ministries as $m): ?>
                  <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                    <input type="checkbox" name="ministries[]" value="<?= $m['id'] ?>">
                    <?= htmlspecialchars($m['name']) ?>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Families</label>
              <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:4px;">
                <?php foreach ($families as $f): ?>
                  <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                    <input type="checkbox" name="families[]" value="<?= $f['id'] ?>">
                    <?= htmlspecialchars($f['name']) ?>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Other Groups (Religious / Social — inside or outside the church)</label>
              <textarea class="form-control" name="group_memberships" rows="2"
                placeholder="e.g. CDA, Knights of Marshall, KNUST Alumni Association…"
                style="resize:none;"></textarea>
            </div>

            <div class="section-heading">Next of Kin</div>
            <div class="grid-2" style="gap:16px;">
              <div class="form-group"><label class="form-label">Full Name</label>
                <input class="form-control" name="next_of_kin_name" placeholder="e.g. Kofi Mensah"></div>
              <div class="form-group"><label class="form-label">Relationship</label>
                <input class="form-control" name="next_of_kin_relation" placeholder="e.g. Spouse, Parent…"></div>
            </div>
            <div class="grid-2" style="gap:16px;">
              <div class="form-group"><label class="form-label">Address</label>
                <input class="form-control" name="next_of_kin_address" placeholder="Residential address"></div>
              <div class="form-group"><label class="form-label">Phone</label>
                <input class="form-control" name="next_of_kin_phone" placeholder="0244-000-000"></div>
            </div>

            <div style="background:#F1F5F9;border-radius:10px;padding:14px;display:flex;align-items:center;gap:12px;">
              <input type="checkbox" id="sendWelcome" name="send_welcome" checked
                style="width:16px;height:16px;cursor:pointer;">
              <label for="sendWelcome" style="font-size:13px;font-weight:600;cursor:pointer;color:var(--deep2);">
                Send welcome message automatically
              </label>
            </div>
          </div><!-- /addStep2 -->

      </div><!-- /modal-body -->

      <div class="modal-footer" style="justify-content:space-between;">
        <div>
          <button type="button" class="btn btn-outline" id="addBackBtn" style="display:none;"
            onclick="memberStepNav('add',1)">← Back</button>
          <button type="button" class="btn btn-outline" id="addCancelBtn"
            onclick="closeModal('addMemberModal')">Cancel</button>
        </div>
        <div style="display:flex;gap:10px;">
          <button type="button" class="btn btn-primary" id="addNextBtn"
            onclick="memberStepNav('add',2)">Next →</button>
          <button type="submit" class="btn btn-primary" id="addSubmitBtn" style="display:none;">
            <i class="ph ph-hand-heart"></i> Register Member
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- ─── 2. EDIT MEMBER MODAL ──────────────────────────────────────── -->
<div class="modal-overlay" id="editMemberModal">
  <div class="modal" style="max-width:620px;">
    <div class="modal-header" style="justify-content:space-between;align-items:center;">
      <h3>Member Profile</h3>
      <span style="width:1px;height:28px;background:#DDD5C8;display:inline-block;margin:0 14px;"></span>
      <div class="step-indicator" style="margin-bottom:0;gap:6px;flex:1;justify-content:flex-start;">
        <div style="display:flex;flex-direction:column;align-items:center;">
          <div class="step-dot active" id="editDot1" style="width:26px;height:26px;font-size:11px;">1</div>
          <div class="step-label" style="font-size:10px;">Personal</div>
        </div>
        <div class="step-line" id="editLine1" style="max-width:32px;"></div>
        <div style="display:flex;flex-direction:column;align-items:center;">
          <div class="step-dot" id="editDot2" style="width:26px;height:26px;font-size:11px;">2</div>
          <div class="step-label" style="font-size:10px;">Spiritual</div>
        </div>
      </div>
      <button class="close-btn" onclick="closeModal('editMemberModal')"><i class="ph ph-x"></i></button>
    </div>
    <form action="handlers/member_handler.php" method="POST" id="editMemberForm" enctype="multipart/form-data">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="edit_member">
      <input type="hidden" name="member_id" id="editMemberId">
      <div class="modal-body">

          <!-- ── STEP 1: Personal ──────────────────────────────── -->
          <div class="member-step active" id="editStep1">

            <!-- Photo + Basic Identity inline row -->
            <div class="photo-identity-row">
              <div class="photo-upload-container">
                <div class="photo-upload-circle" onclick="document.getElementById('editMemberPhoto').click()">
                  <img id="editPhotoPreview" src="" style="display:none;">
                  <div class="photo-upload-overlay" id="editPhotoPlaceholder"><i class="ph ph-camera"></i></div>
                </div>
                <label class="photo-upload-label" onclick="document.getElementById('editMemberPhoto').click()">Change Photo</label>
                <input type="file" id="editMemberPhoto" name="photo" hidden accept="image/*"
                  onchange="handlePreview(this,'editPhotoPreview','editPhotoPlaceholder')">
              </div>
              <div class="fields-block">
                <div class="grid-2" style="gap:12px;margin-bottom:12px;">
                  <div class="form-group" style="margin-bottom:0;"><label class="form-label">First Name</label>
                    <input class="form-control" name="first_name" id="editFirstName" required></div>
                  <div class="form-group" style="margin-bottom:0;"><label class="form-label">Last Name</label>
                    <input class="form-control" name="last_name" id="editLastName" required></div>
                </div>
                <div class="grid-2" style="gap:12px;">
                  <div class="form-group" style="margin-bottom:0;"><label class="form-label">Gender</label>
                    <select class="form-control" name="gender" id="editGender" required>
                      <option value="Male">Male</option>
                      <option value="Female">Female</option>
                    </select></div>
                  <div class="form-group" style="margin-bottom:0;"><label class="form-label">Date of Birth</label>
                    <input type="date" name="dob" id="editDob" class="form-control"></div>
                </div>
              </div>
            </div>

            <div class="section-heading">Contact & Location</div>
            <div class="grid-2" style="gap:16px;">
              <div class="form-group"><label class="form-label">Phone Number</label>
                <input class="form-control" name="phone" id="editPhone" placeholder="0244-000-000"></div>
              <div class="form-group"><label class="form-label">Second Phone (Optional)</label>
                <input class="form-control" name="phone2" id="editPhone2" placeholder="0200-000-000"></div>
            </div>
            <div class="form-group"><label class="form-label">Email Address</label>
              <input class="form-control" type="email" name="email" id="editEmail" placeholder="member@email.com"></div>
            <div class="grid-2" style="gap:16px;">
              <div class="form-group"><label class="form-label">Residential Address</label>
                <input class="form-control" name="address" id="editAddress"></div>
              <div class="form-group"><label class="form-label">Home Town</label>
                <input class="form-control" name="home_town" id="editHomeTown" placeholder="e.g. Kumasi"></div>
            </div>

            <div class="section-heading">Personal Status</div>
            <div class="grid-2" style="gap:16px;">
              <div class="form-group"><label class="form-label">Occupation</label>
                <input class="form-control" name="occupation" id="editOccupation" placeholder="e.g. Teacher"></div>
              <div class="form-group"><label class="form-label">Marital Status</label>
                <select class="form-control" name="marital_status" id="editMaritalStatus">
                  <option value="">Select…</option>
                  <option value="Single">Single</option>
                  <option value="Married">Married</option>
                  <option value="Widowed">Widowed</option>
                  <option value="Divorced">Divorced</option>
                </select></div>
            </div>
            <div class="grid-2" style="gap:16px;">
              <div class="form-group"><label class="form-label">Number of Children</label>
                <input type="number" min="0" class="form-control" name="children_count" id="editChildrenCount" value="0"></div>
              <div class="form-group"><label class="form-label">Member Status</label>
                <select class="form-control" name="status" id="editStatus">
                  <option value="Active">Active</option>
                  <option value="Inactive">Inactive</option>
                  <option value="Affiliate Community Member">Affiliate Community Member</option>
                </select></div>
            </div>

            <div class="section-heading">Sacramental Status</div>
            <div class="grid-2" style="gap:16px;">
              <div class="form-group">
                <label class="form-label">Baptised?</label>
                <div class="yn-group" id="editBaptisedGroup">
                  <label class="yn-option" id="editBaptisedYes" onclick="selectYN(this,'edit_is_baptised','1')">
                    <input type="radio" name="is_baptised" value="1"> Yes
                  </label>
                  <label class="yn-option" id="editBaptisedNo" onclick="selectYN(this,'edit_is_baptised','0')">
                    <input type="radio" name="is_baptised" value="0"> No
                  </label>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Communicant?</label>
                <div class="yn-group" id="editCommunicantGroup">
                  <label class="yn-option" id="editCommunicantYes" onclick="selectYN(this,'edit_is_communicant','1')">
                    <input type="radio" name="is_communicant" value="1"> Yes
                  </label>
                  <label class="yn-option" id="editCommunicantNo" onclick="selectYN(this,'edit_is_communicant','0')">
                    <input type="radio" name="is_communicant" value="0"> No
                  </label>
                </div>
              </div>
            </div>
          </div><!-- /editStep1 -->

          <!-- ── STEP 2: Spiritual ──────────────────────────────── -->
          <div class="member-step" id="editStep2">

            <div class="section-heading">Sacraments Still Needed</div>
            <div style="display:flex;flex-wrap:wrap;gap:12px;" id="editSacramentsNeededWrap">
              <?php foreach (['First Communion','Confirmation','Holy Matrimony','Holy Orders'] as $sac): ?>
                <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                  <input type="checkbox" name="sacraments_needed[]" value="<?= $sac ?>" class="edit-sac-needed-cb">
                  <?= $sac ?>
                </label>
              <?php endforeach; ?>
            </div>

            <div class="section-heading">Programmes Attended</div>
            <div style="display:flex;flex-wrap:wrap;gap:12px;" id="editProgrammesWrap">
              <?php foreach (['Life in the Spirit Seminar','Growth in the Spirit Seminar','Charisms Session','Catholic Alpha'] as $prog): ?>
                <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                  <input type="checkbox" name="programmes[]" value="<?= $prog ?>" class="edit-prog-cb">
                  <?= $prog ?>
                </label>
              <?php endforeach; ?>
            </div>

            <div class="section-heading">Group Memberships</div>
            <div class="form-group">
              <label class="form-label">Church Ministries / Groups</label>
              <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:4px;" id="editMinistriesWrap">
                <?php foreach ($ministries as $m): ?>
                  <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                    <input type="checkbox" name="ministries[]" value="<?= $m['id'] ?>" class="edit-ministry-cb">
                    <?= htmlspecialchars($m['name']) ?>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Families</label>
              <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:4px;" id="editFamiliesWrap">
                <?php foreach ($families as $f): ?>
                  <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                    <input type="checkbox" name="families[]" value="<?= $f['id'] ?>" class="edit-family-cb">
                    <?= htmlspecialchars($f['name']) ?>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Other Groups (Religious / Social)</label>
              <textarea class="form-control" name="group_memberships" id="editGroupMemberships" rows="2"
                placeholder="e.g. CDA, Knights of Marshall…" style="resize:none;"></textarea>
            </div>

            <div class="section-heading">Next of Kin</div>
            <div class="grid-2" style="gap:16px;">
              <div class="form-group"><label class="form-label">Full Name</label>
                <input class="form-control" name="next_of_kin_name" id="editNokName"></div>
              <div class="form-group"><label class="form-label">Relationship</label>
                <input class="form-control" name="next_of_kin_relation" id="editNokRelation" placeholder="e.g. Spouse"></div>
            </div>
            <div class="grid-2" style="gap:16px;">
              <div class="form-group"><label class="form-label">Address</label>
                <input class="form-control" name="next_of_kin_address" id="editNokAddress"></div>
              <div class="form-group"><label class="form-label">Phone</label>
                <input class="form-control" name="next_of_kin_phone" id="editNokPhone" placeholder="0244-000-000"></div>
            </div>

          </div><!-- /editStep2 -->

      </div><!-- /modal-body -->

      <div class="modal-footer" style="justify-content:space-between;">
        <div>
          <button type="button" class="btn btn-outline" id="editBackBtn" style="display:none;"
            onclick="memberStepNav('edit',1)">← Back</button>
          <button type="button" class="btn btn-outline" id="editCancelBtn"
            onclick="closeModal('editMemberModal')">Cancel</button>
        </div>
        <div style="display:flex;gap:10px;">
          <button type="button" class="btn btn-primary" id="editNextBtn"
            onclick="memberStepNav('edit',2)">Next →</button>
          <button type="submit" class="btn btn-primary" id="editSubmitBtn" style="display:none;">
            <i class="ph ph-floppy-disk"></i> Save Changes
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
/* ─────────────────────────────────────────────────────────
   TWO-STEP MEMBER WIZARD JAVASCRIPT
───────────────────────────────────────────────────────── */

function memberStepNav(prefix, toStep) {
  const s1   = document.getElementById(prefix + 'Step1');
  const s2   = document.getElementById(prefix + 'Step2');
  const dot1 = document.getElementById(prefix + 'Dot1');
  const dot2 = document.getElementById(prefix + 'Dot2');
  const line = document.getElementById(prefix + 'Line1');
  const nextBtn   = document.getElementById(prefix + 'NextBtn');
  const backBtn   = document.getElementById(prefix + 'BackBtn');
  const cancelBtn = document.getElementById(prefix + 'CancelBtn');
  const submitBtn = document.getElementById(prefix + 'SubmitBtn');

  if (toStep === 2) {
    // Simple validation for step 1
    const form = document.getElementById(prefix + 'MemberForm');
    const firstStep = document.getElementById(prefix + 'Step1');
    const required = firstStep.querySelectorAll('[required]');
    let valid = true;
    required.forEach(el => { if (!el.value.trim()) { el.focus(); valid = false; } });
    if (!valid) return;

    s1.classList.remove('active'); s2.classList.add('active');
    dot1.classList.remove('active'); dot1.classList.add('done');
    dot1.innerHTML = '<i class="ph ph-check" style="font-size:13px;"></i>';
    dot2.classList.add('active');
    line.classList.add('done');
    nextBtn.style.display = 'none';
    cancelBtn.style.display = 'none';
    backBtn.style.display = '';
    submitBtn.style.display = '';
  } else {
    s2.classList.remove('active'); s1.classList.add('active');
    dot1.classList.add('active'); dot1.classList.remove('done');
    dot1.innerHTML = '1';
    dot2.classList.remove('active');
    line.classList.remove('done');
    nextBtn.style.display = '';
    cancelBtn.style.display = '';
    backBtn.style.display = 'none';
    submitBtn.style.display = 'none';
  }
}

/* Reset wizard to step 1 when modal closes */
function resetMemberWizard(prefix) {
  memberStepNav(prefix, 1);
}

/* Yes/No pill toggle */
function selectYN(labelEl, groupName, value) {
  const group = labelEl.closest('.yn-group');
  group.querySelectorAll('.yn-option').forEach(l => l.classList.remove('selected'));
  labelEl.classList.add('selected');
  labelEl.querySelector('input').checked = true;
}

/* Pre-select a YN group by value ('1' or '0') */
function setYN(groupId, value) {
  const group = document.getElementById(groupId);
  if (!group) return;
  group.querySelectorAll('.yn-option').forEach(lbl => {
    const radio = lbl.querySelector('input');
    if (radio.value === value) {
      lbl.classList.add('selected');
      radio.checked = true;
    } else {
      lbl.classList.remove('selected');
    }
  });
}

/* Populate edit modal */
function openEditModal(m) {
  // Reset wizard to step 1
  resetMemberWizard('edit');

  document.getElementById('editMemberId').value      = m.id;
  document.getElementById('editFirstName').value     = m.first_name  || '';
  document.getElementById('editLastName').value      = m.last_name   || '';
  document.getElementById('editGender').value        = m.gender      || 'Male';
  document.getElementById('editDob').value           = m.dob         || '';
  document.getElementById('editPhone').value         = m.phone       || '';
  document.getElementById('editPhone2').value        = m.phone2      || '';
  document.getElementById('editEmail').value         = m.email       || '';
  document.getElementById('editAddress').value       = m.address     || '';
  document.getElementById('editHomeTown').value      = m.home_town   || '';
  document.getElementById('editOccupation').value    = m.occupation  || '';
  document.getElementById('editMaritalStatus').value = m.marital_status || '';
  document.getElementById('editChildrenCount').value = m.children_count || 0;
  document.getElementById('editStatus').value        = m.status      || 'Active';
  document.getElementById('editGroupMemberships').value = m.group_memberships || '';
  document.getElementById('editNokName').value       = m.next_of_kin_name     || '';
  document.getElementById('editNokRelation').value   = m.next_of_kin_relation || '';
  document.getElementById('editNokAddress').value    = m.next_of_kin_address  || '';
  document.getElementById('editNokPhone').value      = m.next_of_kin_phone    || '';

  // Baptised / Communicant Y/N
  setYN('editBaptisedGroup',    m.is_baptised    ? '1' : '0');
  setYN('editCommunicantGroup', m.is_communicant ? '1' : '0');

  // Photo preview
  const prev = document.getElementById('editPhotoPreview');
  const phld = document.getElementById('editPhotoPlaceholder');
  if (m.photo_path) {
    prev.src = m.photo_path; prev.style.display = '';
    phld.style.display = 'none';
  } else {
    prev.style.display = 'none'; phld.style.display = '';
  }

  // Ministries
  document.querySelectorAll('.edit-ministry-cb').forEach(cb => {
    cb.checked = Array.isArray(m.ministries) && m.ministries.includes(parseInt(cb.value));
  });

  // Families
  document.querySelectorAll('.edit-family-cb').forEach(cb => {
    cb.checked = Array.isArray(m.families) && m.families.includes(parseInt(cb.value));
  });

  // Sacraments needed
  document.querySelectorAll('.edit-sac-needed-cb').forEach(cb => {
    cb.checked = Array.isArray(m.sacraments_needed) && m.sacraments_needed.includes(cb.value);
  });

  // Programmes
  document.querySelectorAll('.edit-prog-cb').forEach(cb => {
    cb.checked = Array.isArray(m.programmes) && m.programmes.includes(cb.value);
  });

  openModal('editMemberModal');
}
</script>