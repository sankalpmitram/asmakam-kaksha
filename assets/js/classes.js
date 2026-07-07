/**
 * classes.js
 * Class management: list, live search, add, edit, delete.
 */
document.addEventListener('DOMContentLoaded', function () {
    var listEl = document.getElementById('classesList');
    var emptyEl = document.getElementById('classesEmptyState');
    var searchInput = document.getElementById('classSearchInput');
    var addBtn = document.getElementById('addClassFab');

    function loadClasses(q) {
        var url = 'api/classes.php' + (q ? '?q=' + encodeURIComponent(q) : '');
        apiRequest(url).then(function (res) {
            if (!res.success) return;
            renderClasses(res.data);
        });
    }

    function renderClasses(classes) {
        listEl.querySelectorAll('.list-item').forEach(function (el) { el.remove(); });
        emptyEl.style.display = classes.length === 0 ? 'block' : 'none';

        classes.forEach(function (c) {
            var item = document.createElement('div');
            item.className = 'list-item';
            item.innerHTML =
                '<div class="list-item-body">' +
                    '<div class="list-item-title">' + escapeHtml(c.name) + (c.section ? ' - ' + escapeHtml(c.section) : '') + '</div>' +
                    '<div class="list-item-sub">' + (c.session ? escapeHtml(c.session) + ' &middot; ' : '') +
                        '<span class="badge">' + c.student_count + ' छात्र</span></div>' +
                '</div>' +
                '<div class="list-item-actions">' +
                    '<button class="edit-btn" title="संपादित करें"><svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button>' +
                    '<button class="delete-btn danger" title="हटाएं"><svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg></button>' +
                '</div>';
            item.querySelector('.edit-btn').addEventListener('click', function () { openClassForm(c); });
            item.querySelector('.delete-btn').addEventListener('click', function () { deleteClass(c); });
            listEl.appendChild(item);
        });
    }

    function openClassForm(cls) {
        var overlay = openModalFromTemplate('classFormTemplate');
        var form = overlay.querySelector('#classForm');
        overlay.querySelector('.modalTitleText').textContent = cls ? 'कक्षा संपादित करें' : 'कक्षा जोड़ें';

        if (cls) {
            form.id.value = cls.id;
            form.name.value = cls.name;
            form.section.value = cls.section || '';
            form.session.value = cls.session || '';
            form.teacher.value = cls.teacher || '';
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var payload = {
                name: form.name.value.trim(),
                section: form.section.value.trim(),
                session: form.session.value.trim(),
                teacher: form.teacher.value.trim(),
            };
            var id = form.id.value;
            var req = id
                ? apiRequest('api/classes.php?id=' + id, 'PUT', payload)
                : apiRequest('api/classes.php', 'POST', payload);

            req.then(function (res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    closeModal(overlay);
                    loadClasses(searchInput.value.trim());
                } else {
                    showToast(res.message || 'त्रुटि हुई।', 'error');
                }
            });
        });
    }

    function deleteClass(cls) {
        if (!confirm('क्या आप वाकई "' + cls.name + '" कक्षा हटाना चाहते हैं?')) return;
        apiRequest('api/classes.php?id=' + cls.id, 'DELETE').then(function (res) {
            showToast(res.message, res.success ? 'success' : 'error');
            if (res.success) loadClasses(searchInput.value.trim());
        });
    }

    searchInput.addEventListener('input', debounce(function () {
        loadClasses(searchInput.value.trim());
    }, 300));

    addBtn.addEventListener('click', function () { openClassForm(null); });

    loadClasses('');
});
