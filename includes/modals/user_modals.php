<!-- Add User Modal -->
<div class="modal-overlay" id="addUserModal">
  <div class="modal" style="max-width:500px;">
    <div class="modal-header">
      <h3>Add New Administrator</h3>
      <button class="close-btn" onclick="closeModal('addUserModal')"><i class="ph ph-x"></i></button>
    </div>
    <form action="handlers/user_handler.php" method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="add_user">
      <div class="modal-body">
        <div class="grid-2" style="gap:16px;">
          <div class="form-group">
            <label class="form-label">Full Name</label>
            <input class="form-control" name="name" placeholder="e.g. John Doe" required>
          </div>
          <div class="form-group">
            <label class="form-label">Initials</label>
            <input class="form-control" name="initials" placeholder="e.g. JD" maxlength="5" required>
          </div>
        </div>

        <div class="grid-2" style="gap:16px;">
          <div class="form-group">
            <label class="form-label">Username</label>
            <input class="form-control" name="username" placeholder="johndoe" required>
          </div>
          <div class="form-group">
            <label class="form-label">Role</label>
            <select class="form-control" name="role" required>
              <?php foreach ($roles as $r): ?>
                <option value="<?= htmlspecialchars($r['name']) ?>"><?= htmlspecialchars($r['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="grid-2" style="gap:16px;">
          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" class="form-control" name="email" placeholder="john@example.com" required>
          </div>
          <div class="form-group">
            <label class="form-label">Phone Number</label>
            <input type="text" class="form-control" name="phone" placeholder="e.g. 0541234567">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Initial Password</label>
          <input type="password" class="form-control" name="password" placeholder="Min 6 characters" required minlength="6">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addUserModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Account</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit User Modal -->
<div class="modal-overlay" id="editUserModal">
  <div class="modal" style="max-width:500px;">
    <div class="modal-header">
      <h3>Edit Administrator</h3>
      <button class="close-btn" onclick="closeModal('editUserModal')"><i class="ph ph-x"></i></button>
    </div>
    <form action="handlers/user_handler.php" method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="edit_user">
      <input type="hidden" name="user_id" id="edit_userId">
      
      <div class="modal-body">
        <div class="grid-2" style="gap:16px;">
          <div class="form-group">
            <label class="form-label">Full Name</label>
            <input class="form-control" name="name" id="edit_name" required>
          </div>
          <div class="form-group">
            <label class="form-label">Initials</label>
            <input class="form-control" name="initials" id="edit_initials" maxlength="5" required>
          </div>
        </div>

        <div class="grid-2" style="gap:16px;">
          <div class="form-group">
            <label class="form-label">Username</label>
            <input class="form-control" name="username" id="edit_username" required>
          </div>
          <div class="form-group">
            <label class="form-label">Role</label>
            <select class="form-control" name="role" id="edit_role" required>
              <?php foreach ($roles as $r): ?>
                <option value="<?= htmlspecialchars($r['name']) ?>"><?= htmlspecialchars($r['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="grid-2" style="gap:16px;">
          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" class="form-control" name="email" id="edit_email" required>
          </div>
          <div class="form-group">
            <label class="form-label">Phone Number</label>
            <input type="text" class="form-control" name="phone" id="edit_phone">
          </div>
        </div>

        <div class="form-group" style="background:var(--gold-pale); padding:12px; border-radius:8px; border:1px solid #FECACA; margin-top:8px;">
          <label class="form-label" style="color:#DC2626; margin-bottom:4px;">Change Password (Optional)</label>
          <input type="password" class="form-control" name="password" placeholder="Leave blank to keep current">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('editUserModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- Add Role Modal -->
<div class="modal-overlay" id="addRoleModal">
  <div class="modal" style="max-width:500px;">
    <div class="modal-header">
      <h3>Add New Role</h3>
      <button class="close-btn" onclick="closeModal('addRoleModal')"><i class="ph ph-x"></i></button>
    </div>
    <form action="handlers/user_handler.php" method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="add_role">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Role Name</label>
          <input class="form-control" name="name" placeholder="e.g. Media Manager" required>
        </div>
        
        <div class="form-group">
          <label class="form-label" style="margin-bottom: 12px; display: block;">Permissions</label>
          
          <label class="checkbox-wrap" style="margin-bottom: 8px; display: flex;">
            <input type="checkbox" name="perm_manage_users" value="1"> Manage Users & Roles
          </label>
          <label class="checkbox-wrap" style="margin-bottom: 8px; display: flex;">
            <input type="checkbox" name="perm_manage_finance" value="1"> Manage Finance
          </label>
          <label class="checkbox-wrap" style="margin-bottom: 8px; display: flex;">
            <input type="checkbox" name="perm_manage_welfare" value="1"> Manage Welfare
          </label>
          <label class="checkbox-wrap" style="margin-bottom: 8px; display: flex;">
            <input type="checkbox" name="perm_manage_members" value="1"> Manage Members
          </label>
          <label class="checkbox-wrap" style="margin-bottom: 8px; display: flex;">
            <input type="checkbox" name="perm_manage_events" value="1"> Manage Events
          </label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addRoleModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Role</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Role Modal -->
<div class="modal-overlay" id="editRoleModal">
  <div class="modal" style="max-width:500px;">
    <div class="modal-header">
      <h3>Edit Role</h3>
      <button class="close-btn" onclick="closeModal('editRoleModal')"><i class="ph ph-x"></i></button>
    </div>
    <form action="handlers/user_handler.php" method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="edit_role">
      <input type="hidden" name="role_id" id="edit_roleId">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Role Name</label>
          <input class="form-control" name="name" id="edit_role_name" required>
        </div>
        
        <div class="form-group">
          <label class="form-label" style="margin-bottom: 12px; display: block;">Permissions</label>
          
          <label class="checkbox-wrap" style="margin-bottom: 8px; display: flex;">
            <input type="checkbox" name="perm_manage_users" id="edit_perm_manage_users" value="1"> Manage Users & Roles
          </label>
          <label class="checkbox-wrap" style="margin-bottom: 8px; display: flex;">
            <input type="checkbox" name="perm_manage_finance" id="edit_perm_manage_finance" value="1"> Manage Finance
          </label>
          <label class="checkbox-wrap" style="margin-bottom: 8px; display: flex;">
            <input type="checkbox" name="perm_manage_welfare" id="edit_perm_manage_welfare" value="1"> Manage Welfare
          </label>
          <label class="checkbox-wrap" style="margin-bottom: 8px; display: flex;">
            <input type="checkbox" name="perm_manage_members" id="edit_perm_manage_members" value="1"> Manage Members
          </label>
          <label class="checkbox-wrap" style="margin-bottom: 8px; display: flex;">
            <input type="checkbox" name="perm_manage_events" id="edit_perm_manage_events" value="1"> Manage Events
          </label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('editRoleModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>
