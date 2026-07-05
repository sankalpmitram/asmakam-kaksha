<?php
/**
 * api/dashboard.php
 * Returns aggregate stats for the dashboard cards.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login_api();

$classes = read_json('classes.json');
$students = read_json('students.json');
$attendance = read_json('attendance.json');

$today = date('Y-m-d');
$todayRecords = array_filter($attendance, function ($r) use ($today) {
    return $r['date'] === $today;
});

$present = 0;
$absent = 0;
$late = 0;
$halfDay = 0;
foreach ($todayRecords as $rec) {
    foreach ($rec['records'] as $r) {
        switch ($r['status']) {
            case 'present': $present++; break;
            case 'absent': $absent++; break;
            case 'late': $late++; break;
            case 'half_day': $halfDay++; break;
        }
    }
}
$markedTotal = $present + $absent + $late + $halfDay;
$percentage = $markedTotal > 0 ? round((($present + $late + $halfDay) / $markedTotal) * 100, 1) : 0;

json_response(true, '', [
    'total_classes' => count($classes),
    'total_students' => count($students),
    'today_present' => $present,
    'today_absent' => $absent,
    'today_late' => $late,
    'today_half_day' => $halfDay,
    'attendance_percentage' => $percentage,
    'classes_marked_today' => count($todayRecords),
]);
