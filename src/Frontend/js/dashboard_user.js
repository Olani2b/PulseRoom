const MP3_LIST_ICON =
    'data:image/svg+xml,' +
    encodeURIComponent(
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%2316162a"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg>'
    );

class Dashboard {
    constructor(mainContentId) {
        this.mainContent = document.getElementById(mainContentId);
        this.userPage = 1;
        this.usersPerPage = 5;
        this.cataloguePage = 1;
        this.novelsPerPage = 6;
        this.novels = [];
        this.isLastCataloguePage = false;
        this.currentFileType = '';
        this.init();
    }

    init() {
        this.fetchCatalogueContent(this.cataloguePage);
        this.ensureToastScript();
        this.ensureUploadScript();
        this.updateLinkClasses(document.getElementById('catalogueLink'));
    }

    showSection(sectionId) {
        const sections = document.querySelectorAll('#mainContent > .w3-section');
        sections.forEach(section => {
            section.classList.add('w3-hide');
        });
        document.getElementById(sectionId).classList.remove('w3-hide');
    }

    async fetchCatalogueContent(page, fileType = this.currentFileType) {
        try {
            this.currentFileType = fileType;
            const queryParams = new URLSearchParams({
                page: page,
                limit: this.novelsPerPage,
            });

            if (fileType) {
                queryParams.append('file_type', fileType);
            }

            const response = await fetch(`/api/show_files?${queryParams.toString()}`, {
                method: 'GET'
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const result = await response.json();
            if (result.status === 'success' && Array.isArray(result.files)) {
                this.novels = result.files;
                this.isLastCataloguePage = result['last-page'];
                this.cataloguePage = page;
                this.renderCards(this.novels);
                this.syncFilterButtons();
                this.updatePageInfo();
            } else {
                console.error('Cannot retrieve library items:', result.message);
            }
        } catch (error) {
            console.error('There was a problem with the fetch operation:', error);
        }
        this.updatePageInfo();
    }
    renderCards(files) {
        const container = this.mainContent.querySelector('.w3-row-padding.bg');
    
        if (!container) {
            console.warn('Card container not found. Skipping card rendering.');
            return;
        }
    
        while (container.firstChild) {
            container.removeChild(container.firstChild);
        }

        if (files.length === 0) {
            const emptyState = document.createElement('div');
            emptyState.className = 'w3-panel w3-white w3-card w3-round-xlarge w3-padding-32 w3-center';
            emptyState.textContent = 'No tracks or lyrics match this filter yet.';
            container.appendChild(emptyState);
            return;
        }
    
        // Create rows dynamically and add cards to them
        let row;
        files.forEach((file, index) => {
            if (index % 3 === 0) {
                row = document.createElement('div');
                row.className = 'w3-row-padding library-row';
                container.appendChild(row);
            }
    
            let imageSrc;
            let role;
            let roleColor;
            switch (file.visibility) {
                case 0:
                    role = "Free plan";
                    roleColor = "#0cde42";
                    break;
                case 1:
                    role = "Pro plan";
                    roleColor = "#fecc00";
                    break;
                default:
                    role = "Undefined";
                    roleColor = "black";
                    break;
            }
            if (file.has_mp3) {
                imageSrc = MP3_LIST_ICON;
            } else if (file.has_lyrics) {
                imageSrc = './Frontend/imgs/text-file.png'; 
            } else {
                imageSrc = '/Frontend/imgs/nicola.png'; 
            }
    
            const card = document.createElement('div');
            card.className = 'w3-container w3-center w3-hover-shadow w3-card w3-border w3-round-xlarge library-card';
    
            const img = document.createElement('img');
            img.src = imageSrc;
            img.alt = file.title;
            img.style.width = '25%';
            card.appendChild(img);
    
            const cardContainer = document.createElement('div');
            cardContainer.className = 'w3-container library-card-body';
    
            const title = document.createElement('p');
            const boldTitle = document.createElement('b');
            boldTitle.textContent = file.title;
            title.appendChild(boldTitle);
            cardContainer.appendChild(title);
    
            const author = document.createElement('h6');
            author.textContent = `Artist: ${file.username}`;
            author.style.fontStyle = 'italic';
            cardContainer.appendChild(author);

            const timestamp = document.createElement('p');
            timestamp.textContent = `Uploaded: ${this.formatTimestamp(file.uploaded_at)}`;
            timestamp.className = 'library-meta';
            cardContainer.appendChild(timestamp);
    
            const roleElement = document.createElement('h5');
            roleElement.style.color = roleColor;
            roleElement.style.textShadow = '2px 2px 2px rgba(0, 0, 0, 0.5)';
            const boldRole = document.createElement('b');
            boldRole.textContent = role;
            roleElement.appendChild(boldRole);
            cardContainer.appendChild(roleElement);
    
            card.appendChild(cardContainer);
            const actions = document.createElement('div');
            actions.className = 'library-actions';
    
            if (file.has_lyrics && file.lyrics_id) {
                const readButton = document.createElement('button');
                readButton.className = 'w3-button btn-lyrics';
                readButton.dataset.fileId = file.lyrics_id;
                readButton.dataset.action = 'read';
                readButton.textContent = 'Read Lyrics';
                actions.appendChild(readButton);
            }

            if (file.has_mp3 && file.mp3_id) {
                const downloadButton = document.createElement('button');
                downloadButton.className = 'w3-button btn-download';
                downloadButton.dataset.fileId = file.mp3_id;
                downloadButton.dataset.action = 'download';
                downloadButton.textContent = 'Download';
                actions.appendChild(downloadButton);
            }

            if (Number(file.is_owner) === 1) {
                const deleteButton = document.createElement('button');
                deleteButton.className = 'w3-button w3-red';
                deleteButton.dataset.fileIds = [file.mp3_id, file.lyrics_id].filter(Boolean).join(',');
                deleteButton.dataset.action = 'delete';
                deleteButton.textContent = 'Delete';
                actions.appendChild(deleteButton);
            }

            card.appendChild(actions);
    
            row.appendChild(card);
        });
    
        container.querySelectorAll('button[data-action="read"]').forEach(button => {
            button.addEventListener('click', (event) => this.readFile(event.target.dataset.fileId));
        });
        container.querySelectorAll('button[data-action="download"]').forEach(button => {
            button.addEventListener('click', (event) => this.downloadFile(event.target.dataset.fileId));
        });
        container.querySelectorAll('button[data-action="delete"]').forEach(button => {
            button.addEventListener('click', (event) => this.deleteFile(event.target.dataset.fileIds));
        });
    }
    async deleteFile(fileIds) {
        if (!fileIds) {
            showToast('error', 'File ID is required.');
            return;
        }

        if (!window.confirm('Delete this upload permanently? This removes the song card and all attached files.')) {
            return;
        }

        try {
            const response = await fetch('/api/delete_file', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    file_ids: fileIds,
                    csrf_token: document.getElementById('csrf').value
                })
            });

            const result = await response.json();
            showToast(result.status, result.message);

            if (result.status === 'success') {
                await this.fetchCatalogueContent(this.cataloguePage, this.currentFileType);
            }
        } catch (error) {
            showToast('error', 'Could not delete the upload. Please try again.');
        }
    }
    formatTimestamp(timestamp) {
        const date = new Date(timestamp.replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) {
            return timestamp;
        }

        return date.toLocaleString();
    }
    async readFile(fileId) {
        try {
            if (!fileId) {
                throw new Error('File ID is required');
            }
    
            const response = await fetch('/api/download_file', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({ file_id: fileId })
            });
    
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
    
            const result = await response.json();
            if (result.status === 'success' && result.filetype === 'txt') {
                this.loadLyricsContent(result.filedata, result.title, result.author);
            } else {
                showToast('error', result.message);
            }
        } catch (error) {
            showToast('error', 'Could not load lyrics. Please try again.');
        }
        this.updateLinkClasses(document.getElementById('catalogueLink'));
        this.ensureToastScript();
        const sidebarLinks = document.querySelectorAll('.w3-bar-item');
        sidebarLinks.forEach(link => {
            link.classList.remove('w3-white');
        });
    }
    loadLyricsContent(fileData, title, artist) {
        const modal = document.createElement('div');
        modal.id = 'lyricsModal';
        modal.className = 'w3-modal w3-top';
        modal.style.display = 'block';

        const modalContent = document.createElement('div');
        modalContent.className = 'w3-modal-content w3-animate-opacity';
        modalContent.style.position = 'relative';
        modalContent.style.minWidth = '80%';
        modalContent.style.zIndex = '1000';

        const closeModal = document.createElement('span');
        closeModal.className = 'w3-button w3-black w3-border w3-round-xxlarge lyrics-close-button';
        closeModal.id = 'closeLyricsModal';
        closeModal.textContent = 'X';
        closeModal.style.fontWeight = 'bold';

        const wrapper = document.createElement('div');
        wrapper.id = 'wrapper';

        const container = document.createElement('div');
        container.id = 'container';

        const section = document.createElement('section');
        section.className = 'lyrics-panel';

        const header = document.createElement('header');
        const artistEl = document.createElement('h6');
        artistEl.textContent = `Artist: ${artist}`;
        header.appendChild(artistEl);

        const article = document.createElement('article');
        const titleEl = document.createElement('h2');
        titleEl.className = 'lyrics-title';
        titleEl.textContent = title;
        const lyricsText = document.createTextNode(fileData);
        article.appendChild(titleEl);
        article.appendChild(lyricsText);

        const footer = document.createElement('footer');
        const endNote = document.createElement('p');
        endNote.className = 'lyrics-end-note';
        endNote.textContent = 'Lyrics';
        footer.appendChild(endNote);

        section.appendChild(header);
        section.appendChild(article);
        section.appendChild(footer);

        container.appendChild(section);
        wrapper.appendChild(container);
        modalContent.appendChild(closeModal);
        modalContent.appendChild(wrapper);
        modal.appendChild(modalContent);
        document.body.appendChild(modal);

        document.getElementById('closeLyricsModal').addEventListener('click', () => {
            document.getElementById('lyricsModal').remove();
        });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                modal.remove();
            }
        });

        this.ensureToastScript();
        this.updateLinkClasses(document.getElementById('catalogueLink'));
    }
    async downloadFile(fileId) {
        try {
            if (!fileId) {
                throw new Error('File ID is required');
            }
    
            const response = await fetch('/api/download_file', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({ file_id: fileId })
            });
    
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
    
            const result = await response.json();
            if (result.status === 'success' && result.filetype === 'mp3') {
                showToast('success', "Download started");
                const link = document.createElement('a');
                link.href = `data:audio/mpeg;base64,${result.filedata}`;
                let fname = result.title || 'track';
                if (!/\.mp3$/i.test(fname)) {
                    fname += '.mp3';
                }
                link.download = fname;
                link.click();
            } else {
                showToast('error', result.message);
            }
        } catch (error) {
            showToast('error', 'Could not download the track. Please try again.');
        }
        this.updateLinkClasses(document.getElementById('catalogueLink'));
        this.ensureToastScript();
    }



    async fetchUsers(page) {
        try {
            const queryParams = new URLSearchParams({
                page: page,
                limit: this.usersPerPage,
            });

            const response = await fetch(`/api/show_users?${queryParams.toString()}`, {
                method: 'GET'
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const result = await response.json();
            if (result.status === 'success' && Array.isArray(result.data)) {
                this.users = result.data;
                this.isLastUserPage = result['last-page'];
                this.userPage = page;
                this.renderUserList();
                this.updatePageInfo();
            } else {
                console.error('Cannot retrieve users:', result.message);
            }
        } catch (error) {
            console.error('There was a problem with the fetch operation:', error);
        }
    }

    updatePageInfo() {
        const cataloguePageInfo = document.getElementById('cataloguePageInfo');
        const usersPageInfo = document.getElementById('usersPageInfo');

        if (usersPageInfo) {
            usersPageInfo.textContent = `Page ${this.userPage}`;
        }

        if (cataloguePageInfo) {
            cataloguePageInfo.textContent = `Page ${this.cataloguePage}`;
        }

        // Disable buttons when catalogue pagination is at bounds
        const prevCataloguePageBtn = document.getElementById('prevCataloguePageBtn');
        const nextCataloguePageBtn = document.getElementById('nextCataloguePageBtn');

        if (prevCataloguePageBtn && nextCataloguePageBtn) {
            prevCataloguePageBtn.disabled = this.cataloguePage === 1;
            nextCataloguePageBtn.disabled = this.isLastCataloguePage;
        }

        // Disable buttons and hide span if not enough users for pagination
        const prevUserPageBtn = document.getElementById('prevUserPageBtn');
        const nextUserPageBtn = document.getElementById('nextUserPageBtn');

        if (prevUserPageBtn && nextUserPageBtn) {
            prevUserPageBtn.disabled = this.userPage === 1;
            nextUserPageBtn.disabled = this.isLastUserPage;
        }
    }

    async changePage(type, direction) {
        if (type === 'catalogue') {
            if (direction === 'next' && !this.isLastCataloguePage) {
                this.cataloguePage++;
            } else if (direction === 'prev' && this.cataloguePage > 1) {
                this.cataloguePage--;
            }
            await this.fetchCatalogueContent(this.cataloguePage, this.currentFileType);
        }
        this.updatePageInfo();
    }

    ensureToastScript() {
        // Check if toast.js is already loaded
        if (!document.querySelector('script[src="./Frontend/js/toast.js"]')) {
            var script = document.createElement('script');
            script.src = './Frontend/js/toast.js';
            document.head.appendChild(script);
        } 
    }
    ensureUploadScript() {
        // Check if upload_file.js is already loaded
        if (!document.querySelector('script[src="./Frontend/js/upload_file.js"]')) {
            var script = document.createElement('script');
            script.src = './Frontend/js/upload_file.js';
            document.head.appendChild(script);
        } else {
            // Reinitialize event listeners if upload_file.js is already loaded
            init();
        }
    }

    updateLinkClasses(activeLink) {
        const sidebarLinks = document.querySelectorAll('.w3-bar-item');
        sidebarLinks.forEach(link => {
            link.classList.remove('w3-white');
        });
        activeLink.classList.add('w3-white');
    }
    handleButtonClick(event, fileType) {
        this.cataloguePage = 1;
        this.fetchCatalogueContent(this.cataloguePage,fileType);
    }

    syncFilterButtons() {
        const activeButtonId = this.currentFileType === 'mp3'
            ? 'mp3FilterBtn'
            : this.currentFileType === 'txt'
                ? 'txtBtn'
                : 'latestBtn';
        const activeButton = document.getElementById(activeButtonId);
        if (activeButton) {
            this.updateButtonClasses(activeButton);
        }
    }

    updateButtonClasses(activeButton) {
        const buttons = document.querySelectorAll('#catalogueSection .w3-bottombar .w3-button');
        buttons.forEach(button => {
            button.classList.remove('w3-white');
            button.classList.add('w3-black');
        });
        activeButton.classList.remove('w3-black');
        activeButton.classList.add('w3-white');
    }

    clearMainContent() {
        while (this.mainContent.firstChild) {
            this.mainContent.removeChild(this.mainContent.firstChild);
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const dashboard = new Dashboard('mainContent');

    const uploadFileLink = document.getElementById('uploadFileLink');
    if (uploadFileLink) {
        uploadFileLink.addEventListener('click', function(event) {
            event.preventDefault();
            dashboard.showSection('uploadFileSection');
            dashboard.updateLinkClasses(document.getElementById('uploadFileLink'));
        });
    }

    const uploadFileLink_mobile = document.getElementById('uploadFileLink-mobile');
    if (uploadFileLink_mobile) {
        uploadFileLink_mobile.addEventListener('click', function(event) {
            event.preventDefault();
            dashboard.showSection('uploadFileSection');
            w3_close();
            dashboard.updateLinkClasses(document.getElementById('uploadFileLink'));
        });
    }
    const catalogueLink= document.getElementById('catalogueLink');
    if (catalogueLink) {
        catalogueLink.addEventListener('click', function(event) {
            event.preventDefault();
            dashboard.showSection('catalogueSection');
            dashboard.updateLinkClasses(document.getElementById('catalogueLink'));
            dashboard.fetchCatalogueContent(dashboard.cataloguePage);
        });
    }

    const catalogueLink_mobile = document.getElementById('catalogueLink-mobile');
    if (catalogueLink_mobile) {
        catalogueLink_mobile.addEventListener('click', function(event) {
            event.preventDefault();
            dashboard.showSection('catalogueSection');
            w3_close();
            dashboard.updateLinkClasses(document.getElementById('catalogueLink'));
            dashboard.fetchCatalogueContent(dashboard.cataloguePage);
        });
    }
  
    const logoutBtn = document.getElementById('log2');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(event) {
            event.preventDefault();
            logoutUser();
        });
    }
    const logoutBtn1 = document.getElementById('log1');
    if (logoutBtn1) {
        logoutBtn1.addEventListener('click', function(event) {
            event.preventDefault();
            w3_close();
            logoutUser();
        });
    }

    const latestBtn = document.getElementById('latestBtn');
    if (latestBtn) {
        latestBtn.addEventListener('click', (event) => dashboard.handleButtonClick(event, ''));
    }

    const mp3FilterBtn = document.getElementById('mp3FilterBtn');
    if (mp3FilterBtn) {
        mp3FilterBtn.addEventListener('click', (event) => dashboard.handleButtonClick(event, 'mp3'));
    }

    const txtBtn = document.getElementById('txtBtn');
    if (txtBtn) {
        txtBtn.addEventListener('click', (event) => dashboard.handleButtonClick(event, 'txt'));
    }

    const prevCataloguePageBtn = document.getElementById('prevCataloguePageBtn');
    if (prevCataloguePageBtn) {
        prevCataloguePageBtn.addEventListener('click', () => dashboard.changePage('catalogue', 'prev'));
    }

    const nextCataloguePageBtn = document.getElementById('nextCataloguePageBtn');
    if (nextCataloguePageBtn) {
        nextCataloguePageBtn.addEventListener('click', () => dashboard.changePage('catalogue', 'next'));
    }
});
