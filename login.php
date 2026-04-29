<?php
/**
 * Login Page
 * Stunning, premium login interface for HOG-CCR
 */

require_once 'includes/auth.php';

// If already logged in, redirect to dashboard
if (isAuthenticated()) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Hardcoded credentials for initial implementation
    // Backend team can replace this with database verification
    if ($username === 'admin' && $password === 'password123') {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_data'] = [
            'id' => 1,
            'name' => 'Elder Asante',
            'initials' => 'EA',
            'role' => 'Administrator',
            'email' => 'admin@hogccr.org'
        ];
        header('Location: index.php');
        exit();
    } else {
        $error = 'Invalid username or password. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | House of Grace CCR</title>
  <link rel="icon" type="image/png" href="assets/images/logo.png">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --gold: #DC261A;
      --gold-light: #EF4444;
      --gold-pale: #FEF2F2;
      --deep: #2E2D7B;
      --deep2: #1E1B4B;
      --muted: #64748B;
      --mid: #475569;
      --white: #FFFFFF;
    }

    body {
      background: radial-gradient(circle at top left, var(--deep2), var(--deep));
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      margin: 0;
    }

    .login-container {
      width: 100%;
      max-width: 420px;
      perspective: 1000px;
    }

    .login-card {
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(10px);
      border-radius: 24px;
      padding: 48px 40px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
      border: 1px solid rgba(255, 255, 255, 0.2);
      animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes slideUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .login-header {
      text-align: center;
      margin-bottom: 36px;
    }

    .login-logo {
      width: 80px;
      height: 80px;
      margin-bottom: 16px;
      filter: drop-shadow(0 4px 10px rgba(0,0,0,0.1));
    }

    .login-header h1 {
      font-size: 32px;
      color: var(--deep2);
      margin-bottom: 8px;
    }

    .login-header p {
      color: var(--muted);
      font-size: 14px;
    }

    .form-group {
      margin-bottom: 24px;
      position: relative;
    }

    .form-label {
      font-size: 13px;
      font-weight: 600;
      color: var(--deep2);
      margin-bottom: 8px;
      display: block;
    }

    /* Flex-based input wrapper: icon | input | toggle — all contained */
    .input-wrapper {
      display: flex;
      align-items: center;
      border: 2px solid #E2E8F0;
      background: #ffffff;
      border-radius: 12px;
      transition: border-color 0.3s;
      overflow: hidden;
      padding: 0 4px 0 0;
    }

    .input-wrapper:focus-within {
      border-color: var(--gold);
      box-shadow: 0 0 0 3px rgba(220, 38, 26, 0.08);
    }

    .input-wrapper .field-icon {
      flex-shrink: 0;
      padding: 0 12px 0 16px;
      color: var(--muted);
      font-size: 18px;
      pointer-events: none;
      transition: color 0.3s;
    }

    .input-wrapper:focus-within .field-icon {
      color: var(--gold);
    }

    .input-wrapper .form-control {
      flex: 1;
      min-width: 0;
      border: none !important;
      background: transparent !important;
      box-shadow: none !important;
      padding: 0 !important;
      height: 52px;
      font-size: 15px;
      outline: none !important;
    }

    .input-wrapper .form-control:focus {
      border: none !important;
      box-shadow: none !important;
    }

    .error-msg {
      background: #FEF2F2;
      color: #DC2626;
      padding: 12px 16px;
      border-radius: 12px;
      font-size: 13px;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 10px;
      border-left: 4px solid #DC2626;
      animation: shake 0.4s ease-in-out;
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-5px); }
      75% { transform: translateX(5px); }
    }

    .btn-login {
      width: 100%;
      height: 52px;
      font-size: 16px;
      border-radius: 12px;
      margin-top: 10px;
      padding: 10px 20px;
      font-family: 'DM Sans', sans-serif;
      font-weight: 600;
      cursor: pointer;
      border: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      letter-spacing: 0.2px;
      background: var(--gold);
      color: #ffffff;
      box-shadow: 0 2px 4px rgba(220, 38, 26, 0.15);
      transition: all 0.2s ease;
    }

    .btn-login:hover:not(:disabled) {
      background: var(--gold-light);
      box-shadow: 0 4px 12px rgba(220, 38, 26, 0.25);
      transform: translateY(-1px);
    }

    .login-footer {
      text-align: center;
      margin-top: 32px;
      font-size: 13px;
      color: var(--muted);
    }

    .login-footer a {
      color: var(--gold);
      font-weight: 600;
      transition: opacity 0.2s;
    }

    .login-footer a:hover {
      opacity: 0.8;
    }

    .remember-forgot {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 24px;
      font-size: 13px;
    }

    .checkbox-wrap {
      display: flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      color: var(--muted);
    }

    .checkbox-wrap input {
      accent-color: var(--gold);
    }

    /* Password Toggle — flex child, always inside wrapper */
    .password-toggle {
      flex-shrink: 0;
      background: none;
      border: none;
      color: var(--muted);
      cursor: pointer;
      padding: 8px 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: color 0.2s;
      border-radius: 8px;
    }

    .password-toggle:hover {
      color: var(--gold);
    }

    /* Loading State */
    .btn-login {
      position: relative;
      overflow: hidden;
    }

    .btn-login.loading span,
    .btn-login.loading i.ph-arrow-right {
      opacity: 0;
    }

    .spinner {
      display: none;
      position: absolute;
      left: 50%;
      top: 50%;
      transform: translate(-50%, -50%);
      width: 24px;
      height: 24px;
      border: 3px solid rgba(255, 255, 255, 0.3);
      border-radius: 50%;
      border-top-color: #fff;
      animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
      to { transform: translate(-50%, -50%) rotate(360deg); }
    }

    .btn-login.loading .spinner {
      display: block;
    }

    .btn-login:disabled {
      opacity: 0.7;
      cursor: not-allowed;
    }
  </style>
