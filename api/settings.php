<?php
/**
 * api/settings.php
 * GET   -> returns current settings
 * POST  -> updates settings (multipart/form-data, supports logo upload)
 *          Special field "action=change_password" with old_password/new_password
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login_api();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $settings = read_json('settings.json');
    json_response(true, '', $settings);
}

if ($method !== 'POST') {
    json_response(false, 'अमान्य अनुरोध विधि।', null, 405);
}

// ---- Change password ----
if (($_POST['action'] ?? ($_GET['action'] ?? '')) === 'change_password') {
    $body = $_POST ? $_POST : read_json_body();
    $oldPassword = (string)($body['old_password'] ?? '');
    $newPassword = (string)($body['new_password'] ?? '');

    if (strlen($newPassword) < 6) {
        json_response(false, 'नया कूटशब्द कम से कम 6 अक्षर का होना चाहिए।', null, 422);
    }

    $users = read_json('users.json');
    $found = false;
    foreach ($users as &$u) {
        if ((int)$u['id'] === (int)$_SESSION['user_id']) {
            if (!password_verify($oldPassword, $u['password'])) {
                json_response(false, 'पुराना कूटशब्द गलत है।', null, 401);
            }
            $u['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
            $found = true;
            break;
        }
    }
    unset($u);
    if (!$found) {
        json_response(false, 'उपयोक्ता नहीं मिला।', null, 404);
    }
    write_json('users.json', $users);
    json_response(true, 'कूटशब्द सफलतापूर्वक बदला गया।');
}

// ---- Update general settings ----
$settings = read_json('settings.json');

$settings['school_name'] = sanitize_text($_POST['school_name'] ?? $settings['school_name']);
$settings['teacher_name'] = sanitize_text($_POST['teacher_name'] ?? $settings['teacher_name']);
$settings['session'] = sanitize_text($_POST['session'] ?? $settings['session']);
$settings['default_attendance_time'] = sanitize_text($_POST['default_attendance_time'] ?? $settings['default_attendance_time']);
$settings['whatsapp_template'] = $_POST['whatsapp_template'] ?? $settings['whatsapp_template'];
if (isset($_POST['theme']) && in_array($_POST['theme'], ['light', 'dark'], true)) {
    $settings['theme'] = $_POST['theme'];
}

// Logo upload.
if (isset($_FILES['school_logo']) && $_FILES['school_logo']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $_FILES['school_logo']['tmp_name']);
    finfo_close($finfo);
    if (isset($allowed[$mime]) && $_FILES['school_logo']['size'] <= 2 * 1024 * 1024) {
        $ext = $allowed[$mime];
        $filename = 'logo_' . uniqid() . '.' . $ext;
        $dest = UPLOADS_DIR . $filename;
        if (move_uploaded_file($_FILES['school_logo']['tmp_name'], $dest)) {
            if (!empty($settings['school_logo'])) {
                $old = __DIR__ . '/../' . $settings['school_logo'];
                if (is_file($old)) @unlink($old);
            }
            $settings['school_logo'] = 'uploads/' . $filename;
        }
    }
}

write_json('settings.json', $settings);
json_response(true, 'विन्यास सफलतापूर्वक सहेजा गया।', $settings);
