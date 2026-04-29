<!-- MODALS -->
<!-- Add Member Modal -->
<div class="modal-overlay" id="addMemberModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Register New Member</h3>
      <button class="close-btn" onclick="closeModal('addMemberModal')"><i class="ph ph-x"></i></button>
    </div>
    <form action="" method="POST" id="addMemberForm">
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
              name="first_name" placeholder="e.g. Abena"></div>
          <div class="form-group"><label class="form-label">Last Name</label><input class="form-control"
              name="last_name" placeholder="e.g. Kusi"></div>
        </div>
        <div class="grid-2" style="gap:16px;">
          <div class="form-group"><label class="form-label">Phone</label><input class="form-control" name="phone"
              placeholder="0244-000-000"></div>
          <div class="form-group"><label class="form-label">Date of Birth</label><input type="date" name="dob"
              class="form-control"></div>
        </div>
        <div class="form-group"><label class="form-label">Email Address</label><input class="form-control" type="email"
            name="email" placeholder="member@email.com"></div>
        <div class="grid-2" style="gap:16px;">
          <div class="form-group"><label class="form-label">Ministry</label><select class="form-control"
              name="ministry">
              <option value="">— Select —</option>
              <option>Music Ministry</option>
              <option>Intercessory</option>
              <option>Evangelism</option>
              <option>Youth Wing</option>
              <option>Prayer Group</option>
              <option>Executives</option>
            </select></div>
          <div class="form-group"><label class="form-label">Status</label><select class="form-control" name="status">
              <option>Active</option>
              <option>Inactive</option>
              <option>Visitor</option>
            </select></div>
        </div>
        <div class="form-group"><label class="form-label">Home Address</label><input class="form-control" name="address"
            placeholder="Residential address"></div>
        <div class="form-group"><label class="form-label">Sacraments</label>
          <div style="display:flex;gap:16px;margin-top:6px;">
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;"><input
                name="sacraments[]" value="baptised" type="checkbox"> Baptised</label>
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;"><input
                name="sacraments[]" value="confirmed" type="checkbox"> Confirmed</label>
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;"><input
                name="sacraments[]" value="communion" type="checkbox"> First Communion</label>
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;"><input
                name="sacraments[]" value="matrimony" type="checkbox"> Matrimony</label>
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;"><input
                name="sacraments[]" value="orders" type="checkbox"> Orders</label>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addMemberModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Register Member</button>
      </div>
    </form>
  </div>
</div>

<!-- View Member Modal -->
<div class="modal-overlay" id="viewMemberModal">
  <div class="modal">
    <div class="modal-header">
      <div style="display:flex;align-items:center;gap:12px;">
        <div id="viewPhotoContainer"
          style="width:48px;height:48px;border-radius:50%;overflow:hidden;display:none;border:1px solid var(--border);">
          <img id="viewPhoto" src="" style="width:100%;height:100%;object-fit:cover;">
        </div>
        <div class="avatar" style="background:var(--deep-pale);color:var(--deep);width:48px;height:48px;font-size:18px;"
          id="viewAvatar">JS</div>
        <div>
          <h3 id="viewName">John Smith</h3>
          <div style="font-size:12px;color:var(--muted);" id="viewId">CCR-001</div>
        </div>
      </div>
      <button class="close-btn" onclick="closeModal('viewMemberModal')"><i class="ph ph-x"></i></button>
    </div>
    <div class="modal-body">
      <div class="grid-2" style="gap:24px;margin-bottom:24px;">
        <div>
          <div style="font-size:11px;text-transform:uppercase;color:var(--muted);margin-bottom:4px;">Phone Number</div>
          <div style="font-weight:500;" id="viewPhone">0244-123-456</div>
        </div>
        <div>
          <div style="font-size:11px;text-transform:uppercase;color:var(--muted);margin-bottom:4px;">Email Address</div>
          <div style="font-weight:500;" id="viewEmail">john@email.com</div>
        </div>
        <div>
          <div style="font-size:11px;text-transform:uppercase;color:var(--muted);margin-bottom:4px;">Ministry</div>
          <div id="viewMinistry"><span class="badge badge-blue">Music Ministry</span></div>
        </div>
        <div>
          <div style="font-size:11px;text-transform:uppercase;color:var(--muted);margin-bottom:4px;">Status</div>
          <div id="viewStatus"><span class="badge badge-green">Active</span></div>
        </div>
        <div>
          <div style="font-size:11px;text-transform:uppercase;color:var(--muted);margin-bottom:4px;">Date of Birth</div>
          <div style="font-weight:500;" id="viewDob">12th Oct 1990</div>
        </div>
        <div>
          <div style="font-size:11px;text-transform:uppercase;color:var(--muted);margin-bottom:4px;">Date Joined</div>
          <div style="font-weight:500;" id="viewJoined">Jan 2023</div>
        </div>
      </div>

      <div style="margin-bottom:24px;">
        <div style="font-size:11px;text-transform:uppercase;color:var(--muted);margin-bottom:4px;">Residential Address
        </div>
        <div style="font-weight:500;" id="viewAddress">123 Garden Street, Kumasi</div>
      </div>

      <div>
        <div style="font-size:11px;text-transform:uppercase;color:var(--muted);margin-bottom:8px;">Sacraments</div>
        <div style="display:flex;gap:12px;" id="viewSacraments">
          <span class="badge badge-green">Baptised</span>
          <span class="badge badge-green">Confirmed</span>
          <span class="badge badge-gray">First Communion</span>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('viewMemberModal')">Close</button>
      <button class="btn btn-primary" id="viewEditBtn">Edit Member</button>
    </div>
  </div>
