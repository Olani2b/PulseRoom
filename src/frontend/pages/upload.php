<?php
$assetBase = '/frontend';
$currentUserRole = $_SESSION['role'] ?? 'guest';
$currentPage = 'upload';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PulseRoom | Upload</title>

  <link rel="stylesheet" href="<?= $assetBase; ?>/css/main.css">
  <link rel="stylesheet" href="<?= $assetBase; ?>/css/navbar.css">
  <link rel="stylesheet" href="<?= $assetBase; ?>/css/footer.css">
  <link rel="stylesheet" href="<?= $assetBase; ?>/css/upload.css">
  <link rel="stylesheet" href="<?= $assetBase; ?>/css/toast.css">
</head>
<body>

  <?php include __DIR__ . '/../partials/navbar.php'; ?>

  <main class="upload-page">
    <section class="upload-header">
      <span class="upload-tag">Content Management</span>
      <h1>Upload Content</h1>
      <p>Upload an MP3, add lyrics if you want, and choose who can access the track.</p>
    </section>

    <section class="upload-panel">
      <form id="uploadForm" class="upload-form" method="POST" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

        <h2>Upload Track</h2>

        <div class="form-group">
          <label for="title">Title</label>
          <input type="text" id="title" name="title" placeholder="Enter track title">
        </div>

        <div class="form-group">
          <label for="file">MP3 File</label>
          <input type="file" id="file" name="file" accept=".mp3,audio/mpeg" required>
          <small class="field-hint">MP3 is required.</small>
        </div>

        <div class="form-group">
          <label for="text_content">Lyrics</label>
          <textarea id="text_content" name="text_content" rows="10" placeholder="Write or paste your lyrics here"></textarea>
        </div>

        <div class="form-group">
          <span class="radio-label">Access Level</span>
          <div class="radio-row">
            <label class="radio-option">
              <input type="radio" name="track_visibility" value="free" checked>
              <span>Free</span>
            </label>
            <label class="radio-option">
              <input type="radio" name="track_visibility" value="pro">
              <span>Pro</span>
            </label>
          </div>
        </div>

        <button type="submit" class="btn btn-primary">Upload Track</button>
      </form>
    </section>
  </main>

  <div id="toast" class="toast"></div>

  <?php include __DIR__ . '/../partials/footer.php'; ?>

  <script src="<?= $assetBase; ?>/js/app.js"></script>
  <script src="<?= $assetBase; ?>/js/upload.js"></script>
</body>
</html>
