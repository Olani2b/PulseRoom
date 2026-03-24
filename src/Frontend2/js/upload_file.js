// Init upload file page

function init() {
    const uploadForm = document.getElementById('uploadForm');
    uploadForm.addEventListener('submit', uploadFile);
    document.getElementById('upload_file_radio').addEventListener('change', toggleUploadSection);
    document.getElementById('upload_text_radio').addEventListener('change', toggleUploadSection);   
    toggleUploadSection();
}

async function uploadFile(event) {
    event.preventDefault();

    const formData = new FormData();
    const uploadTypeElement = document.querySelector('input[name="upload_type"]:checked');
    const uploadType = uploadTypeElement ? uploadTypeElement.value : 'none';

    if (uploadType === 'none') {
        showToast('warning', 'Select the type of content to upload.');
        return;
    }
    if (uploadType === 'file') {
        const fileInput = document.getElementById('file');
        const roleElement = document.querySelector('input[name="novel-category"]:checked');
        const role = roleElement ? roleElement.value : 'none';

        if (role === 'none') {
            showToast('warning', 'Select a plan visibility.');
            return;
        }
        if (!fileInput || fileInput.files.length === 0) {
            showToast('warning', 'Select an MP3 file to upload.');
            return;
        }
        const file = fileInput.files[0];
        const maxFileSize = 2 * 1024 * 1024;

        if (file.size > maxFileSize) {
            showToast('warning', 'File size exceeds the maximum limit of 2 MB.');
            resetFields();
            return;
        }
        formData.append('file', fileInput.files[0]);
        formData.append('novel_category', role);
    } 
    else{
        const title = document.getElementById('title').value;
        const textContent = document.getElementById('text_content').value;
        const roleElement = document.querySelector('input[name="novel-category"]:checked');
        const role = roleElement ? roleElement.value : 'none';
        if (role === 'none') {
            showToast('warning', 'Select a plan visibility.');
            return;
        }
        if (title.trim() === '') {
            showToast('warning', 'Insert a title.');
            return;
        }
        if (textContent.trim() === '') {
            showToast('warning', 'Insert lyrics to upload.');
            return;
        }
        
        formData.append('text_content', textContent);
        formData.append('title', title);
        formData.append('novel_category', role);
    }

    formData.append('upload_type', uploadType);
    formData.append('csrf_token', document.getElementById('csrf_form').value);

    try {
        const response = await fetch('/api/upload_file', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        showToast(result.status, result.message);
        resetFields();
    } catch (error) {
        showToast('error', 'Error during track or lyrics upload.');
        resetFields();
    }
}
function resetFields() {
    if(document.getElementById('upload_file_radio').checked){
        document.getElementById('file').value = '';
    }
    document.getElementById('title').value = '';
    document.getElementById('text_content').value = '';
    const roleElements = document.querySelectorAll('input[name="novel-category"]');
    roleElements.forEach(element => {
        element.checked = false;
    });
}
function toggleUploadSection() {
    const isTextSelected = document.getElementById('upload_text_radio').checked;
    const fileUploadSection = document.getElementById('file_upload_section');
    const textUploadSection = document.getElementById('text_upload_section');  

    if (isTextSelected) {
        fileUploadSection.classList.add('hidden');
        textUploadSection.classList.remove('hidden');
    } else {
        fileUploadSection.classList.remove('hidden');
        textUploadSection.classList.add('hidden');
    }
}