<?php
/**
 * api/backup.php
 * Exports all JSON data files (and settings) into a single ZIP archive
 * for download.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
ensure_data_files();

if (!class_exists('ZipArchive')) {
    http_response_code(500);
    echo 'सर्वर पर ZIP समर्थन उपलब्ध नहीं है (php-zip एक्सटेंशन आवश्यक है)।';
    exit;
}

$files = ['classes.json', 'students.json', 'attendance.json', 'settings.json', 'users.json'];
$zipName = 'asmakam-kaksha-backup-' . date('Ymd-His') . '.zip';
$zipPath = sys_get_temp_dir() . '/' . $zipName;

$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    echo 'बैकअप फ़ाइल नहीं बन सकी।';
    exit;
}

foreach ($files as $f) {
    $data = read_json($f);
    $zip->addFromString('data/' . $f, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Note: student photos & school logo are no longer backed up here — they
// live permanently in Firebase Storage (see includes/storage.php), so a
// Firestore-data-only backup is sufficient; images are not at risk of
// being lost on redeploy/restart the way local files used to be.

$zip->close();

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipName . '"');
header('Content-Length: ' . filesize($zipPath));
readfile($zipPath);
@unlink($zipPath);
exit;
