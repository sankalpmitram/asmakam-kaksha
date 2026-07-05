/**
 * login.js
 * Handles the login form submission on pages/login.php.
 */
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('loginForm');
    var errorEl = document.getElementById('loginError');
    var btn = document.getElementById('loginBtn');

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        errorEl.textContent = '';
        btn.disabled = true;
        btn.textContent = 'प्रवेश हो रहा है...';

        var username = document.getElementById('username').value.trim();
        var password = document.getElementById('password').value;

        fetch('api/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username: username, password: password })
        })
            .then(function (res) { return res.json(); })
            .then(function (json) {
                if (json.success) {
                    window.location.href = json.data.redirect || 'index.php?page=dashboard';
                } else {
                    errorEl.textContent = json.message || 'लॉगिन असफल हुआ।';
                    btn.disabled = false;
                    btn.textContent = 'प्रवेश करें';
                }
            })
            .catch(function () {
                errorEl.textContent = 'नेटवर्क त्रुटि हुई। पुनः प्रयास करें।';
                btn.disabled = false;
                btn.textContent = 'प्रवेश करें';
            });
    });
});
