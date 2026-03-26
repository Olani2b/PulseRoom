const menuToggle = document.getElementById('menuToggle');
const navLinks = document.getElementById('navLinks');

if (menuToggle && navLinks) {
  menuToggle.addEventListener('click', () => {
    navLinks.classList.toggle('open');
  });
}

window.showToast = function showToast(type, message) {
  const toast = document.getElementById('toast');
  if (!toast) {
    return;
  }

  toast.textContent = message;
  toast.className = `toast toast-${type}`;
  requestAnimationFrame(() => toast.classList.add('visible'));

  clearTimeout(showToast.timeoutId);
  showToast.timeoutId = setTimeout(() => {
    toast.classList.remove('visible');
  }, 3200);
};

window.getQueryParam = function getQueryParam(name) {
  return new URLSearchParams(window.location.search).get(name);
};
