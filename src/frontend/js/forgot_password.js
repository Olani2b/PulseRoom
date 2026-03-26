document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('forgotForm');
  const submitButton = document.getElementById('forgotSubmitBtn');
  if (!form) {
    return;
  }

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const originalLabel = submitButton?.textContent || 'Send Reset Request';

    try {
      if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = 'Sending reset email...';
      }

      const response = await fetch('/api/forgot_pwd', {
        method: 'POST',
        body: new FormData(form)
      });
      const result = await response.json();

      if (!response.ok) {
        showToast('error', result.message || 'Failed to initiate password reset.');
        if (submitButton) {
          submitButton.disabled = false;
          submitButton.textContent = originalLabel;
        }
        return;
      }

      showToast('success', result.message || 'Reset email sent.');
      renderSuccessState();
    } catch (error) {
      showToast('error', 'Unable to send the reset email right now.');
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.textContent = originalLabel;
      }
    }
  });

  const renderSuccessState = () => {
    const card = document.querySelector('.forgot-card');
    if (!card) {
      window.location.href = '/login';
      return;
    }

    card.innerHTML = `
      <div class="forgot-success">
        <span class="forgot-tag">Check Your Inbox</span>
        <h1>Reset email sent</h1>
        <p class="forgot-success-text">If the address exists in our system, you will receive a password reset link shortly.</p>
        <p class="forgot-countdown">Redirecting to login in <span id="forgotCountdown">3</span> seconds.</p>
        <div class="forgot-actions">
          <a href="/login" class="btn btn-primary">Go to Login</a>
        </div>
      </div>
    `;

    let countdown = 3;
    const countdownElement = document.getElementById('forgotCountdown');
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
