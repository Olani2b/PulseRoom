class Dashboard {
    constructor(mainContentId) {
        this.mainContent = document.getElementById(mainContentId);
        this.userPage = 1;
        this.usersPerPage = 5;
        this.cataloguePage = 1;
        this.novelsPerPage = 6;
        this.novels = [];
        this.users = [];
        this.isLastUserPage = false;
        this.isLastCataloguePage = false;
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

    async fetchCatalogueContent(page, fileType = '') {
        try {
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
                this.updatePageInfo();
            } else {
                console.error('Cannot retrieve novels:', result.message);
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
    
        // Create rows dynamically and add cards to them
        let row;
        files.forEach((file, index) => {
            if (index % 3 === 0) {
                row = document.createElement('div');
                row.className = 'w3-row-padding';
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
            if (file.filetype === 'txt') {
                imageSrc = './Frontend/imgs/text-file.png'; 
            } else if (file.filetype === 'mp3') {
                imageSrc = './Frontend/imgs/pdf-file.png'; 
            } else {
                imageSrc = '/Frontend/imgs/nicola.png'; 
            }
    
            const card = document.createElement('div');
            card.className = 'w3-third w3-container w3-center w3-margin-bottom w3-hover-shadow w3-card w3-border w3-round-xlarge';
    
            const img = document.createElement('img');
            img.src = imageSrc;
            img.alt = file.filename;
            img.style.width = '25%';
            card.appendChild(img);
    
            const cardContainer = document.createElement('div');
            cardContainer.className = 'w3-container';
    
            const title = document.createElement('p');
            const boldTitle = document.createElement('b');
            boldTitle.textContent = file.title;
            title.appendChild(boldTitle);
            cardContainer.appendChild(title);
    
            const author = document.createElement('h6');
            author.textContent = `Author: ${file.username}`;
            author.style.fontStyle = 'italic';
            cardContainer.appendChild(author);
    
            const roleElement = document.createElement('h5');
            roleElement.style.color = roleColor;
            roleElement.style.textShadow = '2px 2px 2px rgba(0, 0, 0, 0.5)';
            const boldRole = document.createElement('b');
            boldRole.textContent = role;
            roleElement.appendChild(boldRole);
            cardContainer.appendChild(roleElement);
    
            card.appendChild(cardContainer);
    
            if (file.filetype === 'txt') {
                const readButton = document.createElement('button');
                readButton.className = 'w3-button w3-black w3-margin-bottom';
                readButton.dataset.fileId = file.id;
                readButton.dataset.action = 'read';
                readButton.textContent = 'Read';
                card.appendChild(readButton);
            } else if (file.filetype === 'mp3') {
                const downloadButton = document.createElement('button');
                downloadButton.className = 'w3-button w3-black w3-margin-bottom';
                downloadButton.dataset.fileId = file.id;
                downloadButton.dataset.action = 'download';
                downloadButton.textContent = 'Download MP3';
                card.appendChild(downloadButton);
            }
    
            row.appendChild(card);
        });
    
        container.querySelectorAll('button[data-action="read"]').forEach(button => {
            button.addEventListener('click', (event) => this.readFile(event.target.dataset.fileId));
        });
        container.querySelectorAll('button[data-action="download"]').forEach(button => {
            button.addEventListener('click', (event) => this.downloadFile(event.target.dataset.fileId));
        });
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
                this.loadNovelContent(result.filedata, result.title, result.author);
            } else {
                showToast('error', result.message);
            }
        } catch (error) {
            showToast('error', 'An error occurred while downloading the file');
        }
        this.updateLinkClasses(document.getElementById('catalogueLink'));
        this.ensureToastScript();
        const sidebarLinks = document.querySelectorAll('.w3-bar-item');
        sidebarLinks.forEach(link => {
            link.classList.remove('w3-white');
        });
    }
    loadNovelContent(fileData, title, author) {
        // Create the modal container
        const modal = document.createElement('div');
        modal.id = 'novelModal';
        modal.className = 'w3-modal w3-top';
        modal.style.display = 'block';
    
        // Create the modal content
        const modalContent = document.createElement('div');
        modalContent.className = 'w3-modal-content w3-animate-opacity';
        modalContent.style.position = 'relative';
        modalContent.style.minWidth = '80%';
        modalContent.style.zIndex = '1000';
    
        // Create the close button
        const closeModal = document.createElement('span');
        closeModal.className = 'w3-button w3-black w3-display-middle w3-border w3-round-xxlarge';
        closeModal.id = 'closeModal';
        closeModal.textContent = 'X';
        closeModal.style.fontWeight = 'bold';
    
        // Create the wrapper
        const wrapper = document.createElement('div');
        wrapper.id = 'wrapper';
    
        // Create the container
        const container = document.createElement('div');
        container.id = 'container';
    
        // Create the open-book section
        const section = document.createElement('section');
        section.className = 'open-book';
    
        // Create the header
        const header = document.createElement('header');
        const authorElement = document.createElement('h6');
        authorElement.textContent = `Author: ${author}`;
        header.appendChild(authorElement);
    
        // Create the article
        const article = document.createElement('article');
        const chapterTitle = document.createElement('h2');
        chapterTitle.className = 'chapter-title';
        chapterTitle.textContent = title;
        const fileDataParagraph = document.createTextNode(fileData);
        article.appendChild(chapterTitle);
        article.appendChild(fileDataParagraph);
    
        // Create the footer
        const footer = document.createElement('footer');
        const pageNumbers = document.createElement('ol');
        pageNumbers.id = 'page-numbers';
        const pageNumber1 = document.createElement('li');
        pageNumber1.textContent = '1';
        const pageNumber2 = document.createElement('li');
        pageNumber2.textContent = '2';
        pageNumbers.appendChild(pageNumber1);
        pageNumbers.appendChild(pageNumber2);
        footer.appendChild(pageNumbers);
    
        // Append elements to the section
        section.appendChild(header);
        section.appendChild(article);
        section.appendChild(footer);
    
        // Append elements to the container
        container.appendChild(section);
    
        // Append elements to the wrapper
        wrapper.appendChild(container);
    
        // Append elements to the modal content
        modalContent.appendChild(closeModal);
        modalContent.appendChild(wrapper);
    
        // Append modal content to the modal
        modal.appendChild(modalContent);
    
        // Append the modal to the body
        document.body.appendChild(modal);
    
        // Add event listener to the close button
        document.getElementById('closeModal').addEventListener('click', () => {
            document.getElementById('novelModal').remove();
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
                link.download = result.title;
                link.click();
            } else {
                showToast('error', result.message);
            }
        } catch (error) {
            showToast('error', 'An error occurred while downloading the file');
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

    async loadAdminPageContent() {
        await this.fetchUsers(this.userPage);

        document.getElementById('prevUserPageBtn').addEventListener('click', () => this.changePage('users', 'prev'));
        document.getElementById('nextUserPageBtn').addEventListener('click', () => this.changePage('users', 'next'));

        document.getElementById('userTableBody').addEventListener('change', (event) => {
            if (event.target && event.target.name === 'role') {
                const userId = event.target.getAttribute('data-user-id');
                const newRole = event.target.value;
                const actualRole = event.target.getAttribute('data-actual-role');
                this.changeUserRole(userId, newRole, actualRole);
            }
        });
    }

    renderUserList() {
        const userTableBody = document.getElementById('userTableBody');
        while (userTableBody.firstChild) {
            userTableBody.removeChild(userTableBody.firstChild);
        }

        this.users.forEach(user => {
            const tr = document.createElement('tr');

            const tdId = document.createElement('td');
            tdId.style.fontWeight = 'bold';
            tdId.textContent = user.id;
            tr.appendChild(tdId);

            const tdUsername = document.createElement('td');
            tdUsername.style.fontWeight = 'bold';
            tdUsername.textContent = user.username;
            tr.appendChild(tdUsername);

            const tdEmail = document.createElement('td');
            tdEmail.style.fontWeight = 'bold';
            tdEmail.textContent = user.email;
            tr.appendChild(tdEmail);

            const tdRole = document.createElement('td');
            const selectRole = document.createElement('select');
            selectRole.className = 'w3-select w3-border-black w3-round-xxlarge scrollable-menu';
            selectRole.name = 'role';
            selectRole.setAttribute('data-user-id', user.id);
            selectRole.setAttribute('data-actual-role', user.role);

            const optionFree = document.createElement('option');
            optionFree.value = 'free';
            optionFree.textContent = 'Free';
            if (user.role === 'free') {
                optionFree.selected = true;
            }
            selectRole.appendChild(optionFree);

            const optionPro = document.createElement('option');
            optionPro.value = 'pro';
            optionPro.textContent = 'Pro';
            if (user.role === 'pro') {
                optionPro.selected = true;
            }
            selectRole.appendChild(optionPro);

            tdRole.appendChild(selectRole);
            tr.appendChild(tdRole);

            userTableBody.appendChild(tr);
        });

        this.updatePageInfo();
    }

    async changeUserRole(userId, newRole, actualRole) {
        try {
            const formData = new FormData();
            formData.append('id', userId);
            formData.append('new_role', newRole);
            formData.append('actual_role', actualRole);
            formData.append('csrf_token', document.getElementById('csrf').value);
            const response = await fetch('/api/change_role', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.status === 'success') {
                showToast('success', result.message);
                //Reload actual role
                const selectElement = document.querySelector(`select[data-user-id="${userId}"]`);
                if (selectElement) {
                    selectElement.setAttribute('data-actual-role', newRole);
                }
            } else {
                showToast('error', result.message);
                console.error('Failed to change role:', result.message);
            }
        } catch (error) {
            showToast('error', 'An error occurred while changing the role');
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

        // Disable buttons and hide span if not enough novels for pagination
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
        if (type === 'users') {
            if (direction === 'next' && !this.isLastUserPage) {
                this.userPage++;
            } else if (direction === 'prev' && this.userPage > 1) {
                this.userPage--;
            }
            await this.fetchUsers(this.userPage);
        } else if (type === 'catalogue') {
            if (direction === 'next' && !this.isLastCataloguePage) {
                this.cataloguePage++;
            } else if (direction === 'prev' && this.cataloguePage > 1) {
                this.cataloguePage--;
            }
            await this.fetchCatalogueContent(this.cataloguePage);
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
        this.updateButtonClasses(event.target);
        this.cataloguePage = 1;
        this.fetchCatalogueContent(this.cataloguePage,fileType);
    }
    updateButtonClasses(activeButton) {
        const buttons = document.querySelectorAll('.w3-section .w3-button');
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

    const adminPageLink = document.getElementById('adminPageLink');
    if (adminPageLink) {
        adminPageLink.addEventListener('click', function(event) {
            event.preventDefault();
            dashboard.showSection('manageUsersSection');
            dashboard.updateLinkClasses(document.getElementById('adminPageLink'));
            dashboard.loadAdminPageContent();
        });
    }
    
    
    const adminPageLink_mobile = document.getElementById('adminPageLink-mobile');
    if (adminPageLink_mobile) {
        adminPageLink_mobile.addEventListener('click', function(event) {
            event.preventDefault();
            dashboard.showSection('manageUsersSection');
            w3_close();
            dashboard.updateLinkClasses(document.getElementById('adminPageLink'));
            dashboard.loadAdminPageContent();
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

    const pdfBtn = document.getElementById('pdfBtn');
    if (pdfBtn) {
        pdfBtn.addEventListener('click', (event) => dashboard.handleButtonClick(event, 'mp3'));
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