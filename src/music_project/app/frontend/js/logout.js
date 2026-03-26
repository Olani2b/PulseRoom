document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('logoutForm');
  const csrfInput = document.getElementById('logoutCsrf');
  const submitButton = document.getElementById('logoutSubmitBtn');
  const logoutMessage = document.getElementById('logoutMessage');
  const logoutSuccess = document.getElementById('logoutSuccess');
  const logoutCountdown = document.getElementById('logoutCountdown');

  if (!form || !csrfInput) {
    return;
  }

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    try {
      if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = 'Logging out...';
      }

      const response = await fetch('/api/logout', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({ csrf_token: csrfInput.value })
      });
      const result = await response.json();

      if (!response.ok) {
        showToast('error', result.message || 'Logout failed.');
        return;
      }

      showToast('success', result.message || 'Logout successful.');
      form.classList.add('hidden');
      if (logoutMessage) {
        logoutMessage.textContent = 'Your session has ended successfully.';
      }
      if (logoutSuccess) {
        logoutSuccess.classList.remove('hidden');
      }

      let countdown = 3;
      const timer = setInterval(() => {
        countdown -= 1;
        if (logoutCountdown) {
          logoutCountdown.textContent = String(countdown);
        }
        if (countdown <= 0) {
          clearInterval(timer);
          window.location.href = '/';
        }
      }, 1000);
    } catch (error) {
      showToast('error', 'Unable to log out right now.');
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.textContent = 'Logout';
      }
    }
  });
});
