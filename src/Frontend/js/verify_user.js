class EmailConfirmationPage {
    constructor(confirmationContentId) {
        this.mainContent = document.getElementById(confirmationContentId);
    }

    async render() {
        const urlParams = new URLSearchParams(window.location.search);
        const email = urlParams.get('email');
        const token = urlParams.get('token');

        if (!email || !token) {
            this.renderErrorMessage('Invalid confirmation link.');
            return;
        }

        try {
            const response = await fetch(`/api/verify_user?email=${encodeURIComponent(email)}&token=${encodeURIComponent(token)}`, {
                method: 'GET'
            });

            const result = await response.json();

            if (result.status === 'success') {
                this.renderSuccessMessage();
            } else {
                this.renderErrorMessage(result.message || 'Confirmation failed.');
            }
        } catch (error) {
            console.error('Error during email confirmation:', error);
            this.renderErrorMessage('An error occurred during confirmation.');
        }
    }

    renderSuccessMessage() {
        // Clear existing content
        while (this.mainContent.firstChild) {
            this.mainContent.removeChild(this.mainContent.firstChild);
        }

        // Create and append new elements
        const span1 = document.createElement('span');
        span1.className = 'w3-jumbo w3-hide-small w3-animate-bottom w3-animate-delay-1';
        span1.textContent = 'Confirmation Successful';
        this.mainContent.appendChild(span1);
        this.mainContent.appendChild(document.createElement('br'));

        const span2 = document.createElement('span');
        span2.className = 'w3-xxlarge w3-hide-large w3-hide-medium w3-animate-bottom w3-animate-delay-1';
        span2.textContent = 'Confirmation Successful';
        this.mainContent.appendChild(span2);
        this.mainContent.appendChild(document.createElement('br'));

        const span3 = document.createElement('span');
        span3.className = 'w3-xlarge w3-animate-bottom w3-animate-delay-2';
        span3.textContent = 'You have successfully registered, you will be redirected to the login page in ';
        const countdownSpan = document.createElement('span');
        countdownSpan.id = 'countdown';
        countdownSpan.textContent = '3';
        span3.appendChild(countdownSpan);
        span3.appendChild(document.createTextNode(' seconds.'));
        this.mainContent.appendChild(span3);

        this.startCountdown();
    }

    renderErrorMessage(message) {
        // Clear existing content
        while (this.mainContent.firstChild) {
            this.mainContent.removeChild(this.mainContent.firstChild);
        }

        // Create and append new elements
        const span1 = document.createElement('span');
        span1.className = 'w3-jumbo w3-hide-small w3-animate-bottom w3-animate-delay-1';
        span1.textContent = 'Confirmation Failed';
        this.mainContent.appendChild(span1);
        this.mainContent.appendChild(document.createElement('br'));

        const span2 = document.createElement('span');
        span2.className = 'w3-xxlarge w3-hide-large w3-hide-medium w3-animate-bottom w3-animate-delay-1';
        span2.textContent = 'Confirmation Failed';
        this.mainContent.appendChild(span2);
        this.mainContent.appendChild(document.createElement('br'));

        const span3 = document.createElement('span');
        span3.className = 'w3-xlarge w3-animate-bottom w3-animate-delay-2';
        span3.textContent = message;
        this.mainContent.appendChild(span3);
    }

    startCountdown() {
        let countdownElement = document.getElementById('countdown');
        let countdown = 3;
        const interval = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;
            if (countdown <= 0) {
                clearInterval(interval);
                window.location.href = '/login';
            }
        }, 1000);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const emailConfirmationPage = new EmailConfirmationPage('confirmationContentId');
    emailConfirmationPage.render();
});