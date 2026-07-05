<?php
/**
 * api/reset.php
 * Resets the application: wipes classes/students/attendance back to empty,
 * restores default settings, and clears uploaded files.
 * The teacher login (users.json) is preserved so the user is not locked out,
 * unless ?keep_users=0 is explicitly passed.
 *
 * POST (JSON body): { confirm: true, keep_users: true }
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login_api();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'अमान्य अनुरोध विधि।', null, 405);
}

$body = read_json_body();
if (empty($body['confirm'])) {
    json_response(false, 'रीसेट की पुष्टि आवश्यक है।', null, 422);
}

write_json('classes.json', default_data_structure('classes.json'));
write_json('students.json', default_data_structure('students.json'));
write_json('attendance.json', default_data_structure('attendance.json'));
write_json('settings.json', default_data_structure('settings.json'));

if (empty($body['keep_users']) === false) {
    // keep_users defaults to true unless explicitly set to false
}
if (isset($body['keep_users']) && $body['keep_users'] === false) {
    write_json('users.json', default_data_structure('users.json'));
}

// Clear uploaded photos / logos.
if (is_dir(UPLOADS_DIR)) {
    foreach (scandir(UPLOADS_DIR) as $item) {
        if ($item === '.' || $item === '..' || $item === '.gitkeep') continue;
        $full = UPLOADS_DIR . $item;
        if (is_file($full)) @unlink($full);
    }
}

json_response(true, 'एप्लिकेशन सफलतापूर्वक रीसेट किया गया।');
