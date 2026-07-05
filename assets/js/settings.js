/**
 * settings.js
 * Loads and saves app settings, handles password change, theme toggle
 * buttons, backup download, restore upload, and application reset.
 */
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('settingsForm');
    var logoPreview = document.getElementById('logoPreview');
    var logoInput = document.getElementById('schoolLogoInput');
    var themeButtons = document.querySelectorAll('.theme-option-btn');

    function loadSettings() {
        apiRequest('api/settings.php').then(function (res) {
            if (!res.success) return;
            var s = res.data;
            document.getElementById('schoolNameInput').value = s.school_name || '';
            document.getElementById('teacherNameInput').value = s.teacher_name || '';
            document.getElementById('sessionInput').value = s.session || '';
            document.getElementById('attendanceTimeInput').value = s.default_attendance_time || '';
            document.getElementById('whatsappTemplateInput').value = s.whatsapp_template || '';
            if (s.school_logo) logoPreview.src = s.school_logo;
            setActiveTheme(s.theme || 'light');
        });
    }

    function setActiveTheme(theme) {
        themeButtons.forEach(function (btn) {
            btn.classList.toggle('active', btn.dataset.theme === theme);
        });
    }

    logoInput.addEventListener('change', function () {
        var file = logoInput.files[0];
        if (file) logoPreview.src = URL.createObjectURL(file);
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var fd = new FormData(form);
        var activeTheme = document.body.classList.contains('theme-dark') ? 'dark' : 'light';
        fd.append('theme', activeTheme);
        apiRequest('api/settings.php', 'POST', fd, true).then(function (res) {
            showToast(res.message, res.success ? 'success' : 'error');
        });
    });

    themeButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var theme = btn.dataset.theme;
            document.body.classList.remove('theme-dark', 'theme-light');
            document.body.classList.add('theme-' + theme);
            setActiveTheme(theme);
            var fd = new FormData();
            fd.append('theme', theme);
            apiRequest('api/settings.php', 'POST', fd, true);
        });
    });

    // ---- Password change ----
    var passwordForm = document.getElementById('passwordForm');
    passwordForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var fd = new FormData(passwordForm);
        fd.append('action', 'change_password');
        apiRequest('api/settings.php?action=change_password', 'POST', fd, true).then(function (res) {
            showToast(res.message, res.success ? 'success' : 'error');
            if (res.success) passwordForm.reset();
        });
    });

    // ---- Restore ----
    var restoreInput = document.getElementById('restoreFileInput');
    restoreInput.addEventListener('change', function () {
        var file = restoreInput.files[0];
        if (!file) return;
        if (!confirm('बैकअप पुनर्स्थापित करने से वर्तमान डेटा अधिलेखित हो जाएगा। जारी रखें?')) {
            restoreInput.value = '';
            return;
        }
        var fd = new FormData();
        fd.append('backup_zip', file);
        apiRequest('api/restore.php', 'POST', fd, true).then(function (res) {
            showToast(res.message, res.success ? 'success' : 'error');
            if (res.success) {
                setTimeout(function () { window.location.reload(); }, 1200);
            }
        });
    });

    // ---- Reset application ----
    document.getElementById('resetAppBtn').addEventListener('click', function () {
        if (!confirm('क्या आप वाकई सम्पूर्ण एप्लिकेशन रीसेट करना चाहते हैं? यह क्रिया पूर्ववत नहीं की जा सकती।')) return;
        if (!confirm('अंतिम पुष्टि: सभी कक्षाएँ, छात्र एवं उपस्थिति डेटा स्थायी रूप से हट जाएंगे। आगे बढ़ें?')) return;
        apiRequest('api/reset.php', 'POST', { confirm: true, keep_users: true }).then(function (res) {
            showToast(res.message, res.success ? 'success' : 'error');
            if (res.success) {
                setTimeout(function () { window.location.href = 'index.php?page=dashboard'; }, 1200);
            }
        });
    });

    loadSettings();
});
