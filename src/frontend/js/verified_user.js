document.addEventListener('DOMContentLoaded', async () => {
  const title = document.getElementById('verificationTitle');
  const message = document.getElementById('verificationMessage');
  const email = getQueryParam('email');
  const token = getQueryParam('token');

  if (!email || !token) {
    title.textContent = 'Confirmation Failed';
    message.textContent = 'Invalid confirmation link.';
    return;
  }

  window.history.replaceState({}, document.title, '/verify_user');

  try {
    const response = await fetch('/api/verify_user', {
      method: 'POST',
      body: new URLSearchParams({ email, token })
    });
    const result = await response.json();

    if (!response.ok || result.status !== 'success') {
      title.textContent = 'Confirmation Failed';
      message.textContent = result.message || 'Confirmation failed.';
      return;
    }

    title.textContent = 'Confirmation Successful';
    message.textContent = 'Your account is verified. Redirecting you to login.';
    setTimeout(() => {
      window.location.href = '/login?verified=1';
    }, 1800);
  } catch (error) {
    title.textContent = 'Confirmation Failed';
    message.textContent = 'An error occurred during confirmation.';
  }
});
