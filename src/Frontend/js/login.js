window.addEventListener('load', init);

function init() {
    const loginForm = document.getElementById('loginForm');
    loginForm.addEventListener('submit', handleLogin);
    const showPasswordButton = document.getElementById('show_psw');
    if (showPasswordButton) {
        showPasswordButton.addEventListener('click', () => togglePasswordVisibility('password', showPasswordButton));
    }
}

function togglePasswordVisibility(fieldId, button) {
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


async function handleLogin(event){
    event.preventDefault();
    const loginForm = event.target;
    const formData = new FormData(loginForm);

    try {
        const response = await fetch('/api/login', {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            const result = await response.json();
            //console.log('Login successful:', result);
            //alert(result.message);
            showToast('success', result.message);
            setTimeout(() => {
                window.location.href = '/dashboard';
            }, 1500);
            // Redirecting to the home page
        } else {
            const error = await response.json();
            showToast('error', error.message);
            // Handle login error (e.g., display error message)
        }
    } catch (error) {
        showToast('error', error.message);

    }
}
