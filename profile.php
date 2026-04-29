<?php
/**
 * User Profile & Settings Page
 */
require_once 'includes/auth.php';
requireAuth();

$pageTitle = 'My Profile';
$activePage = 'profile';


// Mock data (Backend team will replace this with logged-in user session data)
$user_data = $currentUser ?? [
  'id' => 'USR-001',
  'first_name' => 'Elder',
  'last_name' => 'Asante',
  'email' => 'asante@ccrhog.org',
  'phone' => '0244-123-456',
  'role' => 'Administrator',
  'initials' => 'EA',
  'photo_url' => null
];

// Ensure sidebar uses the same data
$currentUser = $user_data;
$currentUser['name'] = $user_data['first_name'] . ' ' . $user_data['last_name'];
?>
<!DOCTYPE html>
<html lang="en">

<?php require_once 'includes/head.php'; ?>

<body>

  <?php require_once 'includes/sidebar.php'; ?>

  <!-- MAIN CONTENT -->
  <main id="main">

    <div id="page-profile" class="page">
      <div class="topbar">
        <div style="display:flex;align-items:center;">
          <button class="mobile-toggle" onclick="toggleSidebar()">
            <i class="ph ph-list"></i>
          </button>
          <div class="topbar-title">My Profile</div>
        </div>
        <div class="topbar-actions">
          <span style="font-size: 13px; color: var(--muted);">Last login: Today, 8:45 AM</span>
        </div>
      </div>

      <div class="content">
        <div class="grid-2" style="gap:32px;">

          <!-- Personal Information Card -->
          <div class="card">
            <div class="profile-photo-section">
              <div class="photo-upload-container">
                <div class="photo-upload-circle" onclick="document.getElementById('profilePhoto').click()">
                  <img id="profilePhotoPreview" src="<?= $user_data['photo_url'] ?: '' ?>"
                    style="<?= $user_data['photo_url'] ? '' : 'display:none;' ?>">
                  <div class="photo-upload-overlay" id="profilePhotoPlaceholder"
                    style="<?= $user_data['photo_url'] ? 'display:none;' : '' ?>">
                    <i class="ph ph-camera"></i>
                  </div>
                </div>
                <label class="photo-upload-label" onclick="document.getElementById('profilePhoto').click()">Upload
                  Profile Photo</label>
                <input type="file" id="profilePhoto" name="profile_photo" hidden accept="image/*"
                  onchange="handlePreview(this, 'profilePhotoPreview', 'profilePhotoPlaceholder')">
              </div>
              <h2 id="profileDisplayName">
                <?= htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']) ?>
              </h2>
              <p id="profileDisplayRole"><?= htmlspecialchars($user_data['role']) ?></p>
            </div>

            <div class="card-body">
              <form action="" method="POST" id="profileUpdateForm">
                <div class="grid-2" style="gap:16px;">
                  <div class="form-group">
                    <label class="form-label">First Name</label>
                    <input class="form-control" name="first_name"
                      value="<?= htmlspecialchars($user_data['first_name']) ?>">
                  </div>
                  <div class="form-group">
                    <label class="form-label">Last Name</label>
                    <input class="form-control" name="last_name"
                      value="<?= htmlspecialchars($user_data['last_name']) ?>">
                  </div>
                </div>

                <div class="form-group">
                  <label class="form-label">Email Address</label>
                  <input class="form-control" name="email" type="email"
                    value="<?= htmlspecialchars($user_data['email']) ?>">
                </div>

                <div class="form-group">
                  <label class="form-label">Phone Number</label>
                  <input class="form-control" name="phone" value="<?= htmlspecialchars($user_data['phone']) ?>">
                </div>

                <div style="margin-top:24px;">
                  <button type="submit" class="btn btn-primary" style="width:100%;">Update Information</button>
                </div>
              </form>
            </div>
          </div>

          <!-- Security & Access Card -->
          <div class="card security-card">
            <div class="card-header">
              <h3>Security & Password</h3>
            </div>
            <div class="card-body">
              <p style="font-size:13px; color:var(--muted); margin-bottom:20px;">Change your password to keep your
                account secure.</p>

              <form action="" method="POST" id="passwordUpdateForm">
                <div class="form-group">
                  <label class="form-label">Current Password</label>
                  <div class="password-wrap">
                    <input class="form-control" type="password" name="current_password" placeholder="••••••••">
                    <button type="button" class="password-toggle" onclick="togglePassword(this)">
                      <i class="ph ph-eye"></i>
                    </button>
                  </div>
                </div>

                <div style="height:1px; background:#EDE8DF; margin:24px 0;"></div>

                <div class="form-group">
                  <label class="form-label">New Password</label>
                  <div class="password-wrap">
                    <input class="form-control" type="password" name="new_password" placeholder="••••••••">
                    <button type="button" class="password-toggle" onclick="togglePassword(this)">
                      <i class="ph ph-eye"></i>
                    </button>
                  </div>
                </div>

                <div class="form-group">
                  <label class="form-label">Confirm New Password</label>
                  <div class="password-wrap">
                    <input class="form-control" type="password" name="confirm_password" placeholder="••••••••">
                    <button type="button" class="password-toggle" onclick="togglePassword(this)">
                      <i class="ph ph-eye"></i>
                    </button>
                  </div>
                  <span class="form-help">Must be at least 8 characters long.</span>
                </div>

                <div style="margin-top:32px;">
                  <button type="submit" class="btn btn-outline"
                    style="width:100%; border-color:var(--deep); color:var(--deep);">Update Password</button>
                </div>
              </form>

              <div
                style="margin-top:40px; padding:20px; background:#FEF3C7; border-radius:12px; border:1px dashed #F59E0B;">
                <h4
                  style="font-size:13px; font-weight:700; color:#92400E; margin-bottom:6px; display:flex; align-items:center; gap:8px;">
                  <i class="ph ph-warning" style="font-size: 18px; color: #92400E;"></i>
                  Two-Factor Authentication
                </h4>
                <p style="font-size:12px; color:#B45309; margin-bottom:12px;">Add an extra layer of security to your
                  account by enabling 2FA.</p>
                <button class="btn btn-sm" style="background:white; color:#92400E; border:1px solid #F59E0B;">Enable
                  2FA</button>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

  </main>

  <script src="assets/js/main.js"></script>
  <script>
    // Handle photo preview
    function handlePreview(input, previewId, placeholderId) {
      if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
          const preview = document.getElementById(previewId);
          preview.src = e.target.result;
          preview.style.display = 'block';
          if (placeholderId) document.getElementById(placeholderId).style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
      }
    }

    // Live name update
    const fnInput = document.querySelector('[name="first_name"]');
    const lnInput = document.querySelector('[name="last_name"]');
    const displayNames = [document.getElementById('profileDisplayName'), document.querySelector('.user-info p')];
    
    function updateDisplayName() {
      const name = (fnInput.value + ' ' + lnInput.value).trim();
      displayNames.forEach(el => {
        if (el) el.textContent = name || 'User Name';
      });
    }

    fnInput.addEventListener('input', updateDisplayName);
    lnInput.addEventListener('input', updateDisplayName);

    // Toggle password visibility
    function togglePassword(btn) {
      const wrap = btn.parentElement;
      const input = wrap.querySelector('input');
      const icon = btn.querySelector('i');
      
      const type = input.type === 'password' ? 'text' : 'password';
      input.type = type;
      
      icon.className = type === 'password' ? 'ph ph-eye' : 'ph ph-eye-slash';
    }

    // Simple validation feedback for password
    document.getElementById('passwordUpdateForm').addEventListener('submit', function (e) {
      const newPass = this.querySelector('[name="new_password"]').value;
      const confPass = this.querySelector('[name="confirm_password"]').value;

      if (newPass && newPass !== confPass) {
        e.preventDefault();
        alert('New passwords do not match!');
      }
    });
  </script>
</body>

</html>