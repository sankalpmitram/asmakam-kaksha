<?php
/**
 * api/reports.php
 * Computes report data consumed by pages/reports.php (charts/tables) and
 * by api/export_excel.php / the print-friendly report view.
 *
 * GET ?type=today
 * GET ?type=monthly&class_id=&month=YYYY-MM
 * GET ?type=class&class_id=
 * GET ?type=student&student_id=
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login_api();

$classes = read_json('classes.json');
$students = read_json('students.json');
$attendance = read_json('attendance.json');

function class_name_by_id($classes, $id) {
    foreach ($classes as $c) {
        if ((int)$c['id'] === (int)$id) return $c['name'] . (!empty($c['section']) ? ' - ' . $c['section'] : '');
    }
    return '—';
}

function student_by_id($students, $id) {
    foreach ($students as $s) {
        if ((int)$s['id'] === (int)$id) return $s;
    }
    return null;
}

$type = $_GET['type'] ?? 'today';

switch ($type) {

    case 'today':
        $date = $_GET['date'] ?? date('Y-m-d');
        $rows = [];
        foreach ($attendance as $rec) {
            if ($rec['date'] !== $date) continue;
            foreach ($rec['records'] as $r) {
                $student = student_by_id($students, $r['student_id']);
                $rows[] = [
                    'class_name' => class_name_by_id($classes, $rec['class_id']),
                    'roll_number' => $student['roll_number'] ?? '-',
                    'student_name' => $student['name'] ?? 'अज्ञात',
                    'status' => $r['status'],
                ];
            }
        }
        json_response(true, '', ['date' => $date, 'rows' => $rows]);
        break;

    case 'monthly':
        $classId = (int)($_GET['class_id'] ?? 0);
        $month = $_GET['month'] ?? date('Y-m');
        $records = array_values(array_filter($attendance, function ($r) use ($classId, $month) {
            return (!$classId || (int)$r['class_id'] === $classId) && strpos($r['date'], $month) === 0;
        }));

        $classStudents = $classId
            ? array_values(array_filter($students, function ($s) use ($classId) { return (int)$s['class_id'] === $classId; }))
            : $students;

        $summary = [];
        foreach ($classStudents as $s) {
            $summary[$s['id']] = [
                'student_name' => $s['name'],
                'roll_number' => $s['roll_number'],
                'present' => 0, 'absent' => 0, 'late' => 0, 'half_day' => 0, 'total_marked' => 0,
            ];
        }
        foreach ($records as $rec) {
            foreach ($rec['records'] as $r) {
                if (!isset($summary[$r['student_id']])) continue;
                $summary[$r['student_id']][$r['status']]++;
                $summary[$r['student_id']]['total_marked']++;
            }
        }
        foreach ($summary as &$row) {
            $present = $row['present'] + $row['late'] + $row['half_day'];
            $row['percentage'] = $row['total_marked'] > 0 ? round(($present / $row['total_marked']) * 100, 1) : 0;
        }
        unset($row);

        json_response(true, '', [
            'month' => $month,
            'class_name' => $classId ? class_name_by_id($classes, $classId) : 'सभी कक्षाएँ',
            'days_marked' => count($records),
            'students' => array_values($summary),
        ]);
        break;

    case 'class':
        $classId = (int)($_GET['class_id'] ?? 0);
        $records = array_values(array_filter($attendance, function ($r) use ($classId) {
            return (int)$r['class_id'] === $classId;
        }));
        $classStudents = array_values(array_filter($students, function ($s) use ($classId) {
            return (int)$s['class_id'] === $classId;
        }));

        $summary = [];
        foreach ($classStudents as $s) {
            $summary[$s['id']] = [
                'student_name' => $s['name'],
                'roll_number' => $s['roll_number'],
                'present' => 0, 'absent' => 0, 'late' => 0, 'half_day' => 0, 'total_marked' => 0,
            ];
        }
        foreach ($records as $rec) {
            foreach ($rec['records'] as $r) {
                if (!isset($summary[$r['student_id']])) continue;
                $summary[$r['student_id']][$r['status']]++;
                $summary[$r['student_id']]['total_marked']++;
            }
        }
        foreach ($summary as &$row) {
            $present = $row['present'] + $row['late'] + $row['half_day'];
            $row['percentage'] = $row['total_marked'] > 0 ? round(($present / $row['total_marked']) * 100, 1) : 0;
        }
        unset($row);

        json_response(true, '', [
            'class_name' => class_name_by_id($classes, $classId),
            'total_days' => count($records),
            'students' => array_values($summary),
        ]);
        break;

    case 'student':
        $studentId = (int)($_GET['student_id'] ?? 0);
        $student = student_by_id($students, $studentId);
        if (!$student) {
            json_response(false, 'छात्र नहीं मिला।', null, 404);
        }
        $rows = [];
        $counts = ['present' => 0, 'absent' => 0, 'late' => 0, 'half_day' => 0];
        foreach ($attendance as $rec) {
            foreach ($rec['records'] as $r) {
                if ((int)$r['student_id'] === $studentId) {
                    $rows[] = ['date' => $rec['date'], 'status' => $r['status']];
                    $counts[$r['status']]++;
                }
            }
        }
        usort($rows, function ($a, $b) { return strcmp($b['date'], $a['date']); });
        $total = array_sum($counts);
        $percentage = $total > 0 ? round((($counts['present'] + $counts['late'] + $counts['half_day']) / $total) * 100, 1) : 0;

        json_response(true, '', [
            'student' => $student,
            'class_name' => class_name_by_id($classes, $student['class_id']),
            'counts' => $counts,
            'percentage' => $percentage,
            'rows' => $rows,
        ]);
        break;

    default:
        json_response(false, 'अमान्य प्रतिवेदन प्रकार।', null, 422);
}