</head>
<body>

  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <img src="assets/images/logo.png" alt="Logo" class="login-logo">
        <h1>Welcome Back</h1>
        <p>Sign in to HOG-CCR Management System</p>
      </div>

      <?php if ($error): ?>
        <div class="error-msg">
          <i class="ph ph-warning-circle"></i>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="login.php" id="loginForm">
        <div class="form-group">
          <label class="form-label">Username</label>
          <div class="input-wrapper">
            <i class="ph ph-user field-icon"></i>
            <input type="text" name="username" class="form-control" placeholder="Enter username" required autofocus>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Password</label>
          <div class="input-wrapper">
            <i class="ph ph-lock field-icon"></i>
            <input type="password" name="password" id="passwordInput" class="form-control" placeholder="Enter password" required>
            <button type="button" class="password-toggle" onclick="togglePassword()" aria-label="Toggle password visibility">
              <i class="ph ph-eye" id="toggleIcon"></i>
            </button>
          </div>
        </div>

        <div class="remember-forgot">
          <label class="checkbox-wrap">
            <input type="checkbox" name="remember"> Remember me
          </label>
          <a href="#">Forgot password?</a>
        </div>

        <button type="submit" class="btn btn-primary btn-login" id="submitBtn">
          <span>Sign In</span>
          <i class="ph ph-arrow-right"></i>
          <div class="spinner"></div>
        </button>
      </form>

      <div class="login-footer">
        &copy; <?= date('Y') ?> House of Grace Church. All rights reserved.
      </div>
    </div>
  </div>

  <script>
    function togglePassword() {
      const input = document.getElementById('passwordInput');
      const icon = document.getElementById('toggleIcon');
      
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('ph-eye');
        icon.classList.add('ph-eye-slash');
      } else {
        input.type = 'password';
        icon.classList.remove('ph-eye-slash');
        icon.classList.add('ph-eye');
      }
    }

    document.getElementById('loginForm').addEventListener('submit', function(e) {
      const btn = document.getElementById('submitBtn');
      btn.classList.add('loading');
      btn.disabled = true;
      
      // In a real app, the form would submit here. 
      // The loading state will stay until the page reloads or redirects.
    });
  </script>

</body>
</html>
