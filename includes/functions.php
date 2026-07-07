<?php
date_default_timezone_set('Asia/Kolkata');

/**
 * functions.php
 * Core helper functions used across the application.
 *
 * STORAGE ENGINE: Firestore (Google Cloud). Data used to live in local
 * JSON files, which was a problem on hosts with an ephemeral filesystem
 * (e.g. Render's free tier resets local files on restart/redeploy).
 * Firestore is a real persistent cloud database, so data now survives
 * restarts, redeploys, and server sleep/wake cycles.
 *
 * IMPORTANT: every other file in this app (all of api/*.php, reports,
 * print_report.php, etc.) still calls read_json() / write_json() / next_id()
 * exactly as before. Only the *internals* of those three functions changed
 * — from "read/write a local file" to "read/write a Firestore collection".
 * This means the rest of the codebase required zero changes to move from
 * JSON files to Firestore.
 *
 * "Files" map to Firestore collections as follows:
 *   classes.json    -> collection "classes"    (list of documents)
 *   students.json   -> collection "students"   (list of documents)
 *   attendance.json -> collection "attendance" (list of documents)
 *   users.json      -> collection "users"      (list of documents)
 *   settings.json   -> collection "settings", single document "app"
 *
 * Student/school photo uploads live in Firebase Storage (includes/storage.php)
 * — not local disk, and not Firestore either, since Firestore documents
 * have a 1MiB size limit that's too small for full-size photos.
 */

require_once __DIR__ . '/firestore.php';
require_once __DIR__ . '/storage.php';

// Firestore collection name for each "logical file" the app used to use.
define('FIRESTORE_LIST_COLLECTIONS', [
    'classes.json' => 'classes',
    'students.json' => 'students',
    'attendance.json' => 'attendance',
    'users.json' => 'users',
]);
define('FIRESTORE_SETTINGS_COLLECTION', 'settings');
define('FIRESTORE_SETTINGS_DOC_ID', 'app');

/**
 * Default skeletons for each data file / collection.
 * Used to seed Firestore the first time the app runs (empty database).
 */
function default_data_structure($fileName) {
    switch ($fileName) {
        case 'classes.json':
            return [];
        case 'students.json':
            return [];
        case 'attendance.json':
            return [];
        case 'users.json':
            return [
                [
                    'id' => 1,
                    'username' => 'teacher',
                    // Default password: teacher@123 (hashed). Change after first login.
                    'password' => password_hash('teacher@123', PASSWORD_DEFAULT),
                    'name' => 'कक्षा शिक्षक'
                ]
            ];
        case 'settings.json':
            return [
                'school_name' => 'आदर्श सरस्वती विद्यालय',
                'school_logo' => '',
                'teacher_name' => 'कक्षा शिक्षक',
                'session' => date('Y') . '-' . (date('Y') + 1),
                'default_attendance_time' => '08:00',
                'whatsapp_template' => "आदरणीय अभिभावक,\n\nआपका पुत्र/पुत्री {student_name} आज दिनांक {date} को विद्यालय में अनुपस्थित रहे/रही।\n\nकृपया अनुपस्थिति का कारण कक्षा शिक्षक को अवश्य बताएं।\n\nधन्यवाद।",
                'theme' => 'light'
            ];
        default:
            return [];
    }
}

/**
 * Ensures local upload directories exist, and seeds Firestore with
 * default documents the very first time the app is used (empty DB).
 * Called on every request bootstrap.
 */
function ensure_data_files() {

    // Seed default settings document if it doesn't exist yet.
    $settingsDoc = firestore_get(FIRESTORE_SETTINGS_COLLECTION, FIRESTORE_SETTINGS_DOC_ID);

    if ($settingsDoc === null) {

        $ok = firestore_set(
            FIRESTORE_SETTINGS_COLLECTION,
            FIRESTORE_SETTINGS_DOC_ID,
            default_data_structure('settings.json')
        );

        if (!$ok) {
            json_response(false, 'Settings document create failed');
        }
    }

    // Seed default teacher user if the users collection is empty.
    $collections = FIRESTORE_LIST_COLLECTIONS;

    $users = firestore_list($collections['users.json']);

    if (empty($users)) {

        foreach (default_data_structure('users.json') as $user) {

            $ok = firestore_set(
                $collections['users.json'],
                $user['id'],
                $user
            );

            if (!$ok) {
                json_response(false, 'Default user create failed');
            }
        }
    }
}

