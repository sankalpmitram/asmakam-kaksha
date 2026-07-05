<?php
/**
 * api/attendance.php
 * Manages attendance records.
 *
 * Each attendance record represents ONE class on ONE date:
 * { id, class_id, date, records: [{student_id, status}], created_at, updated_at }
 * status is one of: present, absent, late, half_day
 *
 * GET ?class_id=&date=      -> single record for that class + date (for take/edit attendance)
 * GET ?class_id=&month=YYYY-MM -> all records for a class in a month (calendar view)
 * GET ?student_id=          -> all records containing that student (student-wise history/report)
 * GET ?date=                -> all class records on a given date (today's report)
 * GET (no filters)          -> all records (used sparingly, e.g. dashboard stats)
 * POST                      -> create new attendance record (blocks duplicate class+date)
 * PUT ?id=                  -> update existing attendance record
 * DELETE ?id=               -> delete attendance record
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login_api();

$method = $_SERVER['REQUEST_METHOD'];
$attendance = read_json('attendance.json');

$validStatuses = ['present', 'absent', 'late', 'half_day'];

function find_record_index(&$attendance, $classId, $date) {
    foreach ($attendance as $i => $rec) {
        if ((int)$rec['class_id'] === (int)$classId && $rec['date'] === $date) {
            return $i;
        }
    }
    return -1;
}

switch ($method) {
    case 'GET':
        if (isset($_GET['class_id']) && isset($_GET['date'])) {
            $idx = find_record_index($attendance, (int)$_GET['class_id'], $_GET['date']);
            if ($idx === -1) {
                json_response(true, 'इस तिथि हेतु कोई अभिलेख नहीं।', null);
            }
            json_response(true, '', $attendance[$idx]);
        }

        if (isset($_GET['class_id']) && isset($_GET['month'])) {
            $classId = (int)$_GET['class_id'];
            $month = $_GET['month']; // format YYYY-MM
            $filtered = array_values(array_filter($attendance, function ($r) use ($classId, $month) {
                return (int)$r['class_id'] === $classId && strpos($r['date'], $month) === 0;
            }));
            json_response(true, '', $filtered);
        }

        if (isset($_GET['student_id'])) {
            $studentId = (int)$_GET['student_id'];
            $result = [];
            foreach ($attendance as $rec) {
                foreach ($rec['records'] as $r) {
                    if ((int)$r['student_id'] === $studentId) {
                        $result[] = [
                            'attendance_id' => $rec['id'],
                            'class_id' => $rec['class_id'],
                            'date' => $rec['date'],
                            'status' => $r['status'],
                        ];
                    }
                }
            }
            usort($result, function ($a, $b) { return strcmp($b['date'], $a['date']); });
            json_response(true, '', $result);
        }

        if (isset($_GET['date'])) {
            $date = $_GET['date'];
            $filtered = array_values(array_filter($attendance, function ($r) use ($date) {
                return $r['date'] === $date;
            }));
            json_response(true, '', $filtered);
        }

        json_response(true, '', $attendance);
        break;

    case 'POST':
        $body = read_json_body();
        $classId = (int)($body['class_id'] ?? 0);
        $date = sanitize_text($body['date'] ?? '');
        $records = $body['records'] ?? [];

        if (!$classId || $date === '' || !is_array($records) || count($records) === 0) {
            json_response(false, 'कक्षा, तिथि एवं छात्र सूची आवश्यक है।', null, 422);
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            json_response(false, 'तिथि प्रारूप मान्य नहीं है।', null, 422);
        }

        $idx = find_record_index($attendance, $classId, $date);
        if ($idx !== -1) {
            json_response(false, 'इस तिथि की उपस्थिति पहले से दर्ज है। कृपया संपादित करें।', null, 409);
        }

        $cleanRecords = [];
        foreach ($records as $r) {
            $status = $r['status'] ?? 'present';
            if (!in_array($status, $GLOBALS['validStatuses'], true)) $status = 'present';
            $cleanRecords[] = [
                'student_id' => (int)($r['student_id'] ?? 0),
                'status' => $status,
            ];
        }

        $newRecord = [
            'id' => next_id($attendance),
            'class_id' => $classId,
            'date' => $date,
            'records' => $cleanRecords,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $attendance[] = $newRecord;
        write_json('attendance.json', $attendance);
        json_response(true, 'उपस्थिति सफलतापूर्वक सहेजी गई।', $newRecord, 201);
        break;

    case 'PUT':
        $id = (int)($_GET['id'] ?? 0);
        $body = read_json_body();
        $records = $body['records'] ?? null;
        $found = false;
        foreach ($attendance as &$rec) {
            if ((int)$rec['id'] === $id) {
                if (is_array($records)) {
                    $cleanRecords = [];
                    foreach ($records as $r) {
                        $status = $r['status'] ?? 'present';
                        if (!in_array($status, $validStatuses, true)) $status = 'present';
                        $cleanRecords[] = [
                            'student_id' => (int)($r['student_id'] ?? 0),
                            'status' => $status,
                        ];
                    }
                    $rec['records'] = $cleanRecords;
                }
                $rec['updated_at'] = date('Y-m-d H:i:s');
                $found = true;
                $updated = $rec;
                break;
            }
        }
        unset($rec);
        if (!$found) {
            json_response(false, 'उपस्थिति अभिलेख नहीं मिला।', null, 404);
        }
        write_json('attendance.json', $attendance);
        json_response(true, 'उपस्थिति सफलतापूर्वक अद्यतन हुई।', $updated);
        break;

    case 'DELETE':
        $id = (int)($_GET['id'] ?? 0);
        $exists = false;
        $newList = [];
        foreach ($attendance as $rec) {
            if ((int)$rec['id'] === $id) {
                $exists = true;
                continue;
            }
            $newList[] = $rec;
        }
        if (!$exists) {
            json_response(false, 'उपस्थिति अभिलेख नहीं मिला।', null, 404);
        }
        write_json('attendance.json', $newList);
        json_response(true, 'उपस्थिति अभिलेख सफलतापूर्वक हटाया गया।');
        break;

    default:
        json_response(false, 'अमान्य अनुरोध विधि।', null, 405);
}
