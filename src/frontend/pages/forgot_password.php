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
  <title>PulseRoom | Forgot Password</title>

  <link rel="stylesheet" href="<?= $assetBase; ?>/css/main.css">
  <link rel="stylesheet" href="<?= $assetBase; ?>/css/navbar.css">
  <link rel="stylesheet" href="<?= $assetBase; ?>/css/footer.css">
  <link rel="stylesheet" href="<?= $assetBase; ?>/css/forgot_password.css">
  <link rel="stylesheet" href="<?= $assetBase; ?>/css/toast.css">
</head>
<body>

  <?php include __DIR__ . '/../partials/navbar.php'; ?>

  <main class="forgot-page">
    <section class="forgot-wrapper">
      <div class="forgot-card">
        <div class="forgot-header">
          <span class="forgot-tag">Account Recovery</span>
          <h1>Forgot Password</h1>
          <p>Enter your email address and we’ll send you a reset link.</p>
        </div>

        <form id="forgotForm" class="forgot-form" method="POST" novalidate>
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="Enter your email address" required>
          </div>

          <div class="forgot-actions">
            <button type="submit" class="btn btn-primary" id="forgotSubmitBtn">Send Reset Request</button>
          </div>
        </form>

        <div class="forgot-footer-text">
          <p>Remembered your password? <a href="/login">Back to login</a></p>
        </div>
      </div>
    </section>
  </main>

  <?php include __DIR__ . '/../partials/footer.php'; ?>

  <div id="toast" class="toast"></div>

  <script src="<?= $assetBase; ?>/js/app.js"></script>
  <script src="<?= $assetBase; ?>/js/forgot_password.js"></script>
</body>
</html>
