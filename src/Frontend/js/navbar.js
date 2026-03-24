// Used to toggle the menu on small screens when clicking on the menu button
function w3_open() {
    const mySidebar = document.getElementById("mySidebar");
    if (mySidebar.classList.contains('visible')) {
        mySidebar.classList.remove('visible');
        mySidebar.classList.add('hidden');
    } else {
        mySidebar.classList.remove('hidden');
        mySidebar.classList.add('visible');
    }
}

// Close the sidebar with the close button
function w3_close() {
    const mySidebar = document.getElementById("mySidebar");
    mySidebar.classList.remove('visible');
    mySidebar.classList.add('hidden');
}

function w3_open_dash() {
    const mySidebar = document.getElementById("mySidebar");
    const myOverlay = document.getElementById("myOverlay");
    mySidebar.classList.add('visible');
    myOverlay.classList.add('visible');
}

function w3_close_dash() {
    const mySidebar = document.getElementById("mySidebar");
    const myOverlay = document.getElementById("myOverlay");
    mySidebar.classList.remove('visible');
    myOverlay.classList.remove('visible');
}

document.addEventListener('DOMContentLoaded', function() {
    // Add event listener to open the sidebar
    const openSidebarBtn = document.querySelector('.w3-bar-item.w3-button.w3-right.w3-hide-large');
    if (openSidebarBtn) {
        openSidebarBtn.addEventListener('click', w3_open);
    }

    // Add event listener to close the sidebar
    const closeSidebarBtn = document.getElementById('closeSidebarBtn');
    if (closeSidebarBtn) {
        closeSidebarBtn.addEventListener('click', w3_close);
    }

    // Ensure the sidebar is hidden initially
    const mySidebar = document.getElementById("mySidebar");
    if (mySidebar) {
        mySidebar.classList.add('hidden');
    }
});