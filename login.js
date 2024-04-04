$(document).ready(function() {
    function clearServerSession() {
        $.post('authentication/login.php', { action: 'logout' }, function(response) {});
    }

    async function login(username, password) {
        $('#login_button').prop('disabled', true);
        $('#username_input').prop('disabled', true);
        $('#password_button').prop('disabled', true);
        $('#guestLoginLink').prop('disabled', true);
        try {
            const response = await $.post('authentication/login.php', { action: 'login', username: username, password: password });
            if (response.status == 'success') {
                localStorage.setItem('username', response.data['username']);
                localStorage.setItem('role', response.data['role']);
                window.location.href = response.url;
            } else {
                alert('Login failed. Please check your username and password.');
            }
        } catch (error) {
            console.error('An error occurred while logging in:', error);
            alert('An error occurred while logging in. Please try again later.');
        } finally {
            $('#login_button').prop('disabled', false);
            $('#username_input').prop('disabled', false);
            $('#password_button').prop('disabled', false);
            $('#guestLoginLink').prop('disabled', false);
        }
    }

    document.getElementById('guestLoginLink').addEventListener('click', async function(e) {
        e.preventDefault();
        const username = 'guest';
        const password = 'guest';
        login(username, password);
    });

    $('#login-form').on('submit', function(e) {
        e.preventDefault();
        const username = $('#username_input').val();
        const password = $('#password_input').val();
        login(username, password);
    });

    function verifySession() {
        $.post('authentication/login.php', { action: 'verifySession' }, function(response) {
            if (response.status == 'success') {
                if (localStorage.getItem('username') == response.data['username'] && localStorage.getItem('role') == response.data['role']) {
                    window.location.href = response.url;
                    alert('You already have an active session. Please, logout if required.');
                } else {
                    localStorage.clear();
                    clearServerSession();
                }
            } else {
                localStorage.clear();
            }
        }, 'json');
    }

    verifySession();
});