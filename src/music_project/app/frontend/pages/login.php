<?php
$assetBase = '/music_project/app/frontend';
$currentUserRole = 'guest';
$currentPage = 'login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PulseRoom | Login</title>

  <link rel="stylesheet" href="<?= $assetBase; ?>/css/main.css">
  <link rel="stylesheet" href="<?= $assetBase; ?>/css/navbar.css">
  <link rel="stylesheet" href="<?= $assetBase; ?>/css/footer.css">
  <link rel="stylesheet" href="<?= $assetBase; ?>/css/login.css">
  <link rel="stylesheet" href="<?= $assetBase; ?>/css/toast.css">
</head>
<body>

  <?php include __DIR__ . '/../partials/navbar.php'; ?>

  <main class="login-page">
    <section class="login-wrapper">
      <div class="login-card">
        <div class="login-header">
          <span class="login-tag">Account Access</span>
          <h1>Login</h1>
          <p>Enter your username and password to continue to the platform.</p>
        </div>

        <?php if (isset($_GET['verified']) && $_GET['verified'] == 1): ?>
          <div class="auth-message auth-success">
            Your account has been verified successfully. You can now log in.
          </div>
        <?php endif; ?>

        <form id="loginForm" class="login-form" novalidate>
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

          <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="Enter your username" required>
          </div>

          <div class="form-group">
            <label for="password">Password</label>
            <div class="password-box">
              <input type="password" id="password" name="password" placeholder="Enter your password" required>
              <button type="button" id="togglePassword" class="toggle-password">Show</button>
            </div>
          </div>

          <div class="login-actions">
            <button type="submit" class="btn btn-primary" id="loginSubmitBtn">Login</button>
            <a href="/forgot_password" class="forgot-link">Forgot password?</a>
          </div>
        </form>

        <div class="login-footer-text">
          <p>Don’t have an account? <a href="/register">Register here</a></p>
        </div>
      </div>
    </section>
  </main>

  <?php include __DIR__ . '/../partials/footer.php'; ?>

  <div id="toast" class="toast"></div>

  <script src="<?= $assetBase; ?>/js/app.js"></script>
  <script src="<?= $assetBase; ?>/js/login.js"></script>
</body>
</html>
