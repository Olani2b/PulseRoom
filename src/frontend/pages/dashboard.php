<?php
$assetBase = '/frontend';
$currentUserRole = $_SESSION['role'] ?? 'guest';
$currentPage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PulseRoom | Dashboard</title>

  <link rel="stylesheet" href="<?= $assetBase; ?>/css/main.css">
  <link rel="stylesheet" href="<?= $assetBase; ?>/css/navbar.css">
  <link rel="stylesheet" href="<?= $assetBase; ?>/css/footer.css">
  <link rel="stylesheet" href="<?= $assetBase; ?>/css/dashboard.css">
  <link rel="stylesheet" href="<?= $assetBase; ?>/css/toast.css">
</head>
<body>

  <?php include __DIR__ . '/../partials/navbar.php'; ?>

  <main class="dashboard-page">
    <section class="catalogue-shell">
      <div class="catalogue-head">
        <h1>Catalogue</h1>
      </div>

      <section class="dashboard-controls">
        <div class="filter-group">
          <span class="filter-label">Filter:</span>
          <button class="filter-btn active" data-file-type="both" type="button">Latest</button>
          <button class="filter-btn filter-btn-icon" data-file-type="mp3" type="button">MP3</button>
          <button class="filter-btn filter-btn-icon" data-file-type="txt" type="button">Lyrics</button>
        </div>
      </section>

      <section class="media-section">
        <div id="mediaGrid" class="media-grid"></div>
        <div class="empty-state hidden" id="dashboardEmptyState">No uploads were found for this filter.</div>
        <div class="pager">
          <button class="btn btn-secondary pager-btn" id="prevPageBtn" type="button">Previous</button>
          <span class="pager-label" id="pageLabel">Page 1</span>
          <button class="btn btn-secondary pager-btn" id="nextPageBtn" type="button">Next</button>
        </div>
      </section>
    </section>
  </main>

  <div id="lyricsModal" class="modal hidden">
    <div class="modal-content lyrics-book-modal">
      <button id="closeModal" class="close-modal" type="button">×</button>
      <div class="lyrics-book">
        <div class="lyrics-book-spine" aria-hidden="true"></div>
        <div class="lyrics-book-page">
          <div class="lyrics-book-header">
            <span class="lyrics-book-tag">Lyrics Book</span>
            <h2 id="modalTitle">Lyrics Title</h2>
            <p id="modalSubtitle" class="modal-subtitle">By Artist</p>
          </div>
          <div id="modalLyrics" class="modal-lyrics"></div>
        </div>
      </div>
    </div>
  </div>

  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
  <div id="toast" class="toast"></div>

  <?php include __DIR__ . '/../partials/footer.php'; ?>

  <script src="<?= $assetBase; ?>/js/app.js"></script>
  <script src="<?= $assetBase; ?>/js/dashboard.js"></script>
</body>
</html>
