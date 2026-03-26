<?php
$assetBase = '/frontend';
$currentUserRole = $_SESSION['role'] ?? 'guest';
$currentPage = 'logout';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PulseRoom | Logout</title>

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
      <div class="login-card logout-card">
        <div class="login-header">
          <span class="login-tag">Session</span>
          <h1>Logout</h1>
          <p id="logoutMessage">End your current session securely and return to the public homepage.</p>
        </div>

        <form id="logoutForm" class="login-form">
          <input type="hidden" id="logoutCsrf" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
          <div class="login-actions">
            <button type="submit" class="btn btn-primary" id="logoutSubmitBtn">Logout</button>
            <a href="/dashboard" class="btn btn-secondary">Back</a>
          </div>
        </form>

        <div id="logoutSuccess" class="logout-success hidden">
          <p class="logout-countdown">You have been logged out. Redirecting in <span id="logoutCountdown">3</span> seconds.</p>
          <a href="/" class="btn btn-primary">Go Home</a>
        </div>
      </div>
    </section>
  </main>

  <div id="toast" class="toast"></div>

  <?php include __DIR__ . '/../partials/footer.php'; ?>

  <script src="<?= $assetBase; ?>/js/app.js"></script>
  <script src="<?= $assetBase; ?>/js/logout.js"></script>
</body>
</html>
