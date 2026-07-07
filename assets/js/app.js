/**
 * app.js
 * Shared helpers used across all pages: toast notifications, a fetch
 * wrapper for the JSON API, theme (light/dark) toggle, and small
 * modal/DOM utilities.
 */

/* ---------------- Toast ---------------- */
function showToast(message, type) {
    var toast = document.getElementById('toast');
    if (!toast) return;
    toast.textContent = message;
    toast.className = 'toast show' + (type ? ' ' + type : '');
    clearTimeout(window.__toastTimer);
    window.__toastTimer = setTimeout(function () {
        toast.className = 'toast';
    }, 2600);
}

/* ---------------- API helper ---------------- */
/**
 * apiRequest(url, method, body, isFormData)
 * Wraps fetch() for our JSON API. Returns a Promise resolving to
 * { success, message, data }.
 */
function apiRequest(url, method, body, isFormData) {
    method = method || 'GET';
    var options = { method: method, headers: {} };

    if (body !== undefined && body !== null) {
        if (isFormData) {
            options.body = body; // FormData sets its own Content-Type
        } else {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(body);
        }
    }

    return fetch(url, options)
        .then(function (res) {
            return res.json().then(function (json) {
                if (!res.ok && res.status === 401) {
                    showToast(json.message || 'सत्र समाप्त हो गया है।', 'error');
                    setTimeout(function () { window.location.href = 'index.php?page=login'; }, 1200);
                }
                return json;
            });
        })
        .catch(function () {
            showToast('नेटवर्क त्रुटि हुई। पुनः प्रयास करें।', 'error');
            return { success: false, message: 'नेटवर्क त्रुटि', data: null };
        });
}

/* ---------------- Theme toggle ---------------- */
(function () {
    var themeBtn = document.getElementById('themeToggleBtn');
    if (!themeBtn) return;
    themeBtn.addEventListener('click', function () {
        var isDark = document.body.classList.contains('theme-dark');
        var newTheme = isDark ? 'light' : 'dark';
        document.body.classList.remove('theme-dark', 'theme-light');
        document.body.classList.add('theme-' + newTheme);

        var fd = new FormData();
        fd.append('theme', newTheme);
        apiRequest('api/settings.php', 'POST', fd, true);
    });
})();

/* ---------------- Modal helpers ---------------- */
/**
 * openModalFromTemplate(templateId) -> appends the template's content to
 * #modalRoot and wires up any .close-modal-btn inside it. Returns the
 * inserted overlay element.
 */
function openModalFromTemplate(templateId) {
    var tpl = document.getElementById(templateId);
    if (!tpl) return null;
    var root = document.getElementById('modalRoot');
    var node = tpl.content.cloneNode(true);
    root.appendChild(node);
    var overlay = root.lastElementChild;

    overlay.querySelectorAll('.close-modal-btn').forEach(function (btn) {
        btn.addEventListener('click', function () { closeModal(overlay); });
    });
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) closeModal(overlay);
    });
    return overlay;
}

function closeModal(overlay) {
    if (overlay && overlay.parentNode) {
        overlay.parentNode.removeChild(overlay);
    }
}

/* ---------------- Small utilities ---------------- */
function debounce(fn, delay) {
    var timer = null;
    return function () {
        var args = arguments;
        clearTimeout(timer);
        timer = setTimeout(function () { fn.apply(null, args); }, delay || 300);
    };
}

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function todayStr() {
    var d = new Date();
    var m = String(d.getMonth() + 1).padStart(2, '0');
    var day = String(d.getDate()).padStart(2, '0');
    return d.getFullYear() + '-' + m + '-' + day;
}

function monthStr() {
    var d = new Date();
    var m = String(d.getMonth() + 1).padStart(2, '0');
    return d.getFullYear() + '-' + m;
}

var STATUS_LABELS = { present: 'उपस्थित', absent: 'अनुपस्थित', late: 'विलंब', half_day: 'आधा दिन' };
