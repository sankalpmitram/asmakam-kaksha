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
    $path = DATA_DIR . $f;
    if (file_exists($path)) {
        $zip->addFile($path, 'data/' . $f);
    }
}

// Include uploaded photos / logo as well, so a restore keeps images intact.
if (is_dir(UPLOADS_DIR)) {
    $items = scandir(UPLOADS_DIR);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item === '.gitkeep') continue;
        $full = UPLOADS_DIR . $item;
        if (is_file($full)) {
            $zip->addFile($full, 'uploads/' . $item);
        }
    }
}

$zip->close();

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipName . '"');
header('Content-Length: ' . filesize($zipPath));
readfile($zipPath);
@unlink($zipPath);
exit;
