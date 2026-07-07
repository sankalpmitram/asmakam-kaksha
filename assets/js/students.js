/**
 * students.js
 * Student management: list, search, filter by class, add, edit
 * (including photo upload), move to another class, delete.
 */
document.addEventListener('DOMContentLoaded', function () {
    var listEl = document.getElementById('studentsList');
    var emptyEl = document.getElementById('studentsEmptyState');
    var searchInput = document.getElementById('studentSearchInput');
    var classFilter = document.getElementById('studentClassFilter');
    var addBtn = document.getElementById('addStudentFab');
    var allClasses = [];

    function loadClassesIntoSelects() {
        return apiRequest('api/classes.php').then(function (res) {
            if (!res.success) return;
            allClasses = res.data;
            [classFilter].forEach(function (sel) {
                allClasses.forEach(function (c) {
                    var opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.name + (c.section ? ' - ' + c.section : '');
                    sel.appendChild(opt);
                });
            });
        });
    }

    function classNameById(id) {
        var c = allClasses.find(function (c) { return String(c.id) === String(id); });
        return c ? (c.name + (c.section ? ' - ' + c.section : '')) : '—';
    }

    function loadStudents() {
        var params = [];
        var q = searchInput.value.trim();
        var classId = classFilter.value;
        if (q) params.push('q=' + encodeURIComponent(q));
        if (classId) params.push('class_id=' + encodeURIComponent(classId));
        var url = 'api/students.php' + (params.length ? '?' + params.join('&') : '');
        apiRequest(url).then(function (res) {
            if (!res.success) return;
            renderStudents(res.data);
        });
    }

    function renderStudents(students) {
        listEl.querySelectorAll('.list-item').forEach(function (el) { el.remove(); });
        emptyEl.style.display = students.length === 0 ? 'block' : 'none';

        students.forEach(function (s) {
            var item = document.createElement('div');
            item.className = 'list-item';
            var photo = s.photo ? s.photo : 'assets/images/student-placeholder.svg';
            item.innerHTML =
                '<img class="list-item-avatar" src="' + escapeHtml(photo) + '" alt="फोटो">' +
                '<div class="list-item-body">' +
                    '<div class="list-item-title">' + escapeHtml(s.name) + '</div>' +
                    '<div class="list-item-sub">अनुक्रमांक: ' + escapeHtml(s.roll_number) + ' &middot; ' + escapeHtml(classNameById(s.class_id)) + '</div>' +
                '</div>' +
                '<div class="list-item-actions">' +
                    '<button class="move-btn" title="स्थानांतरित करें"><svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M4 12h13l-4-4 1.4-1.4L21 13l-6.6 6.4L13 18l4-4H4z"/></svg></button>' +
                    '<button class="edit-btn" title="संपादित करें"><svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button>' +
                    '<button class="delete-btn danger" title="हटाएं"><svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg></button>' +
                '</div>';
            item.querySelector('.edit-btn').addEventListener('click', function () { openStudentForm(s); });
            item.querySelector('.delete-btn').addEventListener('click', function () { deleteStudent(s); });
            item.querySelector('.move-btn').addEventListener('click', function () { openMoveForm(s); });
            listEl.appendChild(item);
        });
    }

    function fillClassSelect(select, selectedId) {
        select.innerHTML = '<option value="">कक्षा चुनें</option>';
        allClasses.forEach(function (c) {
            var opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.name + (c.section ? ' - ' + c.section : '');
            if (selectedId && String(selectedId) === String(c.id)) opt.selected = true;
            select.appendChild(opt);
        });
    }

    function openStudentForm(student) {
        var overlay = openModalFromTemplate('studentFormTemplate');
        var form = overlay.querySelector('#studentForm');
        var classSelect = overlay.querySelector('#studentFormClassSelect');
        var photoPreview = overlay.querySelector('#studentPhotoPreview');
        var photoInput = overlay.querySelector('#studentPhotoInput');

        overlay.querySelector('.modalTitleText').textContent = student ? 'छात्र संपादित करें' : 'छात्र जोड़ें';
        fillClassSelect(classSelect, student ? student.class_id : classFilter.value);

        if (student) {
            form.id.value = student.id;
            form.name.value = student.name;
            form.roll_number.value = student.roll_number;
            form.father_name.value = student.father_name || '';
            form.mother_name.value = student.mother_name || '';
            form.guardian_name.value = student.guardian_name || '';
            form.gender.value = student.gender || '';
            form.whatsapp_number.value = student.whatsapp_number || '';
            form.dob.value = student.dob || '';
            form.address.value = student.address || '';
            form.notes.value = student.notes || '';
            if (student.photo) photoPreview.src = student.photo;
        }

        photoInput.addEventListener('change', function () {
            var file = photoInput.files[0];
            if (file) {
                photoPreview.src = URL.createObjectURL(file);
            }
        });

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var id = form.id.value;

            if (!id) {
                // Create: multipart form covers everything including photo.
                var fd = new FormData(form);
                apiRequest('api/students.php', 'POST', fd, true).then(function (res) {
                    handleStudentSaveResult(res, overlay);
                });
            } else {
                // Update: send text fields as JSON, then upload photo separately if changed.
                var payload = {
                    name: form.name.value.trim(),
                    roll_number: form.roll_number.value.trim(),
                    class_id: classSelect.value,
                    father_name: form.father_name.value.trim(),
                    mother_name: form.mother_name.value.trim(),
                    guardian_name: form.guardian_name.value.trim(),
                    gender: form.gender.value,
                    whatsapp_number: form.whatsapp_number.value.trim(),
                    dob: form.dob.value,
                    address: form.address.value.trim(),
                    notes: form.notes.value.trim(),
                };
                apiRequest('api/students.php?id=' + id, 'PUT', payload).then(function (res) {
                    if (res.success && photoInput.files[0]) {
                        var fd = new FormData();
                        fd.append('id', id);
                        fd.append('photo', photoInput.files[0]);
                        apiRequest('api/student_photo.php', 'POST', fd, true).then(function () {
                            handleStudentSaveResult(res, overlay);
                        });
                    } else {
                        handleStudentSaveResult(res, overlay);
                    }
                });
            }
        });
    }

    function handleStudentSaveResult(res, overlay) {
        if (res.success) {
            showToast(res.message, 'success');
            closeModal(overlay);
            loadStudents();
        } else {
            showToast(res.message || 'त्रुटि हुई।', 'error');
        }
    }

    function openMoveForm(student) {
        var overlay = openModalFromTemplate('moveStudentTemplate');
        var form = overlay.querySelector('#moveStudentForm');
        var select = overlay.querySelector('#moveClassSelect');
        fillClassSelect(select, null);
        form.student_id.value = student.id;

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            apiRequest('api/students.php?action=move&id=' + student.id, 'POST', { class_id: select.value })
                .then(function (res) {
                    if (res.success) {
                        showToast(res.message, 'success');
                        closeModal(overlay);
                        loadStudents();
                    } else {
                        showToast(res.message || 'त्रुटि हुई।', 'error');
                    }
                });
        });
    }

    function deleteStudent(student) {
        if (!confirm('क्या आप वाकई "' + student.name + '" को हटाना चाहते हैं?')) return;
        apiRequest('api/students.php?id=' + student.id, 'DELETE').then(function (res) {
            showToast(res.message, res.success ? 'success' : 'error');
            if (res.success) loadStudents();
        });
    }

    searchInput.addEventListener('input', debounce(loadStudents, 300));
    classFilter.addEventListener('change', loadStudents);
    addBtn.addEventListener('click', function () { openStudentForm(null); });

    loadClassesIntoSelects().then(loadStudents);
});
