<?php $currentUserRole = 'guest'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Page Not Found | PulseRoom</title>

  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/navbar.css">
  <link rel="stylesheet" href="../css/footer.css">
  <link rel="stylesheet" href="../css/404.css">
</head>
<body>

  <?php include __DIR__ . '/../partials/navbar.php'; ?>

  <main class="error-page">
    <div class="error-container">

      <h1 class="error-code">404</h1>

      <h2 class="error-title">Page Not Found</h2>

      <p class="error-message">
        The page you are looking for does not exist or has been moved.
      </p>

      <div class="error-actions">
        <a href="index.php" class="btn btn-primary">Go Home</a>
        <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
      </div>

    </div>
  </main>

  <?php include __DIR__ . '/../partials/footer.php'; ?>

</body>
</html>