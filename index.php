<?php
/**
 * index.php
 * Front controller. Routes requests to the correct page based on
 * the ?page= query parameter. Handles auth gating.
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

ensure_data_files();

$allowedPages = ['dashboard', 'students', 'classes', 'attendance', 'attendance_history', 'reports', 'settings'];
$page = $_GET['page'] ?? 'dashboard';

// ---- Login page (public) ----
if ($page === 'login') {
    if (is_logged_in()) {
        header('Location: index.php?page=dashboard');
        exit;
    }
    require __DIR__ . '/pages/login.php';
    exit;
}

// ---- Everything else requires login ----
require_login();

if (!in_array($page, $allowedPages, true)) {
    $page = 'dashboard';
}

$settings = read_json('settings.json');

$titles = [
    'dashboard' => 'मुख्यपटलम्',
    'students' => 'छात्राः',
    'classes' => 'कक्षाः',
    'attendance' => 'उपस्थितिः',
    'attendance_history' => 'उपस्थिति इतिहासः',
    'reports' => 'प्रतिवेदनम्',
    'settings' => 'विन्यासः',
];
$pageTitle = $titles[$page] ?? 'अस्माकं कक्षा';

require __DIR__ . '/includes/header.php';
require __DIR__ . '/pages/' . $page . '.php';
require __DIR__ . '/includes/footer.php';
