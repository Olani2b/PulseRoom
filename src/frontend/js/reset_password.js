document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('resetForm');
  const passwordInput = document.getElementById('new_password');
  const confirmInput = document.getElementById('conf_new_password');
  const passwordMessage = document.getElementById('resetPasswordMessage');
  const strengthFill = document.getElementById('resetStrengthFill');
  const strengthText = document.getElementById('resetStrengthText');

  const toggleNew = document.getElementById('toggleNewPassword');
  const toggleConfirm = document.getElementById('toggleConfirmNewPassword');

  const setToggle = (button, input) => {
    if (!button || !input) {
      return;
    }
    button.addEventListener('click', () => {
      const isHidden = input.type === 'password';
      input.type = isHidden ? 'text' : 'password';
      button.textContent = isHidden ? 'Hide' : 'Show';
    });
  };

  const updateStrength = () => {
    const value = passwordInput.value;
    const hasLower = /[a-z]/.test(value);
    const hasUpper = /[A-Z]/.test(value);
    const hasNumber = /[0-9]/.test(value);
    const hasSpecial = /[^A-Za-z0-9]/.test(value);
    const categories = [hasLower, hasUpper, hasNumber, hasSpecial].filter(Boolean).length;
    const meetsMinimum = value.length >= 8 && hasLower && hasUpper && hasNumber;

    let score = 0;
    if (value.length === 0) {
      score = 0;
    } else if (!meetsMinimum) {
      score = value.length >= 8 || categories >= 2 ? 1 : 0;
    } else if (value.length >= 14 && categories >= 4) {
      score = 3;
    } else if (value.length >= 10 && categories >= 3) {
      score = 2;
    } else {
      score = 2;
    }

    const widths = ['0%', '25%', '50%', '75%', '75%'];
    const labels = [
      'Use 8+ characters with uppercase, lowercase, and a number. Avoid common words.',
      'Weak password',
      'Okay password',
      'Good password',
      'Good password'
    ];
    const fillColors = ['#253244', '#d8868b', '#c9a46a', '#8fc0c9', '#8fc0c9'];
    const textClasses = ['', 'too-weak', 'weak', 'good', 'good'];

    strengthFill.style.width = widths[score];
    strengthFill.style.background = fillColors[score];
    strengthText.textContent = labels[score];
    strengthText.classList.remove('too-weak', 'weak', 'good', 'strong');
    if (textClasses[score]) {
      strengthText.classList.add(textClasses[score]);
    }
  };

  const updateMatchState = () => {
    if (!passwordInput.value && !confirmInput.value) {
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

  passwordInput?.addEventListener('input', () => {
    updateStrength();
    updateMatchState();
  });
  confirmInput?.addEventListener('input', updateMatchState);

  if (!form) {
    return;
  }

  const email = form.querySelector('input[name="email"]').value;
  const token = form.querySelector('input[name="token"]').value;
  if (!email || !token) {
    showToast('error', 'Invalid reset link.');
    form.querySelector('button[type="submit"]').disabled = true;
    return;
  }

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

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
});
