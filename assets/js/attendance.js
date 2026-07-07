/**
 * attendance.js
 * Take / edit attendance for a selected class + date.
 */
document.addEventListener('DOMContentLoaded', function () {
    var classSelect = document.getElementById('attendanceClassSelect');
    var dateInput = document.getElementById('attendanceDateInput');
    var listEl = document.getElementById('attendanceStudentList');
    var controlsEl = document.getElementById('attendanceControls');
    var countLabel = document.getElementById('attendanceCountLabel');
    var saveBar = document.getElementById('attendanceSaveBar');
    var saveBtn = document.getElementById('saveAttendanceBtn');
    var markAllBtn = document.getElementById('markAllPresentBtn');
    var emptyState = document.getElementById('attendanceEmptyState');
    var statusBanner = document.getElementById('attendanceStatusBanner');
    var whatsappSection = document.getElementById('whatsappSection');
    var whatsappList = document.getElementById('whatsappList');

    var currentStudents = [];
    var currentRecordId = null; // set if editing an existing record
    var settings = null;

    dateInput.value = todayStr();

    apiRequest('api/settings.php').then(function (res) {
        if (res.success) settings = res.data;
    });

    apiRequest('api/classes.php').then(function (res) {
        if (!res.success) return;
        res.data.forEach(function (c) {
            var opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.name + (c.section ? ' - ' + c.section : '');
            classSelect.appendChild(opt);
        });
    });

    function resetView() {
        listEl.innerHTML = '';
        controlsEl.style.display = 'none';
        saveBar.style.display = 'none';
        whatsappSection.style.display = 'none';
        statusBanner.style.display = 'none';
        currentRecordId = null;
    }

    function loadAttendance() {
        resetView();
        var classId = classSelect.value;
        var date = dateInput.value;
        if (!classId || !date) {
            emptyState.style.display = 'block';
            return;
        }
        emptyState.style.display = 'none';

        Promise.all([
            apiRequest('api/students.php?class_id=' + classId),
            apiRequest('api/attendance.php?class_id=' + classId + '&date=' + date)
        ]).then(function (results) {
            var studentsRes = results[0];
            var attendanceRes = results[1];
            if (!studentsRes.success) return;
            currentStudents = studentsRes.data;

            var existingRecord = attendanceRes.success ? attendanceRes.data : null;
            var statusMap = {};
            if (existingRecord) {
                currentRecordId = existingRecord.id;
                existingRecord.records.forEach(function (r) { statusMap[r.student_id] = r.status; });
                statusBanner.textContent = 'इस तिथि की उपस्थिति पहले से दर्ज है। परिवर्तन करके "सहेजें" दबाएँ।';
                statusBanner.style.display = 'block';
            }

            if (currentStudents.length === 0) {
                listEl.innerHTML = '<div class="empty-state"><p>इस कक्षा में कोई छात्र नहीं है।</p></div>';
                return;
            }

            renderStudentRows(statusMap);
            controlsEl.style.display = 'flex';
            saveBar.style.display = 'block';
        });
    }

    function renderStudentRows(statusMap) {
        listEl.innerHTML = '';
        currentStudents.forEach(function (s) {
            var status = statusMap[s.id] || 'present';
            var row = document.createElement('div');
            row.className = 'attendance-row';
            row.dataset.studentId = s.id;
            row.dataset.status = status;
            var photo = s.photo ? s.photo : 'assets/images/student-placeholder.svg';
            row.innerHTML =
                '<img class="attendance-row-avatar" src="' + escapeHtml(photo) + '" alt="">' +
                '<div class="attendance-row-info">' +
                    '<div class="attendance-row-name">' + escapeHtml(s.name) + '</div>' +
                    '<div class="attendance-row-roll">अनुक्रमांक: ' + escapeHtml(s.roll_number) + '</div>' +
                '</div>' +
                '<div class="status-btn-group">' +
                    '<button class="status-btn" data-status="present">उप.</button>' +
                    '<button class="status-btn" data-status="absent">अनु.</button>' +
                    '<button class="status-btn" data-status="late">विलं.</button>' +
                    '<button class="status-btn" data-status="half_day">आधा</button>' +
                '</div>';
            row.querySelectorAll('.status-btn').forEach(function (btn) {
                if (btn.dataset.status === status) btn.classList.add('active');
                btn.addEventListener('click', function () {
                    row.dataset.status = btn.dataset.status;
                    row.querySelectorAll('.status-btn').forEach(function (b) { b.classList.remove('active'); });
                    btn.classList.add('active');
                    updateCountLabel();
                });
            });
            listEl.appendChild(row);
        });
        updateCountLabel();
    }

    function updateCountLabel() {
        var rows = listEl.querySelectorAll('.attendance-row');
        var present = 0, absent = 0;
        rows.forEach(function (r) {
            if (r.dataset.status === 'absent') absent++;
            else present++;
        });
        countLabel.textContent = 'कुल: ' + rows.length + ' | उपस्थित: ' + present + ' | अनुपस्थित: ' + absent;
    }

    markAllBtn.addEventListener('click', function () {
        listEl.querySelectorAll('.attendance-row').forEach(function (row) {
            row.dataset.status = 'present';
            row.querySelectorAll('.status-btn').forEach(function (b) {
                b.classList.toggle('active', b.dataset.status === 'present');
            });
        });
        updateCountLabel();
    });

    saveBtn.addEventListener('click', function () {
        var records = [];
        listEl.querySelectorAll('.attendance-row').forEach(function (row) {
            records.push({ student_id: parseInt(row.dataset.studentId, 10), status: row.dataset.status });
        });

        var payload = { class_id: classSelect.value, date: dateInput.value, records: records };
        var req = currentRecordId
            ? apiRequest('api/attendance.php?id=' + currentRecordId, 'PUT', payload)
            : apiRequest('api/attendance.php', 'POST', payload);

        req.then(function (res) {
            if (!res.success) {
                showToast(res.message || 'त्रुटि हुई।', 'error');
                return;
            }
            showToast('उपस्थिति सफलतापूर्वक सहेजी गई।', 'success');
            currentRecordId = res.data.id;
            statusBanner.style.display = 'none';
            renderWhatsappSection(records);
        });
    });

    function renderWhatsappSection(records) {
        var absentStudents = records
            .filter(function (r) { return r.status === 'absent'; })
            .map(function (r) { return currentStudents.find(function (s) { return s.id === r.student_id; }); })
            .filter(Boolean);

        if (absentStudents.length === 0) {
            whatsappSection.style.display = 'none';
            return;
        }

        whatsappList.innerHTML = '';
        var template = (settings && settings.whatsapp_template) ||
            'आदरणीय अभिभावक,\n\nआपका पुत्र/पुत्री {student_name} आज दिनांक {date} को विद्यालय में अनुपस्थित रहे/रही।\n\nकृपया अनुपस्थिति का कारण कक्षा शिक्षक को अवश्य बताएं।\n\nधन्यवाद।';

        absentStudents.forEach(function (s) {
            var row = document.createElement('div');
            row.className = 'list-item';
            var hasPhone = s.whatsapp_number && s.whatsapp_number.length > 0;
            row.innerHTML =
                '<div class="list-item-body">' +
                    '<div class="list-item-title">' + escapeHtml(s.name) + '</div>' +
                    '<div class="list-item-sub">' + (hasPhone ? escapeHtml(s.whatsapp_number) : 'कोई व्हाट्सएप संख्या नहीं') + '</div>' +
                '</div>' +
                (hasPhone
                    ? '<button class="btn btn-whatsapp btn-sm">भेजें</button>'
                    : '<span class="badge">—</span>');

            if (hasPhone) {
                row.querySelector('button').addEventListener('click', function () {
                    var message = template
                        .replace(/{student_name}/g, s.name)
                        .replace(/{date}/g, dateInput.value);
                    var phone = s.whatsapp_number.replace(/[^0-9]/g, '');
                    if (phone.length === 10) phone = '91' + phone;
                    var url = 'https://wa.me/' + phone + '?text=' + encodeURIComponent(message);
                    window.open(url, '_blank');
                });
            }
            whatsappList.appendChild(row);
        });

        whatsappSection.style.display = 'block';
    }

    classSelect.addEventListener('change', loadAttendance);
    dateInput.addEventListener('change', loadAttendance);

    emptyState.style.display = 'block';
});
