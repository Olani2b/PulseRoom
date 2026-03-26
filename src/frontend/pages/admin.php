<?php
$assetBase = '/frontend';
$currentUserRole = $_SESSION['role'] ?? 'guest';
$currentPage = 'admin';
$username = $_SESSION['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PulseRoom | Admin Panel</title>

  <link rel="stylesheet" href="<?= $assetBase; ?>/css/main.css">
  <link rel="stylesheet" href="<?= $assetBase; ?>/css/navbar.css">
  <link rel="stylesheet" href="<?= $assetBase; ?>/css/footer.css">
  <link rel="stylesheet" href="<?= $assetBase; ?>/css/admin.css">
  <link rel="stylesheet" href="<?= $assetBase; ?>/css/toast.css">
</head>
<body>

  <?php include __DIR__ . '/../partials/navbar.php'; ?>

  <main class="admin-page">
    <section class="admin-header">
      <div>
        <span class="admin-tag">Administrator Area</span>
        <h1>Admin Panel</h1>
        <p>Manage user access levels and platform privileges.</p>
      </div>

      <div class="admin-badge">
        <span class="admin-name"><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="admin-role">Administrator</span>
      </div>
    </section>

    <section class="admin-table-section">
      <div class="table-card">
        <div class="table-header">
          <h2>User Privilege Management</h2>
          <p>Switch users between free and premium access.</p>
        </div>

        <div class="table-wrapper">
          <table class="users-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Current Access</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="usersTableBody"></tbody>
          </table>
        </div>
        <div class="empty-state hidden" id="adminEmptyState">No user records were found.</div>
        <div class="pager">
          <button class="btn btn-secondary pager-btn" id="adminPrevBtn" type="button">Previous</button>
          <span class="pager-label" id="adminPageLabel">Page 1</span>
          <button class="btn btn-secondary pager-btn" id="adminNextBtn" type="button">Next</button>
        </div>
      </div>
    </section>
  </main>

  <input type="hidden" id="adminCsrfToken" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
  <div id="toast" class="toast"></div>

  <?php include __DIR__ . '/../partials/footer.php'; ?>

  <script src="<?= $assetBase; ?>/js/app.js"></script>
  <script src="<?= $assetBase; ?>/js/admin.js"></script>
</body>
</html>