/**
 * Read a "data file" — actually reads from Firestore, but keeps the
 * exact same signature/return-shape the rest of the app already expects:
 * - settings.json returns a single associative array
 * - everything else returns a plain list of associative arrays (each with 'id')
 */
function read_json($fileName) {
    ensure_data_files();

    if ($fileName === 'settings.json') {
        $doc = firestore_get(FIRESTORE_SETTINGS_COLLECTION, FIRESTORE_SETTINGS_DOC_ID);
        return $doc !== null ? $doc : default_data_structure($fileName);
    }

    $collections = FIRESTORE_LIST_COLLECTIONS;
    if (!isset($collections[$fileName])) {
        return default_data_structure($fileName);
    }
    return firestore_list($collections[$fileName]);
}

/**
 * Write a "data file" — actually writes to Firestore. Mirrors the old
 * "whole file overwrite" semantics: whatever list is passed in becomes
 * the complete contents of that collection (existing docs not present
 * in $data are deleted; everything in $data is created/updated).
 */
function write_json($fileName, $data) {
    ensure_data_files();

    if ($fileName === 'settings.json') {
        return firestore_set(FIRESTORE_SETTINGS_COLLECTION, FIRESTORE_SETTINGS_DOC_ID, $data);
    }

    $collections = FIRESTORE_LIST_COLLECTIONS;
    if (!isset($collections[$fileName])) {
        return false;
    }
    $collection = $collections[$fileName];

    $existing = firestore_list($collection);
    $existingIds = array_map(function ($item) { return (string)$item['id']; }, $existing);
    $newIds = array_map(function ($item) { return (string)$item['id']; }, $data);

    foreach ($existingIds as $id) {
        if (!in_array($id, $newIds, true)) {
            firestore_delete($collection, $id);
        }
    }
    foreach ($data as $item) {
        firestore_set($collection, $item['id'], $item);
    }
    return true;
}

/**
 * Generate the next numeric ID for a list of associative arrays.
 * (Unchanged — still works purely on the in-memory array read_json() returns.)
 */
function next_id($items) {
    $max = 0;
    foreach ($items as $item) {
        if (isset($item['id']) && (int)$item['id'] > $max) {
            $max = (int)$item['id'];
        }
    }
    return $max + 1;
}

/**
 * Sanitize a plain text input string.
 */
function sanitize_text($value) {
    if ($value === null) return '';
    $value = trim($value);
    $value = strip_tags($value);
    return $value;
}

/**
 * Validate an Indian-style phone / WhatsApp number.
 * Accepts 10 digit numbers optionally prefixed with +91 / 91.
 */
function is_valid_phone($number) {
    $number = preg_replace('/[\s\-]/', '', $number);
    return (bool) preg_match('/^(\+?91)?[6-9][0-9]{9}$/', $number);
}

/**
 * Normalize a phone number to international format for wa.me links.
 */
function normalize_phone($number) {
    $number = preg_replace('/[^0-9]/', '', $number);
    if (strlen($number) === 10) {
        $number = '91' . $number;
    } elseif (strlen($number) === 12 && substr($number, 0, 2) === '91') {
        // already fine
    }
    return $number;
}

/**
 * Send a JSON response and terminate execution.
 */
function json_response($success, $message = '', $data = null, $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Read and decode JSON request body (for POST/PUT/DELETE via fetch).
 */
function read_json_body() {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

/**
 * Escape output for safe HTML rendering.
 */
function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
/**
 * Date को DD-MM-YYYY में दिखाने के लिए
 */
function format_date($date) {

    if (empty($date)) {
        return '';
    }

    return date('d-m-Y', strtotime($date));
}