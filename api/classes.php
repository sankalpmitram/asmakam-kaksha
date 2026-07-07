<?php
/**
 * api/classes.php
 * CRUD API for classes.
 *
 * GET    ?id=1            -> single class
 * GET    (no id)          -> list all classes (optional ?q=search)
 * POST                    -> create new class
 * PUT    ?id=1            -> update class
 * DELETE ?id=1            -> delete class (also detaches / warns about students)
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login_api();

$method = $_SERVER['REQUEST_METHOD'];
$classes = read_json('classes.json');

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            foreach ($classes as $c) {
                if ((int)$c['id'] === $id) {
                    json_response(true, '', $c);
                }
            }
            json_response(false, 'कक्षा नहीं मिली।', null, 404);
        }

        $q = sanitize_text($_GET['q'] ?? '');
        if ($q !== '') {
            $qLower = mb_strtolower($q, 'UTF-8');
            $classes = array_values(array_filter($classes, function ($c) use ($qLower) {
                return mb_strpos(mb_strtolower($c['name'], 'UTF-8'), $qLower) !== false
                    || mb_strpos(mb_strtolower($c['section'] ?? '', 'UTF-8'), $qLower) !== false;
            }));
        }

        // Attach student counts.
        $students = read_json('students.json');
        foreach ($classes as &$c) {
            $c['student_count'] = count(array_filter($students, function ($s) use ($c) {
                return (int)$s['class_id'] === (int)$c['id'];
            }));
        }
        unset($c);

        json_response(true, '', $classes);
        break;

    case 'POST':
        $body = read_json_body();
        $name = sanitize_text($body['name'] ?? '');
        if ($name === '') {
            json_response(false, 'कक्षा नाम आवश्यक है।', null, 422);
        }

        $newClass = [
            'id' => next_id($classes),
            'name' => $name,
            'section' => sanitize_text($body['section'] ?? ''),
            'session' => sanitize_text($body['session'] ?? ''),
            'teacher' => sanitize_text($body['teacher'] ?? ''),
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $classes[] = $newClass;
        write_json('classes.json', $classes);
        json_response(true, 'कक्षा सफलतापूर्वक जोड़ी गई।', $newClass, 201);
        break;

    case 'PUT':
        $id = (int)($_GET['id'] ?? 0);
        $body = read_json_body();
        $found = false;
        foreach ($classes as &$c) {
            if ((int)$c['id'] === $id) {
                $name = sanitize_text($body['name'] ?? $c['name']);
                if ($name === '') {
                    json_response(false, 'कक्षा नाम आवश्यक है।', null, 422);
                }
                $c['name'] = $name;
                $c['section'] = sanitize_text($body['section'] ?? $c['section']);
                $c['session'] = sanitize_text($body['session'] ?? $c['session']);
                $c['teacher'] = sanitize_text($body['teacher'] ?? $c['teacher']);
                $found = true;
                $updated = $c;
                break;
            }
        }
        unset($c);
        if (!$found) {
            json_response(false, 'कक्षा नहीं मिली।', null, 404);
        }
        write_json('classes.json', $classes);
        json_response(true, 'कक्षा सफलतापूर्वक अद्यतन की गई।', $updated);
        break;

    case 'DELETE':
        $id = (int)($_GET['id'] ?? 0);
        $exists = false;
        $newList = [];
        foreach ($classes as $c) {
            if ((int)$c['id'] === $id) {
                $exists = true;
                continue;
            }
            $newList[] = $c;
        }
        if (!$exists) {
            json_response(false, 'कक्षा नहीं मिली।', null, 404);
        }
        write_json('classes.json', $newList);

        // Remove class reference from students (leave students intact but unassigned).
        $students = read_json('students.json');
        foreach ($students as &$s) {
            if ((int)$s['class_id'] === $id) {
                $s['class_id'] = null;
            }
        }
        unset($s);
        write_json('students.json', $students);

        json_response(true, 'कक्षा सफलतापूर्वक हटाई गई।');
        break;

    default:
        json_response(false, 'अमान्य अनुरोध विधि।', null, 405);
}
