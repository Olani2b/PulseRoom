document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('registerForm');
  const passwordInput = document.getElementById('password');
  const confirmInput = document.getElementById('conf_password');
  const passwordMessage = document.getElementById('passwordMessage');
  const strengthFill = document.getElementById('strengthFill');
  const strengthText = document.getElementById('strengthText');
  const submitButton = document.getElementById('registerSubmitBtn');

  const togglePassword = document.getElementById('togglePassword');
  const toggleConfirmPassword = document.getElementById(
    'toggleConfirmPassword'
  );

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

        <p class="register-success-text">
          We sent you a verification email. Please confirm your account
          before logging in.
        </p>

        <p class="register-countdown">
          Redirecting to login in
          <span id="registerCountdown">3</span> seconds.
        </p>

        <div class="register-actions">
          <a href="/login" class="btn btn-primary">Go to Login</a>
        </div>
      </div>
    `;

    let countdown = 3;
    const countdownElement = document.getElementById(
      'registerCountdown'
    );

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

  setToggle(togglePassword, passwordInput);
  setToggle(toggleConfirmPassword, confirmInput);

  passwordInput.addEventListener('input', () => {
    updatePasswordState();
    updateMatchState();
  });

  confirmInput.addEventListener('input', updateMatchState);

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

    const originalLabel = submitButton?.textContent || 'Register';

    if (submitButton) {
      submitButton.disabled = true;
      submitButton.textContent = 'Creating account...';
    }

    try {
      const response = await fetch('/api/register', {
        method: 'POST',
        body: new FormData(form)
      });

      const result = await response.json();

      if (!response.ok) {
        showToast(
          'error',
          result.message || 'Registration failed.'
        );

        if (submitButton) {
          submitButton.disabled = false;
          submitButton.textContent = originalLabel;
        }

        return;
      }

      showToast(
        'success',
        result.message || 'User registered successfully.'
      );

      renderSuccessState();
    } catch (error) {
      console.error(error);

      showToast('error', 'Unable to register right now.');

      if (submitButton) {
        submitButton.disabled = false;
        submitButton.textContent = originalLabel;
      }
    }
  });

  updatePasswordState();
});