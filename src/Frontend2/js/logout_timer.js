
let countdown = 3;
const countdownElement = document.getElementById('countdown');

const interval = setInterval(() => {
    countdown--;
    countdownElement.textContent = countdown;
    if (countdown === 0) {
        clearInterval(interval);
        window.location.href = '/';
    }
}, 1000);
