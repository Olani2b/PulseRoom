<?php
$assetBase = '/frontend';
$currentUserRole = 'guest';
$currentPage = 'register';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PulseRoom | Register</title>

  <link rel="stylesheet" href="<?= $assetBase; ?>/css/main.css">
  <link rel="stylesheet" href="<?= $assetBase; ?>/css/navbar.css">
  <link rel="stylesheet" href="<?= $assetBase; ?>/css/footer.css">
  <link rel="stylesheet" href="<?= $assetBase; ?>/css/register.css">
  <link rel="stylesheet" href="<?= $assetBase; ?>/css/toast.css">
</head>
<body>

  <?php include __DIR__ . '/../partials/navbar.php'; ?>

  <main class="register-page">
    <section class="register-wrapper">
      <div class="register-card">
        <div class="register-header">
          <span class="register-tag">Create Account</span>
          <h1>Register</h1>
          <p>Create your account to access the protected platform features.</p>
        </div>

        <div id="registerMessage" class="register-message" style="display: none;"></div>

        <form id="registerForm" class="register-form" method="POST" novalidate>
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

          <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="Choose a username" required>
          </div>

          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="Enter your email" required>
          </div>

          <div class="form-group">
            <label for="password">Password</label>
            <div class="password-box">
              <input type="password" id="password" name="password" placeholder="Create a password" required>
              <button type="button" id="togglePassword" class="toggle-password">Show</button>
            </div>
            <div class="strength-wrap">
              <div class="strength-bar">
                <div id="strengthFill" class="strength-fill"></div>
              </div>
              <p id="strengthText" class="strength-text">Use 8+ characters with uppercase, lowercase, and a number. Avoid common words.</p>
            </div>
          </div>

          <div class="form-group">
            <label for="conf_password">Confirm Password</label>
            <div class="password-box">
              <input type="password" id="conf_password" name="conf_password" placeholder="Confirm your password" required>
              <button type="button" id="toggleConfirmPassword" class="toggle-password">Show</button>
            </div>
          </div>

          <p id="passwordMessage" class="password-message"></p>

          <div class="register-actions">
            <button type="submit" class="btn btn-primary" id="registerSubmitBtn">Register</button>
          </div>
        </form>

        <div class="register-footer-text">
          <p>Already have an account? <a href="/login">Login here</a></p>
        </div>
      </div>
    </section>
  </main>

  <?php include __DIR__ . '/../partials/footer.php'; ?>

  <div id="toast" class="toast"></div>

  <script src="<?= $assetBase; ?>/js/app.js"></script>
  <script src="<?= $assetBase; ?>/js/register.js"></script>
</body>
</html>
