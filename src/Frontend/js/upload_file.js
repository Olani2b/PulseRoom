// Init upload file page
let isUploading = false;

function init() {
    const uploadForm = document.getElementById('uploadForm');
    if (!uploadForm || uploadForm.dataset.initialized === 'true') {
        return;
    }

    uploadForm.addEventListener('submit', uploadFile);
    uploadForm.dataset.initialized = 'true';
}

async function uploadFile(event) {
    event.preventDefault();

    if (isUploading) {
        showToast('warning', 'Upload already in progress. Please wait.');
        return;
    }

    const formData = new FormData();
    const fileInput = document.getElementById('file');
    const title = document.getElementById('title').value.trim();
    const textContent = document.getElementById('text_content').value.trim();
    const roleElement = document.querySelector('input[name="track-visibility"]:checked');
    const role = roleElement ? roleElement.value : 'none';

    if (role === 'none') {
        showToast('warning', 'Select a plan visibility.');
        return;
    }

    const hasFile = fileInput && fileInput.files.length > 0;
    const hasLyrics = textContent !== '';

    if (!hasFile && !hasLyrics) {
        showToast('warning', 'Upload an MP3, lyrics, or both.');
        return;
    }

    if (hasFile) {
        const file = fileInput.files[0];
        const maxFileSize = 5 * 1024 * 1024;

        if (file.size > maxFileSize) {
            showToast('warning', 'File size exceeds the maximum limit of 5 MB.');
            resetFields();
            return;
        }

        formData.append('file', file);
    }

    if (hasLyrics && title === '') {
        showToast('warning', 'Insert a title when uploading lyrics.');
        return;
    }

    if (title !== '') {
        formData.append('title', title);
    }

    if (hasLyrics) {
        formData.append('text_content', textContent);
    }

    formData.append('track_visibility', role);
    formData.append('upload_type', 'mixed');
    formData.append('csrf_token', document.getElementById('csrf_form').value);

    isUploading = true;
    setUploadButtonState(true);

    try {
        const response = await fetch('/api/upload_file', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`Upload failed with status ${response.status}`);
        }

        const result = await response.json();
        showToast(result.status, result.message);
        if (result.status === 'success') {
            resetFields();
            setTimeout(() => {
                window.location.reload();
            }, 1400);
        }
    } catch (error) {
        showToast('error', 'Error during track or lyrics upload.');
    } finally {
        isUploading = false;
        setUploadButtonState(false);
    }
}

function setUploadButtonState(isBusy) {
    const submitBtn = document.getElementById('submitBtn');
    if (!submitBtn) {
        return;
    }

    submitBtn.disabled = isBusy;
    submitBtn.innerHTML = isBusy
        ? '<i class="fa fa-spinner fa-spin"></i> UPLOADING...'
        : '<i class="fa fa-upload"></i> UPLOAD';
}

function resetFields() {
    document.getElementById('file').value = '';
    document.getElementById('title').value = '';
    document.getElementById('text_content').value = '';
    const roleElements = document.querySelectorAll('input[name="track-visibility"]');
    roleElements.forEach(element => {
        element.checked = false;
    });
}
