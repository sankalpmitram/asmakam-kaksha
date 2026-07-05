<?php
/**
 * api/restore.php
 * Restores JSON data files (and uploaded photos) from a ZIP backup
 * previously created by api/backup.php.
 *
 * POST (multipart/form-data): backup_zip (file)
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login_api();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'अमान्य अनुरोध विधि।', null, 405);
}

if (!class_exists('ZipArchive')) {
    json_response(false, 'सर्वर पर ZIP समर्थन उपलब्ध नहीं है (php-zip एक्सटेंशन आवश्यक है)।', null, 500);
}

if (!isset($_FILES['backup_zip']) || $_FILES['backup_zip']['error'] !== UPLOAD_ERR_OK) {
    json_response(false, 'कृपया एक मान्य ZIP फ़ाइल चुनें।', null, 422);
}

$file = $_FILES['backup_zip'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowedMimes = ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'];
if (!in_array($mime, $allowedMimes, true) && strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'zip') {
    json_response(false, 'केवल ZIP फ़ाइल स्वीकार्य है।', null, 422);
}

$zip = new ZipArchive();
if ($zip->open($file['tmp_name']) !== true) {
    json_response(false, 'ZIP फ़ाइल खोली नहीं जा सकी।', null, 422);
}

$allowedDataFiles = ['classes.json', 'students.json', 'attendance.json', 'settings.json', 'users.json'];
$extractedAny = false;

for ($i = 0; $i < $zip->numFiles; $i++) {
    $entryName = $zip->getNameIndex($i);

    // Restore data/*.json files, validating JSON before writing.
    if (preg_match('#^data/([a-zA-Z0-9_\-]+\.json)$#', $entryName, $m) && in_array($m[1], $allowedDataFiles, true)) {
        $contents = $zip->getFromIndex($i);
        $decoded = json_decode($contents, true);
        if ($decoded !== null && json_last_error() === JSON_ERROR_NONE) {
            write_json($m[1], $decoded);
            $extractedAny = true;
        }
        continue;
    }

    // Restore uploads/* files (photos, logos).
    if (preg_match('#^uploads/([a-zA-Z0-9_\-.]+)$#', $entryName, $m)) {
        $contents = $zip->getFromIndex($i);
        if ($contents !== false) {
            file_put_contents(UPLOADS_DIR . $m[1], $contents);
            $extractedAny = true;
        }
    }
}

$zip->close();

if (!$extractedAny) {
    json_response(false, 'ZIP में कोई मान्य बैकअप डेटा नहीं मिला।', null, 422);
}

json_response(true, 'डेटा सफलतापूर्वक पुनर्स्थापित हुआ। कृपया पुनः लॉगिन करें यदि आवश्यक हो।');
