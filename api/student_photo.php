<?php
/**
 * api/student_photo.php
 * Uploads / replaces a student's photo. Separate from the main PUT
 * endpoint because PHP cannot parse multipart bodies for PUT requests.
 *
 * POST (multipart/form-data): id, photo
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login_api();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'अमान्य अनुरोध विधि।', null, 405);
}

$id = (int)($_POST['id'] ?? 0);
if (!$id) {
    json_response(false, 'छात्र आईडी आवश्यक है।', null, 422);
}

if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    json_response(false, 'फोटो अपलोड नहीं हुई।', null, 422);
}

$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $_FILES['photo']['tmp_name']);
finfo_close($finfo);

if (!isset($allowed[$mime])) {
    json_response(false, 'केवल JPG, PNG अथवा WEBP छवि स्वीकार्य है।', null, 422);
}
if ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
    json_response(false, 'छवि का आकार 2MB से कम होना चाहिए।', null, 422);
}

$students = read_json('students.json');
$found = false;
$oldPhoto = null;
foreach ($students as &$s) {
    if ((int)$s['id'] === $id) {
        $oldPhoto = $s['photo'] ?? null;
        $ext = $allowed[$mime];
        $filename = 'student_' . uniqid() . '.' . $ext;
        $dest = UPLOADS_DIR . $filename;
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
            json_response(false, 'फोटो सहेजने में त्रुटि हुई।', null, 500);
        }
        $s['photo'] = 'uploads/' . $filename;
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

if ($oldPhoto) {
    $path = __DIR__ . '/../' . $oldPhoto;
    if (is_file($path)) @unlink($path);
}

json_response(true, 'फोटो सफलतापूर्वक अद्यतन हुई।', $updated);
