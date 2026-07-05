<?php
/**
 * functions.php
 * Core helper functions used across the application.
 * Handles JSON file storage (with file locking), input sanitization,
 * JSON API responses and small utility helpers.
 */

define('DATA_DIR', __DIR__ . '/../data/');
define('UPLOADS_DIR', __DIR__ . '/../uploads/');

/**
 * Default skeletons for each data file.
 * Used to auto-create files the first time they are needed.
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
 * Ensure the data directory and all required JSON files exist.
 * Called on every request bootstrap.
 */
function ensure_data_files() {
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0775, true);
    }
    if (!is_dir(UPLOADS_DIR)) {
        mkdir(UPLOADS_DIR, 0775, true);
    }
    $files = ['classes.json', 'students.json', 'attendance.json', 'settings.json', 'users.json'];
    foreach ($files as $file) {
        $path = DATA_DIR . $file;
        if (!file_exists($path)) {
            file_put_contents($path, json_encode(default_data_structure($file), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }
}

/**
 * Read a JSON data file safely (shared lock).
 * Returns an associative array / list. Never returns false.
 */
function read_json($fileName) {
    ensure_data_files();
    $path = DATA_DIR . $fileName;
    if (!file_exists($path)) {
        return default_data_structure($fileName);
    }
    $fp = fopen($path, 'r');
    if (!$fp) {
        return default_data_structure($fileName);
    }
    $data = default_data_structure($fileName);
    if (flock($fp, LOCK_SH)) {
        $contents = stream_get_contents($fp);
        flock($fp, LOCK_UN);
        $decoded = json_decode($contents, true);
        if (json_last_error() === JSON_ERROR_NONE && $decoded !== null) {
            $data = $decoded;
        }
    }
    fclose($fp);
    return $data;
}

/**
 * Write data to a JSON file safely (exclusive lock) to avoid
 * corruption when multiple requests write at the same time.
 */
function write_json($fileName, $data) {
    ensure_data_files();
    $path = DATA_DIR . $fileName;
    $fp = fopen($path, 'c+');
    if (!$fp) {
        return false;
    }
    $result = false;
    if (flock($fp, LOCK_EX)) {
        ftruncate($fp, 0);
        rewind($fp);
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        fwrite($fp, $json);
        fflush($fp);
        flock($fp, LOCK_UN);
        $result = true;
    }
    fclose($fp);
    return $result;
}

/**
 * Generate the next numeric ID for a list of associative arrays.
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
