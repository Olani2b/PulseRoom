<?php
$assetBase = '/frontend';
$currentUserRole = $_SESSION['role'] ?? 'guest';
$currentPage = 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PulseRoom | Home</title>

  <link rel="stylesheet" href="<?= $assetBase; ?>/css/main.css">
  <link rel="stylesheet" href="<?= $assetBase; ?>/css/navbar.css">
  <link rel="stylesheet" href="<?= $assetBase; ?>/css/footer.css">
  <link rel="stylesheet" href="<?= $assetBase; ?>/css/index.css">
</head>
<body>

  <?php include __DIR__ . '/../partials/navbar.php'; ?>

  <main>
    <section class="hero" id="home">
      <div class="hero-overlay"></div>
      <div class="hero-content">
        <span class="hero-tag">Secure platform for musicians</span>
        <h1>PulseRoom</h1>
        <p>
          A secure web platform where registered users can manage and access
          music-related content through protected pages and role-based functionality.
        </p>

        <div class="hero-buttons">
          <a href="<?= $currentUserRole === 'guest' ? '/login' : '/dashboard'; ?>" class="btn btn-primary">
            <?= $currentUserRole === 'guest' ? 'Login' : 'Open Dashboard'; ?>
          </a>
          <?php if ($currentUserRole === 'guest'): ?>
            <a href="/register" class="btn btn-secondary">Register</a>
          <?php else: ?>
            <a href="/upload" class="btn btn-secondary">Upload</a>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <section class="plans-section" id="access-levels">
      <div class="section-heading center">
        <span class="section-tag">Access Levels</span>
        <h2>Choose the access type that fits your account</h2>
        <p>
          PulseRoom supports both non-premium and premium users with controlled access levels.
        </p>
      </div>

      <div class="plans-grid">
        <article class="plan-card">
          <div class="plan-header free-header">Free</div>
          <div class="plan-body">
            <ul>
              <li>Access to non-premium content</li>
              <li>Standard user account privileges</li>
              <li>Protected access after login</li>
            </ul>
          </div>
        </article>

        <article class="plan-card premium-card">
          <div class="plan-header premium-header">Premium</div>
          <div class="plan-body">
            <ul>
              <li>Access to premium content</li>
              <li>Extended media availability</li>
              <li>Managed by administrator privileges</li>
            </ul>
          </div>
        </article>
      </div>
    </section>

    <section class="team-section" id="team">
      <div class="section-heading center">
        <span class="section-tag">Project Team</span>
        <h2>Built by</h2>
        <p>This platform was developed as part of the Web and Network Security course project.</p>
      </div>

      <div class="team-grid">
        <div class="team-card">
          <div class="team-avatar">O</div>
          <h3>Olani</h3>
        </div>

        <div class="team-card">
          <div class="team-avatar">M</div>
          <h3>Maryam Khalid</h3>
        </div>
      </div>
    </section>
  </main>

  <?php include __DIR__ . '/../partials/footer.php'; ?>

  <script src="<?= $assetBase; ?>/js/app.js"></script>
</body>
</html>
