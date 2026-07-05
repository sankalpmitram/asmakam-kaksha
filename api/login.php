<?php
/**
 * api/login.php
 * Handles login form submission (POST JSON: {username, password}).
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'अमान्य अनुरोध विधि।', null, 405);
}

$body = read_json_body();
$username = sanitize_text($body['username'] ?? '');
$password = (string)($body['password'] ?? '');

if ($username === '' || $password === '') {
    json_response(false, 'कृपया उपयोक्ता नाम एवं कूटशब्द दोनों प्रविष्ट करें।', null, 422);
}

if (attempt_login($username, $password)) {
    json_response(true, 'सफलतापूर्वक प्रवेश हुआ।', ['redirect' => 'index.php?page=dashboard']);
} else {
    json_response(false, 'उपयोक्ता नाम अथवा कूटशब्द गलत है।', null, 401);
}
