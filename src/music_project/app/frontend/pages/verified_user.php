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
  <title>PulseRoom | Verified User</title>

  <link rel="stylesheet" href="<?= $assetBase; ?>/css/main.css">
  <link rel="stylesheet" href="<?= $assetBase; ?>/css/navbar.css">
  <link rel="stylesheet" href="<?= $assetBase; ?>/css/footer.css">
  <link rel="stylesheet" href="<?= $assetBase; ?>/css/verified_user.css">
</head>
<body>

  <?php include __DIR__ . '/../partials/navbar.php'; ?>

  <main class="verified-page">
    <section class="verified-card">
      <div class="verified-icon-wrap">
        <div class="verified-icon">✓</div>
      </div>

      <span class="verified-tag">Account Verification</span>
      <h1 class="verified-title" id="verificationTitle">Verifying Your Account</h1>
      <p class="verified-text" id="verificationMessage">We are confirming your email address now.</p>

      <div class="verified-actions">
        <a href="/login" class="btn btn-primary">Go to Login</a>
        <a href="/" class="btn btn-secondary">Back to Home</a>
      </div>
    </section>
  </main>

  <?php include __DIR__ . '/../partials/footer.php'; ?>

  <script src="<?= $assetBase; ?>/js/app.js"></script>
  <script src="<?= $assetBase; ?>/js/verified_user.js"></script>
</body>
</html>
