/**
 * dashboard.js
 * Loads and renders dashboard summary stats.
 */
document.addEventListener('DOMContentLoaded', function () {
    apiRequest('api/dashboard.php').then(function (res) {
        if (!res.success) return;
        var d = res.data;
        document.getElementById('statTotalClasses').textContent = d.total_classes;
        document.getElementById('statTotalStudents').textContent = d.total_students;
        document.getElementById('statPresent').textContent = d.today_present + d.today_late + d.today_half_day;
        document.getElementById('statAbsent').textContent = d.today_absent;
        document.getElementById('statPercentage').textContent = d.attendance_percentage + '%';
        document.getElementById('statPercentageBar').style.width = d.attendance_percentage + '%';
    });
});
