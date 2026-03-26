<?php
$currentUserRole = $currentUserRole ?? ($_SESSION['role'] ?? 'guest');
$currentPage = $currentPage ?? '';
$currentUsername = $_SESSION['username'] ?? '';
$planLabel = $currentUserRole === 'admin'
    ? 'Admin'
    : ($currentUserRole === 'pro' ? 'Pro Plan' : 'Free Plan');

function navIsActive($page, $currentPage) {
    return $page === $currentPage ? 'active' : '';
}
?>

<nav class="navbar">

  <div class="navbar-left">
    <a href="/" class="brand">PulseRoom</a>
  </div>

  <button class="menu-toggle" id="menuToggle">☰</button>

  <div class="navbar-right" id="navLinks">

    <?php if ($currentUserRole === 'guest'): ?>
      <a href="/" class="<?= navIsActive('home', $currentPage); ?>">Home</a>
      <a href="/login" class="<?= navIsActive('login', $currentPage); ?>">Login</a>
      <a href="/register" class="<?= navIsActive('register', $currentPage); ?>">Register</a>

    <?php elseif ($currentUserRole !== 'guest' && $currentUserRole !== 'admin'): ?>
      <span class="nav-chip nav-user"><?= htmlspecialchars($currentUsername, ENT_QUOTES, 'UTF-8'); ?></span>
      <span class="nav-chip nav-plan"><?= $planLabel; ?></span>
      <a href="/dashboard" class="<?= navIsActive('dashboard', $currentPage); ?>">Dashboard</a>
      <a href="/upload" class="<?= navIsActive('upload', $currentPage); ?>">Upload</a>
      <a href="/logout" class="<?= navIsActive('logout', $currentPage); ?>">Logout</a>

    <?php elseif ($currentUserRole === 'admin'): ?>
      <span class="nav-chip nav-user"><?= htmlspecialchars($currentUsername, ENT_QUOTES, 'UTF-8'); ?></span>
      <span class="nav-chip nav-plan"><?= $planLabel; ?></span>
      <a href="/dashboard" class="<?= navIsActive('dashboard', $currentPage); ?>">Dashboard</a>
      <a href="/upload" class="<?= navIsActive('upload', $currentPage); ?>">Upload</a>
      <a href="/admin" class="<?= navIsActive('admin', $currentPage); ?>">Admin</a>
      <a href="/logout" class="<?= navIsActive('logout', $currentPage); ?>">Logout</a>

    <?php endif; ?>

  </div>

</nav>
