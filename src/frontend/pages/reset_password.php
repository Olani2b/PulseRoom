<?php
$assetBase = '/frontend';
$currentUserRole = 'guest';
$currentPage = 'login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PulseRoom | Reset Password</title>

  <link rel="stylesheet" href="<?= $assetBase; ?>/css/main.css">
  <link rel="stylesheet" href="<?= $assetBase; ?>/css/navbar.css">
  <link rel="stylesheet" href="<?= $assetBase; ?>/css/footer.css">
  <link rel="stylesheet" href="<?= $assetBase; ?>/css/reset_password.css">
  <link rel="stylesheet" href="<?= $assetBase; ?>/css/toast.css">
</head>
<body>

  <?php include __DIR__ . '/../partials/navbar.php'; ?>

  <main class="reset-page">
    <section class="reset-wrapper">
      <div class="reset-card">
        <div class="reset-header">
          <span class="reset-tag">New Password</span>
          <h1>Reset Password</h1>
          <p>Choose a new password to restore access to your account.</p>
        </div>

        <form id="resetForm" class="reset-form" method="POST" novalidate>
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="email" value="">
          <input type="hidden" name="token" value="">

          <div class="form-group">
            <label for="new_password">New Password</label>
            <div class="password-box">
              <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>
              <button type="button" id="toggleNewPassword" class="toggle-password">Show</button>
            </div>
            <div class="strength-wrap">
              <div class="strength-bar">
                <div id="resetStrengthFill" class="strength-fill"></div>
              </div>
              <p id="resetStrengthText" class="strength-text">Use 8+ characters with uppercase, lowercase, a number, and a special character.</p>
            </div>
          </div>

          <div class="form-group">
            <label for="conf_new_password">Confirm Password</label>
            <div class="password-box">
              <input type="password" id="conf_new_password" name="conf_new_password" placeholder="Confirm new password" required>
              <button type="button" id="toggleConfirmNewPassword" class="toggle-password">Show</button>
            </div>
          </div>

          <p id="resetPasswordMessage" class="password-message"></p>

          <div class="reset-actions">
            <button type="submit" class="btn btn-primary">Reset Password</button>
          </div>
        </form>

        <div class="reset-footer-text">
          <p><a href="/login">Back to login</a></p>
        </div>
      </div>
    </section>
  </main>

  <?php include __DIR__ . '/../partials/footer.php'; ?>

  <div id="toast" class="toast"></div>

  <script src="<?= $assetBase; ?>/js/app.js"></script>
  <script src="<?= $assetBase; ?>/js/reset_password.js"></script>
</body>
</html>
