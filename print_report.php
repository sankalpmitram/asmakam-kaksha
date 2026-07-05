<?php
/**
 * print_report.php
 * Standalone print-friendly page for a report. The user can use the
 * browser's "Print" -> "Save as PDF" option, satisfying the Export PDF
 * requirement without any external PDF library dependency.
 *
 * GET params: type=today|monthly|class|student (+ same filters as api/reports.php)
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

$settings = read_json('settings.json');
$classes = read_json('classes.json');
$students = read_json('students.json');
$attendance = read_json('attendance.json');

function pcn($classes, $id) {
    foreach ($classes as $c) {
        if ((int)$c['id'] === (int)$id) return $c['name'] . (!empty($c['section']) ? ' - ' . $c['section'] : '');
    }
    return '—';
}
function psb($students, $id) {
    foreach ($students as $s) {
        if ((int)$s['id'] === (int)$id) return $s;
    }
    return null;
}
$statusLabel = ['present' => 'उपस्थित', 'absent' => 'अनुपस्थित', 'late' => 'विलंब', 'half_day' => 'आधा दिन'];

$type = $_GET['type'] ?? 'today';
$reportTitle = 'प्रतिवेदन';
?>
<!DOCTYPE html>
<html lang="hi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>प्रतिवेदन प्रिंट | अस्माकं कक्षा</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
    body { background: #fff; padding: 16px; }
    .print-header { text-align: center; margin-bottom: 16px; }
    .print-header h2 { margin: 4px 0; }
    table.report-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    table.report-table th, table.report-table td { border: 1px solid #999; padding: 6px 8px; text-align: right; }
    table.report-table th:first-child, table.report-table td:first-child,
    table.report-table th:nth-child(2), table.report-table td:nth-child(2) { text-align: left; }
    .print-actions { margin-bottom: 16px; text-align: center; }
    @media print {
        .print-actions { display: none; }
    }
</style>
</head>
<body>
<div class="print-actions">
    <button class="btn btn-primary" onclick="window.print()">🖨️ प्रिंट / PDF डाउनलोड करें</button>
</div>

<div class="print-header">
    <?php if (!empty($settings['school_logo'])): ?>
        <img src="<?php echo h($settings['school_logo']); ?>" alt="लोगो" style="height:60px;">
    <?php endif; ?>
    <h2><?php echo h($settings['school_name'] ?? 'विद्यालय'); ?></h2>
    <div><?php echo h($settings['session'] ?? ''); ?></div>
</div>

<?php if ($type === 'today'): ?>
    <?php
    $date = $_GET['date'] ?? date('Y-m-d');
    $rows = [];
    foreach ($attendance as $rec) {
        if ($rec['date'] !== $date) continue;
        foreach ($rec['records'] as $r) {
            $s = psb($students, $r['student_id']);
            $rows[] = [pcn($classes, $rec['class_id']), $s['roll_number'] ?? '-', $s['name'] ?? 'अज्ञात', $statusLabel[$r['status']] ?? $r['status']];
        }
    }
    ?>
    <h3 style="text-align:center;">दिनांक <?php echo h($date); ?> का उपस्थिति प्रतिवेदन</h3>
    <table class="report-table">
        <thead><tr><th>कक्षा</th><th>अनुक्रमांक</th><th>छात्र नाम</th><th>स्थिति</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr><td><?php echo h($row[0]); ?></td><td><?php echo h($row[1]); ?></td><td><?php echo h($row[2]); ?></td><td><?php echo h($row[3]); ?></td></tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?>
            <tr><td colspan="4">इस तिथि हेतु कोई अभिलेख नहीं है।</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

<?php elseif ($type === 'monthly' || $type === 'class'): ?>
    <?php
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
    ?>
    <h3 style="text-align:center;">
        <?php echo $classId ? h(pcn($classes, $classId)) : 'सभी कक्षाएँ'; ?>
        <?php echo $month ? ' — ' . h($month) : ''; ?> उपस्थिति प्रतिवेदन
    </h3>
    <table class="report-table">
        <thead><tr><th>अनुक्रमांक</th><th>छात्र नाम</th><th>उपस्थित</th><th>अनुपस्थित</th><th>विलंब</th><th>आधा दिन</th><th>कुल दिन</th><th>%</th></tr></thead>
        <tbody>
        <?php foreach ($summary as $row): ?>
            <?php
            $presentTotal = $row['present'] + $row['late'] + $row['half_day'];
            $pct = $row['total'] > 0 ? round(($presentTotal / $row['total']) * 100, 1) : 0;
            ?>
            <tr>
                <td><?php echo h($row['roll']); ?></td>
                <td><?php echo h($row['name']); ?></td>
                <td><?php echo h($row['present']); ?></td>
                <td><?php echo h($row['absent']); ?></td>
                <td><?php echo h($row['late']); ?></td>
                <td><?php echo h($row['half_day']); ?></td>
                <td><?php echo h($row['total']); ?></td>
                <td><?php echo h($pct); ?>%</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

<?php elseif ($type === 'student'): ?>
    <?php
    $studentId = (int)($_GET['student_id'] ?? 0);
    $student = psb($students, $studentId);
    $rows = [];
    foreach ($attendance as $rec) {
        foreach ($rec['records'] as $r) {
            if ((int)$r['student_id'] === $studentId) {
                $rows[] = [$rec['date'], $statusLabel[$r['status']] ?? $r['status']];
            }
        }
    }
    usort($rows, function ($a, $b) { return strcmp($b[0], $a[0]); });
    ?>
    <h3 style="text-align:center;"><?php echo h($student['name'] ?? 'अज्ञात'); ?> — उपस्थिति विवरण</h3>
    <p style="text-align:center;">कक्षा: <?php echo h(pcn($classes, $student['class_id'] ?? 0)); ?> | अनुक्रमांक: <?php echo h($student['roll_number'] ?? '-'); ?></p>
    <table class="report-table">
        <thead><tr><th>तिथि</th><th>स्थिति</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr><td><?php echo h($row[0]); ?></td><td><?php echo h($row[1]); ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>अमान्य प्रतिवेदन प्रकार।</p>
<?php endif; ?>

</body>
</html>
