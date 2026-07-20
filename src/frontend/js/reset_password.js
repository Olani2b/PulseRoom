document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('resetForm');
  const passwordInput = document.getElementById('new_password');
  const confirmInput = document.getElementById('conf_new_password');
  const passwordMessage = document.getElementById('resetPasswordMessage');
  const strengthFill = document.getElementById('resetStrengthFill');
  const strengthText = document.getElementById('resetStrengthText');
  const submitButton = form?.querySelector('button[type="submit"]');

  const toggleNew = document.getElementById('toggleNewPassword');
  const toggleConfirm = document.getElementById('toggleConfirmNewPassword');

  if (
    !form ||
    !passwordInput ||
    !confirmInput ||
    !passwordMessage ||
    !strengthFill ||
    !strengthText
  ) {
    return;
  }

  const setToggle = (button, input) => {
    if (!button) {
      return;
    }

    button.addEventListener('click', (event) => {
      event.preventDefault();

      const isHidden = input.type === 'password';
      input.type = isHidden ? 'text' : 'password';
      button.textContent = isHidden ? 'Hide' : 'Show';
    });
  };

  const getPasswordError = (password) => {
    if (password.length < 8) {
      return 'Password must be at least 8 characters long.';
    }

    if (!/[A-Z]/.test(password)) {
      return 'Password must contain at least one uppercase letter.';
    }

    if (!/[a-z]/.test(password)) {
      return 'Password must contain at least one lowercase letter.';
    }

    if (!/[0-9]/.test(password)) {
      return 'Password must contain at least one number.';
    }

    if (!/[^A-Za-z0-9\s]/.test(password)) {
      return 'Password must contain at least one special character.';
    }

    return null;
  };

  const updatePasswordState = () => {
    const password = passwordInput.value;

    strengthText.classList.remove('too-weak', 'weak', 'good', 'strong');

    if (!password) {
      strengthFill.style.width = '0%';
      strengthFill.style.backgroundColor = '#253244';
      strengthText.textContent =
        'Use 8+ characters with uppercase, lowercase, a number, and a special character.';
      return;
    }

    const passwordError = getPasswordError(password);

    if (passwordError) {
      strengthFill.style.width = '35%';
      strengthFill.style.backgroundColor = '#d8868b';
      strengthText.textContent = passwordError;
      strengthText.classList.add('too-weak');
      return;
    }

    strengthFill.style.width = '100%';
    strengthFill.style.backgroundColor = '#8fc0c9';
    strengthText.textContent = 'Basic password requirements satisfied.';
    strengthText.classList.add('good');
  };

  const updateMatchState = () => {
    if (!confirmInput.value) {
      passwordMessage.textContent = '';
      return;
    }

    if (passwordInput.value === confirmInput.value) {
      passwordMessage.textContent = 'Passwords match.';
      passwordMessage.style.color = '#6fd6a4';
    } else {
      passwordMessage.textContent = 'Passwords do not match.';
      passwordMessage.style.color = '#d8868b';
    }
  };

  setToggle(toggleNew, passwordInput);
  setToggle(toggleConfirm, confirmInput);

  passwordInput.addEventListener('input', () => {
    updatePasswordState();
    updateMatchState();
  });
  confirmInput.addEventListener('input', updateMatchState);

  const email = getQueryParam('email');
  const token = getQueryParam('token');
  if (!email || !token) {
    showToast('error', 'Invalid reset link.');
    if (submitButton) {
      submitButton.disabled = true;
    }
    return;
  }

  form.querySelector('input[name="email"]').value = email;
  form.querySelector('input[name="token"]').value = token;
  window.history.replaceState({}, document.title, '/reset_password');

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const passwordError = getPasswordError(passwordInput.value);

    if (passwordError) {
      showToast('error', passwordError);
      passwordInput.focus();
      return;
    }

    if (passwordInput.value !== confirmInput.value) {
      showToast('error', 'Passwords do not match.');
      confirmInput.focus();
      return;
    }

    try {
      const response = await fetch('/api/reset_pwd', {
        method: 'POST',
        body: new FormData(form)
      });
      const result = await response.json();

      if (!response.ok) {
        showToast('error', result.message || 'Password reset failed.');
        return;
      }

      showToast('success', result.message || 'Password reset successfully.');
      setTimeout(() => {
        window.location.href = '/login';
      }, 1400);
    } catch (error) {
      showToast('error', 'Unable to reset the password right now.');
    }
  });

  updatePasswordState();
});
