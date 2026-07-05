/**
 * reports.js
 * Reports hub: tab switching + data loading/rendering for
 * today / monthly / class / student reports, plus PDF & Excel export.
 */
document.addEventListener('DOMContentLoaded', function () {
    var tabBtns = document.querySelectorAll('.tab-btn');
    var panels = document.querySelectorAll('.tab-panel');
    var allClasses = [];

    tabBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            tabBtns.forEach(function (b) { b.classList.remove('active'); });
            panels.forEach(function (p) { p.classList.remove('active'); });
            btn.classList.add('active');
            document.querySelector('.tab-panel[data-panel="' + btn.dataset.tab + '"]').classList.add('active');
        });
    });

    // ---- Populate class selects ----
    var classSelects = [
        document.getElementById('monthlyReportClass'),
        document.getElementById('classReportClass'),
        document.getElementById('studentReportClass'),
    ];
    apiRequest('api/classes.php').then(function (res) {
        if (!res.success) return;
        allClasses = res.data;
        classSelects.forEach(function (sel) {
            allClasses.forEach(function (c) {
                var opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.name + (c.section ? ' - ' + c.section : '');
                sel.appendChild(opt);
            });
        });
    });

    // ---- Today's report ----
    var todayDate = document.getElementById('todayReportDate');
    var todayResult = document.getElementById('todayReportResult');
    todayDate.value = todayStr();

    function loadTodayReport() {
        apiRequest('api/reports.php?type=today&date=' + todayDate.value).then(function (res) {
            if (!res.success) return;
            var rows = res.data.rows;
            if (rows.length === 0) {
                todayResult.innerHTML = '<div class="empty-state"><p>इस तिथि हेतु कोई अभिलेख नहीं है।</p></div>';
                return;
            }
            var html = '<div class="report-table-wrap"><table class="data-table"><thead><tr><th>कक्षा</th><th>अनु.</th><th>नाम</th><th>स्थिति</th></tr></thead><tbody>';
            rows.forEach(function (r) {
                html += '<tr><td>' + escapeHtml(r.class_name) + '</td><td>' + escapeHtml(r.roll_number) + '</td><td>' + escapeHtml(r.student_name) + '</td><td>' + (STATUS_LABELS[r.status] || r.status) + '</td></tr>';
            });
            html += '</tbody></table></div>';
            todayResult.innerHTML = html;
        });
    }
    todayDate.addEventListener('change', loadTodayReport);
    document.getElementById('todayExportPdf').addEventListener('click', function () {
        window.open('print_report.php?type=today&date=' + todayDate.value, '_blank');
    });
    document.getElementById('todayExportExcel').addEventListener('click', function () {
        window.location.href = 'api/export_excel.php?type=today&date=' + todayDate.value;
    });
    loadTodayReport();

    // ---- Monthly report ----
    var monthlyClass = document.getElementById('monthlyReportClass');
    var monthlyMonth = document.getElementById('monthlyReportMonth');
    var monthlyResult = document.getElementById('monthlyReportResult');
    monthlyMonth.value = monthStr();

    function loadMonthlyReport() {
        var url = 'api/reports.php?type=monthly&month=' + monthlyMonth.value + (monthlyClass.value ? '&class_id=' + monthlyClass.value : '');
        apiRequest(url).then(function (res) {
            if (!res.success) return;
            renderSummaryTable(monthlyResult, res.data.students);
        });
    }
    monthlyClass.addEventListener('change', loadMonthlyReport);
    monthlyMonth.addEventListener('change', loadMonthlyReport);
    document.getElementById('monthlyExportPdf').addEventListener('click', function () {
        window.open('print_report.php?type=monthly&month=' + monthlyMonth.value + (monthlyClass.value ? '&class_id=' + monthlyClass.value : ''), '_blank');
    });
    document.getElementById('monthlyExportExcel').addEventListener('click', function () {
        window.location.href = 'api/export_excel.php?type=monthly&month=' + monthlyMonth.value + (monthlyClass.value ? '&class_id=' + monthlyClass.value : '');
    });

    // ---- Class report ----
    var classReportClass = document.getElementById('classReportClass');
    var classResult = document.getElementById('classReportResult');

    function loadClassReport() {
        if (!classReportClass.value) { classResult.innerHTML = ''; return; }
        apiRequest('api/reports.php?type=class&class_id=' + classReportClass.value).then(function (res) {
            if (!res.success) return;
            renderSummaryTable(classResult, res.data.students);
        });
    }
    classReportClass.addEventListener('change', loadClassReport);
    document.getElementById('classExportPdf').addEventListener('click', function () {
        if (!classReportClass.value) return;
        window.open('print_report.php?type=class&class_id=' + classReportClass.value, '_blank');
    });
    document.getElementById('classExportExcel').addEventListener('click', function () {
        if (!classReportClass.value) return;
        window.location.href = 'api/export_excel.php?type=class&class_id=' + classReportClass.value;
    });

    // ---- Student report ----
    var studentReportClass = document.getElementById('studentReportClass');
    var studentReportStudent = document.getElementById('studentReportStudent');
    var studentResult = document.getElementById('studentReportResult');

    studentReportClass.addEventListener('change', function () {
        studentReportStudent.innerHTML = '<option value="">छात्र चुनें</option>';
        if (!studentReportClass.value) return;
        apiRequest('api/students.php?class_id=' + studentReportClass.value).then(function (res) {
            if (!res.success) return;
            res.data.forEach(function (s) {
                var opt = document.createElement('option');
                opt.value = s.id;
                opt.textContent = s.name + ' (' + s.roll_number + ')';
                studentReportStudent.appendChild(opt);
            });
        });
    });

    function loadStudentReport() {
        if (!studentReportStudent.value) { studentResult.innerHTML = ''; return; }
        apiRequest('api/reports.php?type=student&student_id=' + studentReportStudent.value).then(function (res) {
            if (!res.success) return;
            var d = res.data;
            var html = '<div class="card">' +
                '<div class="percentage-card-text"><span class="percentage-label">' + escapeHtml(d.student.name) + ' — उपस्थिति प्रतिशत</span><span class="percentage-value">' + d.percentage + '%</span></div>' +
                '</div>';
            html += '<div class="report-table-wrap"><table class="data-table"><thead><tr><th>तिथि</th><th>स्थिति</th></tr></thead><tbody>';
            d.rows.forEach(function (r) {
                html += '<tr><td>' + escapeHtml(r.date) + '</td><td>' + (STATUS_LABELS[r.status] || r.status) + '</td></tr>';
            });
            html += '</tbody></table></div>';
            studentResult.innerHTML = html;
        });
    }
    studentReportStudent.addEventListener('change', loadStudentReport);
    document.getElementById('studentExportPdf').addEventListener('click', function () {
        if (!studentReportStudent.value) return;
        window.open('print_report.php?type=student&student_id=' + studentReportStudent.value, '_blank');
    });
    document.getElementById('studentExportExcel').addEventListener('click', function () {
        if (!studentReportStudent.value) return;
        window.location.href = 'api/export_excel.php?type=student&student_id=' + studentReportStudent.value;
    });

    // ---- Shared summary table renderer (monthly / class) ----
    function renderSummaryTable(container, students) {
        if (!students || students.length === 0) {
            container.innerHTML = '<div class="empty-state"><p>कोई डेटा उपलब्ध नहीं है।</p></div>';
            return;
        }
        var html = '<div class="report-table-wrap"><table class="data-table"><thead><tr><th>अनु.</th><th>नाम</th><th>उप.</th><th>अनु.</th><th>विलं.</th><th>आधा</th><th>%</th></tr></thead><tbody>';
        students.forEach(function (s) {
            html += '<tr><td>' + escapeHtml(s.roll_number) + '</td><td>' + escapeHtml(s.student_name) + '</td><td>' + s.present + '</td><td>' + s.absent + '</td><td>' + s.late + '</td><td>' + s.half_day + '</td><td>' + s.percentage + '%</td></tr>';
        });
        html += '</tbody></table></div>';
        container.innerHTML = html;
    }

    loadMonthlyReport();
});
