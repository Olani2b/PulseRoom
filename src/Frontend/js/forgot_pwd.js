class ForgotPassword {
    constructor(formId) {
        this.forgotPwdForm = document.getElementById(formId);
        this.init();
    }

    init() {
        this.forgotPwdForm.addEventListener('submit', (event) => this.forgotPwd(event));
        this.ensureToastScript();
    }

    async forgotPwd(event) {
        event.preventDefault();
        const formData = new FormData(this.forgotPwdForm);

        try {
            const response = await fetch('/api/forgot_pwd', {
                method: 'POST',
                body: formData
            });

            if (response.ok) {
                const result = await response.json();
                showToast('success', result.message);
                setTimeout(() =>
                    this.showSuccessMessage(), 2000);
            } else {
                const error = await response.json();
                showToast('error', error.message);
            }
        } catch (error) {
            showToast('error', error.message);
        }
    }

    showSuccessMessage() {
        const homeDiv = document.getElementById('home');
        // Clear existing content
        while (homeDiv.firstChild) {
            homeDiv.removeChild(homeDiv.firstChild);
        }

        // Create and append new elements
        const div = document.createElement('div');
        div.className = 'w3-display-left w3-text-white';
        div.style.padding = '48px';

        const span1 = document.createElement('span');
        span1.className = 'w3-jumbo w3-hide-small w3-animate-bottom';
        span1.textContent = 'Check your email inbox.';
        div.appendChild(span1);
        div.appendChild(document.createElement('br'));

        const span2 = document.createElement('span');
        span2.className = 'w3-xxlarge w3-hide-large w3-hide-medium w3-animate-bottom';
        span2.textContent = 'Check your email inbox.';
        div.appendChild(span2);
        div.appendChild(document.createElement('br'));

        const span3 = document.createElement('span');
        span3.className = 'w3-large w3-animate-bottom';
        span3.textContent = 'An email has been sent to your email address to reset your password.';
        div.appendChild(span3);

        homeDiv.appendChild(div);
    }

    ensureToastScript() {
        // Check if toast.js is already loaded
        if (!document.querySelector('script[src="./Frontend/js/toast.js"]')) {
            var script = document.createElement('script');
            script.src = './Frontend/js/toast.js';
            document.head.appendChild(script);
        } else {
            // Reinitialize notifications if toast.js is already loaded
            initNotifications();
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new ForgotPassword('forgotPwdForm');
});