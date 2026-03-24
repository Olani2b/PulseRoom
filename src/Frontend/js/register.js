class Register {
    constructor() {
        this.init();
    }

    init() {
        const registerForm = document.getElementById('registerForm');
        registerForm.addEventListener('submit', (event) => this.handleRegister(event));
        this.ensureToastScript();
        this.ensureZxcvbnScript();
        const passwordField = document.getElementById('password');
        const confPasswordField = document.getElementById('conf_password');
        const showPasswordButton = document.getElementById('show_psw');
        const showConfPasswordButton = document.getElementById('show_conf');
        const passwordStrengthText = document.getElementById('zxcvbn-text');

        if (showPasswordButton) {
            showPasswordButton.addEventListener('click', () => this.togglePasswordVisibility('password', showPasswordButton));
        }

        if (showConfPasswordButton) {
            showConfPasswordButton.addEventListener('click', () => this.togglePasswordVisibility('conf_password', showConfPasswordButton));
        }
        if (passwordField) {
            passwordField.addEventListener('input', () => {
                this.checkPasswordStrength(passwordField.value,  passwordStrengthText);
                this.checkPasswordMatching(passwordField.value, confPasswordField.value)
            });
        }
        if (confPasswordField){
            confPasswordField.addEventListener('input', () =>
                this.checkPasswordMatching(passwordField.value, confPasswordField.value));
        }
    }

    async handleRegister(event) {
        event.preventDefault();
        const icon = document.getElementById('register-icon');
        const registerForm = event.target;
        const formData = new FormData(registerForm);
        const registerSubmit = document.querySelector('button[type="submit"]');

        try {
            this.changeIcon(icon);
            this.toggleButton(registerSubmit);
            const response = await fetch('/api/register', {
                method: 'POST',
                body: formData
            });
            if (response.ok) {
                const result = await response.json();
                this.changeIcon(icon);
                this.toggleButton(registerSubmit);
                showToast('success', result.message);
                this.showConfirmationPage();
            } else {
                const error = await response.json();
                this.toggleButton(registerSubmit);
                this.changeIcon(icon);
                showToast('error', error.message);
            }
        } catch (error) {
            this.toggleButton(registerSubmit);
            this.changeIcon(icon);
            showToast('error', error.message);
        }
    }

    toggleButton(button) {
        if (button.disabled) {
            button.disabled = false;
        } else {
            button.disabled = true;
        }
    }
    changeIcon(icon) {
        if (icon.classList.contains('fa-user-plus')) {
            icon.classList.remove('fa-user-plus');
            icon.classList.add('fa-spinner', 'fa-spin');
        } else {
            icon.classList.remove('fa-spinner', 'fa-spin');
            icon.classList.add('fa-user-plus');
        }
    }

    showConfirmationPage() {
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
        span1.textContent = 'The account has been created successfully.';
        div.appendChild(span1);
        div.appendChild(document.createElement('br'));

        const span2 = document.createElement('span');
        span2.className = 'w3-xxlarge w3-hide-large w3-hide-medium w3-animate-bottom';
        span2.textContent = 'The account has been created successfully.';
        div.appendChild(span2);
        div.appendChild(document.createElement('br'));

        const span3 = document.createElement('span');
        span3.className = 'w3-large w3-animate-bottom';
        span3.textContent = 'An email has been sent, confirm your account to access our services.';
        div.appendChild(span3);

        homeDiv.appendChild(div);
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

window.addEventListener('load', () => new Register());