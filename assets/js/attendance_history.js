/**
 * attendance_history.js
 * Calendar view of attendance for a selected class + month.
 * Clicking a day opens a modal to view/edit/delete that day's record.
 */
document.addEventListener('DOMContentLoaded', function () {
    var classSelect = document.getElementById('historyClassSelect');
    var monthInput = document.getElementById('historyMonthInput');
    var calendarEl = document.getElementById('calendarView');
    var emptyState = document.getElementById('historyEmptyState');
    var currentStudents = [];

    monthInput.value = monthStr();

    apiRequest('api/classes.php').then(function (res) {
        if (!res.success) return;
        res.data.forEach(function (c) {
            var opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.name + (c.section ? ' - ' + c.section : '');
            classSelect.appendChild(opt);
        });
    });

    function loadCalendar() {
        var classId = classSelect.value;
        var month = monthInput.value;
        calendarEl.innerHTML = '';
        if (!classId || !month) {
            emptyState.style.display = 'block';
            return;
        }
        emptyState.style.display = 'none';

        Promise.all([
            apiRequest('api/students.php?class_id=' + classId),
            apiRequest('api/attendance.php?class_id=' + classId + '&month=' + month)
        ]).then(function (results) {
            currentStudents = results[0].success ? results[0].data : [];
            var records = results[1].success ? results[1].data : [];
            renderCalendar(month, records);
        });
    }

    function renderCalendar(month, records) {
        var recordByDate = {};
        records.forEach(function (r) { recordByDate[r.date] = r; });

        var parts = month.split('-');
        var year = parseInt(parts[0], 10);
        var mon = parseInt(parts[1], 10) - 1;
        var firstDay = new Date(year, mon, 1);
        var daysInMonth = new Date(year, mon + 1, 0).getDate();
        var startWeekday = firstDay.getDay();
        var todayIso = todayStr();

        var dayLabels = ['र', 'सो', 'मं', 'बु', 'गु', 'शु', 'श'];
        calendarEl.innerHTML = '';
        dayLabels.forEach(function (l) {
            var el = document.createElement('div');
            el.className = 'calendar-day-label';
            el.textContent = l;
            calendarEl.appendChild(el);
        });

        for (var i = 0; i < startWeekday; i++) {
            var blank = document.createElement('div');
            blank.className = 'calendar-day empty';
            calendarEl.appendChild(blank);
        }

        for (var d = 1; d <= daysInMonth; d++) {
            var dateStr = month + '-' + String(d).padStart(2, '0');
            var cell = document.createElement('div');
            cell.className = 'calendar-day';
            if (dateStr === todayIso) cell.classList.add('today');

            var rec = recordByDate[dateStr];
            var html = '<span>' + d + '</span>';
            if (rec) {
                cell.classList.add('has-record');
                var present = 0, total = 0;
                rec.records.forEach(function (r) {
                    total++;
                    if (r.status !== 'absent') present++;
                });
                var pct = total > 0 ? Math.round((present / total) * 100) : 0;
                html += '<span class="pct">' + pct + '%</span>';
                cell.addEventListener('click', function (recCapture) {
                    return function () { openDayModal(recCapture); };
                }(rec));
            }
            cell.innerHTML = html;
            calendarEl.appendChild(cell);
        }
    }

    function openDayModal(rec) {
        var overlay = openModalFromTemplate('dayRecordModalTemplate');
        overlay.querySelector('.dayModalTitle').textContent = 'दिनांक ' + rec.date + ' का विवरण';
        var body = overlay.querySelector('#dayRecordBody');

        var statusMap = {};
        rec.records.forEach(function (r) { statusMap[r.student_id] = r.status; });

        body.innerHTML = '';
        currentStudents.forEach(function (s) {
            var status = statusMap[s.id] || 'present';
            var row = document.createElement('div');
            row.className = 'attendance-row';
            row.dataset.studentId = s.id;
            row.dataset.status = status;
            row.innerHTML =
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
                });
            });
            body.appendChild(row);
        });

        overlay.querySelector('#saveDayRecordBtn').addEventListener('click', function () {
            var records = [];
            body.querySelectorAll('.attendance-row').forEach(function (row) {
                records.push({ student_id: parseInt(row.dataset.studentId, 10), status: row.dataset.status });
            });
            apiRequest('api/attendance.php?id=' + rec.id, 'PUT', { records: records }).then(function (res) {
                if (res.success) {
                    showToast('उपस्थिति सफलतापूर्वक अद्यतन हुई।', 'success');
                    closeModal(overlay);
                    loadCalendar();
                } else {
                    showToast(res.message || 'त्रुटि हुई।', 'error');
                }
            });
        });

        overlay.querySelector('#deleteDayRecordBtn').addEventListener('click', function () {
            if (!confirm('क्या आप वाकई इस दिन का उपस्थिति अभिलेख हटाना चाहते हैं?')) return;
            apiRequest('api/attendance.php?id=' + rec.id, 'DELETE').then(function (res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    closeModal(overlay);
                    loadCalendar();
                } else {
                    showToast(res.message || 'त्रुटि हुई।', 'error');
                }
            });
        });
    }

    classSelect.addEventListener('change', loadCalendar);
    monthInput.addEventListener('change', loadCalendar);

    emptyState.style.display = 'block';
});
