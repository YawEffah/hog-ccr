<!-- MODALS -->
<!-- Add Member Modal -->
<div class="modal-overlay" id="addMemberModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Register New Member</h3>
      <button class="close-btn" onclick="closeModal('addMemberModal')"><i class="ph ph-x"></i></button>
    </div>
    <form action="handlers/member_handler.php" method="POST" id="addMemberForm" enctype="multipart/form-data">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="add_member">
      <div class="modal-body">
        <div class="photo-upload-container">
          <div class="photo-upload-circle" onclick="document.getElementById('addMemberPhoto').click()">
            <img id="addPhotoPreview" src="" style="display:none;">
            <div class="photo-upload-overlay" id="addPhotoPlaceholder">
              <i class="ph ph-camera"></i>
            </div>
          </div>
          <label class="photo-upload-label" onclick="document.getElementById('addMemberPhoto').click()">Upload
            Photo</label>
          <input type="file" id="addMemberPhoto" name="photo" hidden accept="image/*"
            onchange="handlePreview(this, 'addPhotoPreview', 'addPhotoPlaceholder')">
        </div>
        <div class="grid-2" style="gap:16px;">
          <div class="form-group"><label class="form-label">First Name</label><input class="form-control"
              name="first_name" placeholder="e.g. Abena" required></div>
          <div class="form-group"><label class="form-label">Last Name</label><input class="form-control"
              name="last_name" placeholder="e.g. Kusi" required></div>
        </div>
        <div class="grid-2" style="gap:16px;">
          <div class="form-group"><label class="form-label">Gender</label>
            <select class="form-control" name="gender" required>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
            </select>
          </div>
          <div class="form-group"><label class="form-label">Date of Birth</label><input type="date" name="dob"
              class="form-control"></div>
        </div>
        <div class="grid-2" style="gap:16px;">
          <div class="form-group"><label class="form-label">Phone</label><input class="form-control" name="phone"
              placeholder="0244-000-000"></div>
          <div class="form-group"><label class="form-label">Email Address</label><input class="form-control"
              type="email" name="email" placeholder="member@email.com"></div>
        </div>
        <div class="grid-2" style="gap:16px;">
          <div class="form-group"><label class="form-label">Ministry</label><select class="form-control"
              name="ministry_id">
              <option value="">— Select —</option>
              <?php foreach ($ministries as $m): ?>
                <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
              <?php endforeach; ?>
            </select></div>
          <div class="form-group"><label class="form-label">Status</label><select class="form-control" name="status">
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
              <option value="Visitor">Visitor</option>
            </select></div>
        </div>
        <div class="form-group"><label class="form-label">Home Address</label><input class="form-control" name="address"
            placeholder="Residential address"></div>
        <div class="form-group"><label class="form-label">Sacraments</label>
          <div style="display:flex;gap:16px;margin-top:6px;flex-wrap:wrap;">
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;"><input
                name="sacraments[]" value="Baptised" type="checkbox"> Baptised</label>
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;"><input
                name="sacraments[]" value="Confirmed" type="checkbox"> Confirmed</label>
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;"><input
                name="sacraments[]" value="First Communion" type="checkbox"> First Communion</label>
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;"><input
                name="sacraments[]" value="Matrimony" type="checkbox"> Matrimony</label>
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;"><input
                name="sacraments[]" value="Orders" type="checkbox"> Orders</label>
          </div>
        </div>
        <div class="form-group"><label class="form-label">Notes</label>
          <textarea class="form-control" name="notes" rows="2" placeholder="Any pastoral or general notes…"
            style="resize:none;"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addMemberModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Register Member</button>
      </div>
    </form>
  </div>
</div>



<!-- Edit Member Modal -->
<div class="modal-overlay" id="editMemberModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Member Profile</h3>
      <button class="close-btn" onclick="closeModal('editMemberModal')"><i class="ph ph-x"></i></button>
    </div>
    <form action="handlers/member_handler.php" method="POST" id="editMemberForm" enctype="multipart/form-data">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="edit_member">
      <input type="hidden" name="member_id" id="editMemberId">
      <div class="modal-body">
        <div class="photo-upload-container">
          <div class="photo-upload-circle" onclick="document.getElementById('editMemberPhoto').click()">
            <img id="editPhotoPreview" src="" style="display:none;">
            <div class="photo-upload-overlay" id="editPhotoPlaceholder">
              <i class="ph ph-camera"></i>
            </div>
          </div>
          <label class="photo-upload-label" onclick="document.getElementById('editMemberPhoto').click()">Change
            Photo</label>
          <input type="file" id="editMemberPhoto" name="photo" hidden accept="image/*"
            onchange="handlePreview(this, 'editPhotoPreview', 'editPhotoPlaceholder')">
        </div>
        <div class="grid-2" style="gap:16px;">
          <div class="form-group"><label class="form-label">First Name</label><input class="form-control"
              name="first_name" id="editFirstName" required></div>
          <div class="form-group"><label class="form-label">Last Name</label><input class="form-control"
              name="last_name" id="editLastName" required></div>
        </div>
        <div class="grid-2" style="gap:16px;">
          <div class="form-group"><label class="form-label">Gender</label>
            <select class="form-control" name="gender" id="editGender" required>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
            </select>
          </div>
          <div class="form-group"><label class="form-label">Date of Birth</label><input type="date" name="dob"
              id="editDob" class="form-control"></div>
        </div>
        <div class="grid-2" style="gap:16px;">
          <div class="form-group"><label class="form-label">Phone</label><input class="form-control" name="phone"
              id="editPhone"></div>
          <div class="form-group"><label class="form-label">Email Address</label><input class="form-control"
              type="email" name="email" id="editEmail"></div>
        </div>
        <div class="grid-2" style="gap:16px;">
          <div class="form-group"><label class="form-label">Ministry</label><select class="form-control"
              name="ministry_id" id="editMinistry">
              <option value="">— Select —</option>
              <?php foreach ($ministries as $m): ?>
                <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
              <?php endforeach; ?>
            </select></div>
          <div class="form-group"><label class="form-label">Status</label><select class="form-control" name="status"
              id="editStatus">
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
              <option value="Visitor">Visitor</option>
            </select></div>
        </div>
        <div class="form-group"><label class="form-label">Home Address</label><input class="form-control" name="address"
            id="editAddress"></div>
        <div class="form-group"><label class="form-label">Sacraments</label>
          <div style="display:flex;gap:16px;margin-top:6px;flex-wrap:wrap;">
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;"><input
                name="sacraments[]" value="Baptised" type="checkbox" id="sac_baptised"> Baptised</label>
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;"><input
                name="sacraments[]" value="Confirmed" type="checkbox" id="sac_confirmed"> Confirmed</label>
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;"><input
                name="sacraments[]" value="First Communion" type="checkbox" id="sac_communion"> First Communion</label>
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;"><input
                name="sacraments[]" value="Matrimony" type="checkbox" id="sac_matrimony"> Matrimony</label>
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;"><input
                name="sacraments[]" value="Orders" type="checkbox" id="sac_orders"> Orders</label>
          </div>
        </div>
        <div class="form-group"><label class="form-label">Notes</label>
          <textarea class="form-control" name="notes" id="editNotes" rows="2"
            placeholder="Any pastoral or general notes…" style="resize:none;"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('editMemberModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>