<?php
/**
 * api/students.php
 * CRUD API for students.
 *
 * GET    ?id=1                -> single student
 * GET    ?class_id=1          -> students in a class
 * GET    (no filters)         -> list all students (optional ?q=search)
 * POST                        -> create new student (multipart/form-data, supports photo)
 * PUT    ?id=1                -> update student (multipart/form-data, supports photo)
 * DELETE ?id=1                -> delete student
 * POST   ?action=move&id=1    -> move student to another class (JSON body: {class_id})
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login_api();

$method = $_SERVER['REQUEST_METHOD'];
$students = read_json('students.json');

function class_id_exists($classId) {
    if (!$classId) return true; // unassigned allowed
    $classes = read_json('classes.json');
    foreach ($classes as $c) {
        if ((int)$c['id'] === (int)$classId) return true;
    }
    return false;
}

function roll_number_taken($students, $classId, $roll, $excludeId = null) {
    foreach ($students as $s) {
        if ((int)$s['class_id'] === (int)$classId
            && strcasecmp((string)$s['roll_number'], (string)$roll) === 0
            && (int)$s['id'] !== (int)$excludeId) {
            return true;
        }
    }
    return false;
}

function handle_photo_upload($fieldName, $existing = '') {
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return $existing;
    }
    $file = $_FILES[$fieldName];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return $existing;
    }
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!isset($allowed[$mime])) {
        return $existing;
    }
    if ($file['size'] > 2 * 1024 * 1024) { // 2MB limit
        return $existing;
    }
    $ext = $allowed[$mime];
    $objectName = 'student-photos/' . uniqid() . '.' . $ext;
    $publicUrl = storage_upload_local_file($file['tmp_name'], $objectName, $mime);
    if ($publicUrl) {
        // Delete the old photo (if any) now that the new one is safely uploaded.
        $oldObject = storage_object_name_from_url($existing);
        if ($oldObject) storage_delete_object($oldObject);
        return $publicUrl;
    }
    return $existing;
}

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            foreach ($students as $s) {
                if ((int)$s['id'] === $id) {
                    json_response(true, '', $s);
                }
            }
            json_response(false, 'छात्र नहीं मिला।', null, 404);
        }

        $result = $students;

        if (isset($_GET['class_id']) && $_GET['class_id'] !== '') {
            $classId = (int)$_GET['class_id'];
            $result = array_values(array_filter($result, function ($s) use ($classId) {
                return (int)$s['class_id'] === $classId;
            }));
        }

        $q = sanitize_text($_GET['q'] ?? '');
        if ($q !== '') {
            $qLower = mb_strtolower($q, 'UTF-8');
            $result = array_values(array_filter($result, function ($s) use ($qLower) {
                return mb_strpos(mb_strtolower($s['name'], 'UTF-8'), $qLower) !== false
                    || mb_strpos(mb_strtolower((string)$s['roll_number'], 'UTF-8'), $qLower) !== false
                    || mb_strpos(mb_strtolower($s['father_name'] ?? '', 'UTF-8'), $qLower) !== false;
            }));
        }

        // Sort by roll number naturally within class
        usort($result, function ($a, $b) {
            return strnatcasecmp((string)$a['roll_number'], (string)$b['roll_number']);
        });

        json_response(true, '', $result);
        break;

    case 'POST':
        // Move student action.
        if (($_GET['action'] ?? '') === 'move') {
            $id = (int)($_GET['id'] ?? 0);
            $body = read_json_body();
            $newClassId = isset($body['class_id']) ? (int)$body['class_id'] : null;
            if (!class_id_exists($newClassId)) {
                json_response(false, 'लक्ष्य कक्षा मान्य नहीं है।', null, 422);
            }
            $found = false;
            foreach ($students as &$s) {
                if ((int)$s['id'] === $id) {
                    if (roll_number_taken($students, $newClassId, $s['roll_number'], $id)) {
                        json_response(false, 'नई कक्षा में यह अनुक्रमांक पहले से उपयोग में है।', null, 422);
                    }
                    $s['class_id'] = $newClassId;
                    $found = true;
                    $updated = $s;
                    break;
                }
            }
            unset($s);
            if (!$found) json_response(false, 'छात्र नहीं मिला।', null, 404);
            write_json('students.json', $students);
            json_response(true, 'छात्र सफलतापूर्वक स्थानांतरित हुआ।', $updated);
        }

        $name = sanitize_text($_POST['name'] ?? '');
        $rollNumber = sanitize_text($_POST['roll_number'] ?? '');
        $classId = isset($_POST['class_id']) && $_POST['class_id'] !== '' ? (int)$_POST['class_id'] : null;
        $whatsapp = sanitize_text($_POST['whatsapp_number'] ?? '');

        if ($name === '' || $rollNumber === '' || !$classId) {
            json_response(false, 'नाम, अनुक्रमांक एवं कक्षा आवश्यक हैं।', null, 422);
        }
        if (!class_id_exists($classId)) {
            json_response(false, 'चयनित कक्षा मान्य नहीं है।', null, 422);
        }
        if ($whatsapp !== '' && !is_valid_phone($whatsapp)) {
            json_response(false, 'व्हाट्सएप संख्या मान्य नहीं है।', null, 422);
        }
        if (roll_number_taken($students, $classId, $rollNumber)) {
            json_response(false, 'इस कक्षा में यह अनुक्रमांक पहले से उपयोग में है।', null, 422);
        }

        $photo = handle_photo_upload('photo');

        $newStudent = [
            'id' => next_id($students),
            'name' => $name,
            'roll_number' => $rollNumber,
            'father_name' => sanitize_text($_POST['father_name'] ?? ''),
            'mother_name' => sanitize_text($_POST['mother_name'] ?? ''),
            'guardian_name' => sanitize_text($_POST['guardian_name'] ?? ''),
            'whatsapp_number' => $whatsapp,
            'class_id' => $classId,
            'gender' => sanitize_text($_POST['gender'] ?? ''),
            'dob' => sanitize_text($_POST['dob'] ?? ''),
            'photo' => $photo,
            'address' => sanitize_text($_POST['address'] ?? ''),
            'notes' => sanitize_text($_POST['notes'] ?? ''),
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $students[] = $newStudent;
        write_json('students.json', $students);
        json_response(true, 'छात्र सफलतापूर्वक जोड़ा गया।', $newStudent, 201);
        break;

    case 'PUT':
        // PHP does not populate $_POST for PUT multipart requests automatically,
        // so the client sends PUT as POST with method override (_method=PUT) OR
        // we parse manually. Here we support JSON body updates for text fields
        // and a separate POST endpoint (?action=photo) for photo updates.
        $id = (int)($_GET['id'] ?? 0);
        $body = read_json_body();
        $found = false;
        foreach ($students as &$s) {
            if ((int)$s['id'] === $id) {
                $name = sanitize_text($body['name'] ?? $s['name']);
                $rollNumber = sanitize_text($body['roll_number'] ?? $s['roll_number']);
                $classId = isset($body['class_id']) && $body['class_id'] !== '' ? (int)$body['class_id'] : $s['class_id'];
                $whatsapp = sanitize_text($body['whatsapp_number'] ?? $s['whatsapp_number']);

                if ($name === '' || $rollNumber === '') {
                    json_response(false, 'नाम एवं अनुक्रमांक आवश्यक हैं।', null, 422);
                }
                if (!class_id_exists($classId)) {
                    json_response(false, 'चयनित कक्षा मान्य नहीं है।', null, 422);
                }
                if ($whatsapp !== '' && !is_valid_phone($whatsapp)) {
                    json_response(false, 'व्हाट्सएप संख्या मान्य नहीं है।', null, 422);
                }
                if (roll_number_taken($students, $classId, $rollNumber, $id)) {
                    json_response(false, 'इस कक्षा में यह अनुक्रमांक पहले से उपयोग में है।', null, 422);
                }

                $s['name'] = $name;
                $s['roll_number'] = $rollNumber;
                $s['class_id'] = $classId;
                $s['father_name'] = sanitize_text($body['father_name'] ?? $s['father_name']);
                $s['mother_name'] = sanitize_text($body['mother_name'] ?? $s['mother_name']);
                $s['guardian_name'] = sanitize_text($body['guardian_name'] ?? $s['guardian_name']);
                $s['whatsapp_number'] = $whatsapp;
                $s['gender'] = sanitize_text($body['gender'] ?? $s['gender']);
                $s['dob'] = sanitize_text($body['dob'] ?? $s['dob']);
                $s['address'] = sanitize_text($body['address'] ?? $s['address']);
                $s['notes'] = sanitize_text($body['notes'] ?? $s['notes']);
                if (isset($body['photo'])) {
                    $s['photo'] = sanitize_text($body['photo']);
                }
                $found = true;
                $updated = $s;
                break;
            }
        }
        unset($s);
        if (!$found) {
            json_response(false, 'छात्र नहीं मिला।', null, 404);
        }
        write_json('students.json', $students);
        json_response(true, 'छात्र विवरण सफलतापूर्वक अद्यतन हुआ।', $updated);
        break;

    case 'DELETE':
        $id = (int)($_GET['id'] ?? 0);
        $exists = false;
        $newList = [];
        $photoToRemove = null;
        foreach ($students as $s) {
            if ((int)$s['id'] === $id) {
                $exists = true;
                $photoToRemove = $s['photo'] ?? null;
                continue;
            }
            $newList[] = $s;
        }
        if (!$exists) {
            json_response(false, 'छात्र नहीं मिला।', null, 404);
        }
        write_json('students.json', $newList);
        if ($photoToRemove) {
            $objectName = storage_object_name_from_url($photoToRemove);
            if ($objectName) storage_delete_object($objectName);
        }
        json_response(true, 'छात्र सफलतापूर्वक हटाया गया।');
        break;

    default:
        json_response(false, 'अमान्य अनुरोध विधि।', null, 405);
}
