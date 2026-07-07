<?php
/**
 * api/export_excel.php
 * Streams a CSV file (opens natively in Excel) for the requested report type.
 * GET ?type=today|monthly|class|student&...same params as api/reports.php
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$classes = read_json('classes.json');
$students = read_json('students.json');
$attendance = read_json('attendance.json');

function cn($classes, $id) {
    foreach ($classes as $c) {
        if ((int)$c['id'] === (int)$id) return $c['name'] . (!empty($c['section']) ? ' - ' . $c['section'] : '');
    }
    return '—';
}
function sb($students, $id) {
    foreach ($students as $s) {
        if ((int)$s['id'] === (int)$id) return $s;
    }
    return null;
}
$statusLabel = ['present' => 'उपस्थित', 'absent' => 'अनुपस्थित', 'late' => 'विलंब', 'half_day' => 'आधा दिन'];

$type = $_GET['type'] ?? 'today';
$filename = 'report-' . $type . '-' . date('Ymd-His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
// BOM so Excel renders Hindi/Devanagari correctly.
fwrite($out, "\xEF\xBB\xBF");

switch ($type) {
    case 'today':
        $date = $_GET['date'] ?? date('Y-m-d');
        fputcsv($out, ['तिथि', $date]);
        fputcsv($out, ['कक्षा', 'अनुक्रमांक', 'छात्र नाम', 'स्थिति']);
        foreach ($attendance as $rec) {
            if ($rec['date'] !== $date) continue;
            foreach ($rec['records'] as $r) {
                $student = sb($students, $r['student_id']);
                fputcsv($out, [
                    cn($classes, $rec['class_id']),
                    $student['roll_number'] ?? '-',
                    $student['name'] ?? 'अज्ञात',
                    $statusLabel[$r['status']] ?? $r['status'],
                ]);
            }
        }
        break;

    case 'monthly':
    case 'class':
        $classId = (int)($_GET['class_id'] ?? 0);
        $month = $_GET['month'] ?? null;
        $records = array_values(array_filter($attendance, function ($r) use ($classId, $month) {
            $classMatch = !$classId || (int)$r['class_id'] === $classId;
            $monthMatch = !$month || strpos($r['date'], $month) === 0;
            return $classMatch && $monthMatch;
        }));
        $classStudents = $classId
            ? array_values(array_filter($students, function ($s) use ($classId) { return (int)$s['class_id'] === $classId; }))
            : $students;

        $summary = [];
        foreach ($classStudents as $s) {
            $summary[$s['id']] = ['name' => $s['name'], 'roll' => $s['roll_number'],
                'present' => 0, 'absent' => 0, 'late' => 0, 'half_day' => 0, 'total' => 0];
        }
        foreach ($records as $rec) {
            foreach ($rec['records'] as $r) {
                if (!isset($summary[$r['student_id']])) continue;
                $summary[$r['student_id']][$r['status']]++;
                $summary[$r['student_id']]['total']++;
            }
        }
        fputcsv($out, ['कक्षा', $classId ? cn($classes, $classId) : 'सभी कक्षाएँ']);
        fputcsv($out, ['अनुक्रमांक', 'छात्र नाम', 'उपस्थित', 'अनुपस्थित', 'विलंब', 'आधा दिन', 'कुल दिन', 'प्रतिशत']);
        foreach ($summary as $row) {
            $presentTotal = $row['present'] + $row['late'] + $row['half_day'];
            $pct = $row['total'] > 0 ? round(($presentTotal / $row['total']) * 100, 1) : 0;
            fputcsv($out, [$row['roll'], $row['name'], $row['present'], $row['absent'], $row['late'], $row['half_day'], $row['total'], $pct . '%']);
        }
        break;

    case 'student':
        $studentId = (int)($_GET['student_id'] ?? 0);
        $student = sb($students, $studentId);
        fputcsv($out, ['छात्र नाम', $student['name'] ?? '-']);
        fputcsv($out, ['कक्षा', cn($classes, $student['class_id'] ?? 0)]);
        fputcsv($out, ['तिथि', 'स्थिति']);
        foreach ($attendance as $rec) {
            foreach ($rec['records'] as $r) {
                if ((int)$r['student_id'] === $studentId) {
                    fputcsv($out, [$rec['date'], $statusLabel[$r['status']] ?? $r['status']]);
                }
            }
        }
        break;

    default:
        fputcsv($out, ['अमान्य प्रतिवेदन प्रकार']);
}

fclose($out);
exit;
