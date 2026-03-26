document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('uploadForm');
  const fileInput = document.getElementById('file');

  if (!form || !fileInput) {
    return;
  }

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    if (!fileInput.files.length) {
      showToast('warning', 'Select an MP3 file to upload.');
      return;
    }

    const file = fileInput.files[0];
    if (!file.name.toLowerCase().endsWith('.mp3')) {
      showToast('warning', 'Only MP3 files are supported.');
      return;
    }

    try {
      const response = await fetch('/api/upload_file', {
        method: 'POST',
        body: new FormData(form)
      });
      const result = await response.json();

      if (!response.ok || result.status !== 'success') {
        throw new Error(result.message || 'Upload failed.');
      }

      showToast('success', result.message || 'Content uploaded successfully.');
      form.reset();
      const defaultRadio = form.querySelector('input[name="track_visibility"][value="free"]');
      if (defaultRadio) {
        defaultRadio.checked = true;
      }
    } catch (error) {
      showToast('error', error.message || 'Upload failed.');
    }
  });
});
