
async function logoutUser() {
    try {
        const csrf = document.getElementById('csrf').value;
        const response = await fetch('/api/logout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({ csrf_token: csrf })
        });
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        const data = await response.json();
        if (data.status === 'success') {
            // Redirect to the login page after successful logout
            window.location.href = '/logout';
        } else {
            console.error('Logout failed:', data.message);
        }
    } catch (error) {
        console.error('There was a problem with the fetch operation:', error);
    }
}