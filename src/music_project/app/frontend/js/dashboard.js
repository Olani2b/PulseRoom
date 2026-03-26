document.addEventListener('DOMContentLoaded', () => {
  const mediaGrid = document.getElementById('mediaGrid');
  const emptyState = document.getElementById('dashboardEmptyState');
  const pageLabel = document.getElementById('pageLabel');
  const prevPageBtn = document.getElementById('prevPageBtn');
  const nextPageBtn = document.getElementById('nextPageBtn');
  const modal = document.getElementById('lyricsModal');
  const modalTitle = document.getElementById('modalTitle');
  const modalSubtitle = document.getElementById('modalSubtitle');
  const modalLyrics = document.getElementById('modalLyrics');
  const closeModal = document.getElementById('closeModal');
  const filterButtons = document.querySelectorAll('.filter-btn');

  let currentPage = 1;
  let currentFileType = 'both';
  let isLastPage = false;

  const fetchFiles = async () => {
    try {
      const params = new URLSearchParams({
        page: String(currentPage),
        limit: '6',
        file_type: currentFileType
      });

      const response = await fetch(`/api/show_files?${params.toString()}`);
      const result = await response.json();

      if (!response.ok || result.status !== 'success') {
        throw new Error(result.message || 'Failed to load files.');
      }

      isLastPage = Boolean(result['last-page']);
      renderFiles(result.files || []);
      pageLabel.textContent = `Page ${currentPage}`;
      prevPageBtn.disabled = currentPage === 1;
      nextPageBtn.disabled = isLastPage;
    } catch (error) {
      showToast('error', error.message || 'Unable to load dashboard content.');
    }
  };

  const renderFiles = (files) => {
    mediaGrid.innerHTML = '';
    emptyState.classList.toggle('hidden', files.length > 0);

    files.forEach((file) => {
      const card = document.createElement('article');
      card.className = 'media-card';

      const visibility = file.visibility === 1 ? 'Pro plan' : 'Free plan';
      const uploadedAt = new Date(file.uploaded_at).toLocaleString();
      const icon = file.has_mp3 && !file.has_lyrics ? '♪' : '📖';
      const visibilityClass = file.visibility === 1 ? 'access-pro' : 'access-free';

      const actions = [];
      if (file.has_lyrics) {
        actions.push(`<button class="btn-small read-btn" type="button" data-file-id="${file.lyrics_id}">Read Lyrics</button>`);
      }
      if (file.has_mp3) {
        actions.push(`<button class="btn-small download-btn" type="button" data-file-id="${file.mp3_id}">Download MP3</button>`);
      }
      if (file.is_owner) {
        const ids = [file.mp3_id, file.lyrics_id].filter(Boolean).join(',');
        actions.push(`<button class="btn-small delete-btn" type="button" data-file-ids="${ids}">Delete</button>`);
      }

      card.innerHTML = `
        <div class="media-icon" aria-hidden="true">${icon}</div>
        <h3>${escapeHtml(file.title)}</h3>
        <p class="media-artist">Artist: ${escapeHtml(file.username)}</p>
        <div class="media-meta">Uploaded: ${uploadedAt}</div>
        <div class="access-badge ${visibilityClass}">${visibility}</div>
        <div class="media-actions ${actions.length > 1 ? 'has-two-actions' : ''}">${actions.join('')}</div>
      `;

      mediaGrid.appendChild(card);
    });

    mediaGrid.querySelectorAll('.read-btn').forEach((button) => {
      button.addEventListener('click', () => readLyrics(button.dataset.fileId));
    });
    mediaGrid.querySelectorAll('.download-btn').forEach((button) => {
      button.addEventListener('click', () => downloadMp3(button.dataset.fileId));
    });
    mediaGrid.querySelectorAll('.delete-btn').forEach((button) => {
      button.addEventListener('click', () => deleteUpload(button.dataset.fileIds));
    });
  };

  const readLyrics = async (fileId) => {
    try {
      const response = await fetch('/api/download_file', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ file_id: fileId })
      });
      const result = await response.json();

      if (!response.ok || result.status !== 'success') {
        throw new Error(result.message || 'Unable to open lyrics.');
      }

      modalTitle.textContent = result.title;
      modalSubtitle.textContent = `By ${result.author}`;
      renderLyricsBook(result.filedata);
      modal.classList.remove('hidden');
    } catch (error) {
      showToast('error', error.message || 'Unable to open lyrics.');
    }
  };

  const downloadMp3 = async (fileId) => {
    try {
      const response = await fetch('/api/download_file', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ file_id: fileId })
      });
      const result = await response.json();

      if (!response.ok || result.status !== 'success') {
        throw new Error(result.message || 'Unable to download MP3.');
      }

      const link = document.createElement('a');
      link.href = `data:audio/mpeg;base64,${result.filedata}`;
      link.download = `${result.title}.mp3`;
      link.click();
      showToast('success', 'Download started.');
    } catch (error) {
      showToast('error', error.message || 'Unable to download MP3.');
    }
  };

  const deleteUpload = async (fileIds) => {
    try {
      const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';
      const response = await fetch('/api/delete_file', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          file_ids: fileIds,
          csrf_token: csrfToken
        })
      });
      const result = await response.json();

      if (!response.ok || result.status !== 'success') {
        throw new Error(result.message || 'Delete failed.');
      }

      showToast('success', result.message || 'Upload deleted successfully.');
      fetchFiles();
    } catch (error) {
      showToast('error', error.message || 'Delete failed.');
    }
  };

  const escapeHtml = (value) => {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
  };

  const renderLyricsBook = (lyrics) => {
    const sections = String(lyrics || '')
      .replace(/\r\n/g, '\n')
      .split(/\n{2,}/)
      .map((section) => section.trim())
      .filter(Boolean);

    if (sections.length === 0) {
      modalLyrics.innerHTML = '<p class="lyrics-empty">No lyrics available.</p>';
      return;
    }

    modalLyrics.innerHTML = sections
      .map((section) => {
        const lines = section
          .split('\n')
          .map((line) => line.trim())
          .filter((line) => line.length > 0)
          .map((line) => `<span class="lyrics-line">${escapeHtml(line)}</span>`)
          .join('');

        return `<section class="lyrics-stanza">${lines}</section>`;
      })
      .join('');
  };

  closeModal?.addEventListener('click', () => modal.classList.add('hidden'));
  modal?.addEventListener('click', (event) => {
    if (event.target === modal) {
      modal.classList.add('hidden');
    }
  });

  filterButtons.forEach((button) => {
    button.addEventListener('click', () => {
      filterButtons.forEach((item) => item.classList.remove('active'));
      button.classList.add('active');
      currentFileType = button.dataset.fileType;
      currentPage = 1;
      fetchFiles();
    });
  });

  prevPageBtn?.addEventListener('click', () => {
    if (currentPage > 1) {
      currentPage -= 1;
      fetchFiles();
    }
  });

  nextPageBtn?.addEventListener('click', () => {
    if (!isLastPage) {
      currentPage += 1;
      fetchFiles();
    }
  });

  fetchFiles();
});
