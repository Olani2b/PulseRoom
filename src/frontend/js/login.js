document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('loginForm');
  const toggle = document.getElementById('togglePassword');
  const password = document.getElementById('password');
  const submitButton = document.getElementById('loginSubmitBtn');

  if (toggle && password) {
    toggle.addEventListener('click', () => {
      const isHidden = password.type === 'password';
      password.type = isHidden ? 'text' : 'password';
      toggle.textContent = isHidden ? 'Hide' : 'Show';
    });
  }

  if (!form) {
    return;
  }

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const originalLabel = submitButton?.textContent || 'Login';

    try {
      if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = 'Logging in...';
      }

      const response = await fetch('/api/login', {
        method: 'POST',
        body: new FormData(form)
      });
      const result = await response.json();

      if (!response.ok) {
        showToast('error', result.message || 'Login failed.');
        if (submitButton) {
          submitButton.disabled = false;
          submitButton.textContent = originalLabel;
        }
        return;
      }

      showToast('success', result.message || 'Login successful.');
      setTimeout(() => {
        window.location.href = '/dashboard';
      }, 1000);
    } catch (error) {
      showToast('error', 'Unable to log in right now.');
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.textContent = originalLabel;
      }
    }
  });
});
