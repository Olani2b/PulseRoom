class ResetPswPage {
    constructor(ResetPswPageId) {
        this.mainContent = document.getElementById(ResetPswPageId);
        this.init();
    }

    init() {
        const urlParams = new URLSearchParams(window.location.search);
        const email = urlParams.get('email');
        const token = urlParams.get('token');
        this.ensureToastScript();
        this.ensureZxcvbnScript();
        if (!email || !token) {
            this.renderErrorMessage();
        }
        const form = document.getElementById('resetpswForm');
        const newPasswordInput = document.getElementById('password');
        const confPasswordField = document.getElementById('conf_password');
        const passwordStrengthText = document.getElementById('zxcvbn-text');
        const showPasswordButton = document.getElementById('show_psw');
        const showConfPasswordButton = document.getElementById('show_conf');
    
        form.addEventListener('submit', (event) => this.sendForm(event));
        newPasswordInput.addEventListener('input', () => {
            this.checkPasswordStrength(newPasswordInput.value, passwordStrengthText);
            this.checkPasswordMatching(newPasswordInput.value, confPasswordField.value)
        });
        confPasswordField.addEventListener('input', () => this.checkPasswordMatching(newPasswordInput.value, confPasswordField.value));
        if (showPasswordButton) {
            showPasswordButton.addEventListener('click', () => this.togglePasswordVisibility('password', showPasswordButton));
        }
    
        if (showConfPasswordButton) {
            showConfPasswordButton.addEventListener('click', () => this.togglePasswordVisibility('conf_password', showConfPasswordButton));
        }
    }

    async sendForm(event) {
        event.preventDefault();
        const formData = new FormData(document.getElementById('resetpswForm'));

        try {
            const response = await fetch('/api/reset_pwd', {
                method: 'POST',
                body: formData
            });
            if (response.ok) {
                const result = await response.json();
                showToast('success', result.message);
                setTimeout(() => {
                this.renderSuccessMessage();}, 2000);
            } else {
                const error = await response.json();
                showToast('error', error.message);
            }
        } catch (error) {
            showToast('error', error.message);
            this.renderErrorMessage();
        }
    }
    togglePasswordVisibility(fieldId, button) {
        const passwordField = document.getElementById(fieldId);
        const icon = button.querySelector('i');

        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            icon.classList.add("fa-eye-slash");
            icon.classList.remove("fa-eye");
        } else {
            passwordField.type = 'password';
            icon.classList.add("fa-eye");
            icon.classList.remove("fa-eye-slash");
        }
    }

    checkPasswordStrength(password, text) {        
        const passwordField = document.getElementById('password');
        const meter = document.getElementById('password-strength-meter')

        const strength = [
            "Weakest",
            "Weak",
            "Weak",
            "Weak",
            "Strong"
        ];
     
        const w3_colors = [
            "w3-red",
            "w3-orange",
            "w3-yellow",
            "w3-yellow",
            "w3-green"
        ];

        const meterClasses = [
            "weakest",
            "weak",
            "fair",
            "fair",
            "strong"
        ];
    
        const colors = [
            "#ff6b6b",
            "#ffb304",
            "#f3ff00",
            "#f3ff00",
            "#22fa00"
        ];
        const result = zxcvbn(password);
        if (password === '') {
            text.innerText = '';
            text.style.color = '';
            w3_colors.forEach(color => passwordField.classList.remove(color));
            meter.value = 0;
            meter.className = '';
            return;
        }
    
        text.innerText = `${strength[result.score]}`;
        text.style.color = colors[result.score];
        meter.value = result.score;
        meter.className = meterClasses[result.score];
        w3_colors.forEach(w3_colors => passwordField.classList.remove(w3_colors));

        passwordField.classList.add(w3_colors[result.score]);
    }

   checkPasswordMatching(passwordField, confPasswordField) {
        const matchPsw = document.getElementById('match_psw');
        const confPasswordInput = document.getElementById('conf_password');
        if(matchPsw){
            if (passwordField === confPasswordField && passwordField !== '' && confPasswordField !== '') {
                matchPsw.style.color = '#22fa00';
                matchPsw.innerText = 'Passwords match';
                confPasswordInput.classList.remove('w3-red');
                confPasswordInput.classList.add('w3-green');
            } 
            else if(confPasswordField === '' || passwordField === '') {
                matchPsw.style.color = '';
                matchPsw.innerText = '';
                confPasswordInput.classList.remove('w3-red');
                confPasswordInput.classList.remove('w3-green');
            }
            else {
                matchPsw.style.color = '#ff6b6b';
                matchPsw.innerText = 'Passwords do not match';
                confPasswordInput.classList.add('w3-red');
                confPasswordInput.classList.remove('w3-green');
            }
        }
   }

    ensureZxcvbnScript() {
        // Check if zxcvbn.js is already loaded
        if (!document.querySelector('script[src="./Frontend/js/zxcvbn.js"]')) {
            var script = document.createElement('script');
            script.src = './Frontend/js/zxcvbn.js';
            document.head.appendChild(script);
        }
    }

    renderSuccessMessage() {

        while (this.mainContent.firstChild) {
            this.mainContent.removeChild(this.mainContent.firstChild);
        }
        const span1 = document.createElement('span');
        span1.className = 'w3-jumbo w3-hide-small w3-animate-bottom w3-animate-delay-1';
        span1.textContent = 'The password has been successfully reset.';
        this.mainContent.appendChild(span1);
        this.mainContent.appendChild(document.createElement('br'));

        const span2 = document.createElement('span');
        span2.className = 'w3-xxlarge w3-hide-large w3-hide-medium w3-animate-bottom w3-animate-delay-1';
        span2.textContent = 'The password has been successfully reset.';
        this.mainContent.appendChild(span2);
        this.mainContent.appendChild(document.createElement('br'));

        const span3 = document.createElement('span');
        span3.className = 'w3-xlarge w3-animate-bottom w3-animate-delay-2';
        span3.textContent = 'You can now perform the login to access our services.';
        this.mainContent.appendChild(span3);
    }

    renderErrorMessage() {

        while (this.mainContent.firstChild) {
            this.mainContent.removeChild(this.mainContent.firstChild);
        }
        const span1 = document.createElement('span');
        span1.className = 'w3-jumbo w3-hide-small w3-animate-bottom w3-animate-delay-1';
        span1.textContent = 'The password can not be reset.';
        this.mainContent.appendChild(span1);
        this.mainContent.appendChild(document.createElement('br'));

        const span2 = document.createElement('span');
        span2.className = 'w3-xxlarge w3-hide-large w3-hide-medium w3-animate-bottom w3-animate-delay-1';
        span2.textContent = 'The password can not be reset.';
        this.mainContent.appendChild(span2);
        this.mainContent.appendChild(document.createElement('br'));

        const span3 = document.createElement('span');
        span3.className = 'w3-xlarge w3-animate-bottom w3-animate-delay-2';
        span3.textContent = 'There was an error during the reset password.';
        this.mainContent.appendChild(span3);
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
    const page = new ResetPswPage('ResetPswContentId');
});