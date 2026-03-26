document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('registerForm');
  const passwordInput = document.getElementById('password');
  const confirmInput = document.getElementById('conf_password');
  const passwordMessage = document.getElementById('passwordMessage');
  const strengthFill = document.getElementById('strengthFill');
  const strengthText = document.getElementById('strengthText');
  const registerMessage = document.getElementById('registerMessage');
  const submitButton = document.getElementById('registerSubmitBtn');

  const togglePassword = document.getElementById('togglePassword');
  const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');

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

  setToggle(togglePassword, passwordInput);
  setToggle(toggleConfirmPassword, confirmInput);

  passwordInput?.addEventListener('input', () => {
    updateStrength();
    updateMatchState();
  });
  confirmInput?.addEventListener('input', updateMatchState);

  if (!form) {
    return;
  }

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const originalLabel = submitButton?.textContent || 'Register';

    try {
      if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = 'Creating account...';
      }

      const response = await fetch('/api/register', {
        method: 'POST',
        body: new FormData(form)
      });
      const result = await response.json();

      if (!response.ok) {
        showToast('error', result.message || 'Registration failed.');
        if (submitButton) {
          submitButton.disabled = false;
          submitButton.textContent = originalLabel;
        }
        return;
      }

      showToast('success', result.message || 'User registered successfully.');
      renderSuccessState();
    } catch (error) {
      showToast('error', 'Unable to register right now.');
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.textContent = originalLabel;
      }
    }
  });

  const renderSuccessState = () => {
    const card = document.querySelector('.register-card');
    if (!card) {
      window.location.href = '/login';
      return;
    }

    card.innerHTML = `
      <div class="register-success">
        <span class="register-tag">Check Your Inbox</span>
        <h1>Registration successful</h1>
        <p class="register-success-text">We sent you a verification email. Please confirm your account before logging in.</p>
        <p class="register-countdown">Redirecting to login in <span id="registerCountdown">3</span> seconds.</p>
        <div class="register-actions">
          <a href="/login" class="btn btn-primary">Go to Login</a>
        </div>
      </div>
    `;

    let countdown = 3;
    const countdownElement = document.getElementById('registerCountdown');
    const timer = setInterval(() => {
      countdown -= 1;
      if (countdownElement) {
        countdownElement.textContent = String(countdown);
      }
      if (countdown <= 0) {
        clearInterval(timer);
        window.location.href = '/login';
      }
    }, 1000);
  };
});