</div>

<!-- Edit Member Modal -->
<div class="modal-overlay" id="editMemberModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Edit Member Details</h3>
      <button class="close-btn" onclick="closeModal('editMemberModal')"><i class="ph ph-x"></i></button>
    </div>
    <form action="" method="POST" id="editMemberForm">
      <input type="hidden" name="member_id" id="editMemberId">
      <div class="modal-body">
        <div class="photo-upload-container">
          <div class="photo-upload-circle" onclick="document.getElementById('editMemberPhoto').click()">
            <img id="editPhotoPreview" src="" style="display:none;">
            <div class="photo-upload-overlay">
              <i class="ph ph-pencil"></i>
            </div>
          </div>
          <label class="photo-upload-label" onclick="document.getElementById('editMemberPhoto').click()">Change
            Photo</label>
          <input type="file" id="editMemberPhoto" name="photo" hidden accept="image/*"
            onchange="handlePreview(this, 'editPhotoPreview')">
        </div>
        <div class="grid-2" style="gap:16px;">
          <div class="form-group"><label class="form-label">First Name</label><input class="form-control"
              name="first_name" id="editFn" value="John"></div>
          <div class="form-group"><label class="form-label">Last Name</label><input class="form-control"
              name="last_name" id="editLn" value="Smith"></div>
        </div>
        <div class="grid-2" style="gap:16px;">
          <div class="form-group"><label class="form-label">Phone</label><input class="form-control" name="phone"
              id="editPhone" value="0244-123-456"></div>
          <div class="form-group"><label class="form-label">Date of Birth</label><input type="date" class="form-control"
              name="dob" id="editDob" value="1990-10-12"></div>
        </div>
        <div class="form-group"><label class="form-label">Email Address</label><input class="form-control" type="email"
            name="email" id="editEmail" value="john@email.com"></div>
        <div class="grid-2" style="gap:16px;">
          <div class="form-group"><label class="form-label">Ministry</label><select class="form-control" name="ministry"
              id="editMinistry">
              <option>Music Ministry</option>
              <option>Intercessory</option>
              <option>Evangelism</option>
              <option>Youth Wing</option>
              <option>Prayer Group</option>
              <option>Executives</option>
            </select></div>
          <div class="form-group"><label class="form-label">Status</label><select class="form-control" name="status"
              id="editStatus">
              <option>Active</option>
              <option>Inactive</option>
              <option>Visitor</option>
            </select></div>
        </div>
        <div class="form-group"><label class="form-label">Home Address</label><input class="form-control" name="address"
            id="editAddress" value="123 Garden Street, Kumasi"></div>
        <div class="form-group"><label class="form-label">Sacraments</label>
          <div style="display:flex;gap:16px;margin-top:6px;">
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;"><input type="checkbox"
                name="sacraments[]" value="baptised" id="editBaptised" checked> Baptised</label>
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;"><input type="checkbox"
                name="sacraments[]" value="confirmed" id="editConfirmed" checked> Confirmed</label>
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;"><input type="checkbox"
                name="sacraments[]" value="communion" id="editCommunion"> First Communion</label>
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;"><input type="checkbox"
                name="sacraments[]" value="matrimony" id="editMatrimony"> Matrimony</label>
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;"><input type="checkbox"
                name="sacraments[]" value="orders" id="editOrders"> Orders</label>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('editMemberModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>