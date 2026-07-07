<?php
/**
 * header.php
 * Shared <head> and top app-bar markup for all authenticated pages.
 * Expects $settings (array) and $pageTitle (string) to be set by the caller.
 */
if (!isset($settings)) {
    $settings = read_json('settings.json');
}
$pageTitle = $pageTitle ?? 'मुख्यपटलम्';
?>
<!DOCTYPE html>
<html lang="hi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
<meta name="theme-color" content="#FF9933">
<meta name="description" content="अस्माकं कक्षा - कक्षा प्रबंधन प्रणाली">
<title><?php echo h($pageTitle); ?> | अस्माकं कक्षा</title>
<link rel="manifest" href="manifest.json">
<link rel="icon" href="assets/icons/icon-192.png">
<link rel="apple-touch-icon" href="assets/icons/icon-192.png">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="theme-<?php echo h($settings['theme'] ?? 'light'); ?>" data-page="<?php echo h($_GET['page'] ?? 'dashboard'); ?>">
<div id="toast" class="toast" role="status" aria-live="polite"></div>

<header class="app-bar">
    <div class="app-bar-left">
        <?php if (!empty($settings['school_logo'])): ?>
            <img src="<?php echo h($settings['school_logo']); ?>" alt="लोगो" class="school-logo">
        <?php endif; ?>
        <div class="app-bar-titles">
            <span class="app-bar-title"><?php echo h($pageTitle); ?></span>
            <span class="app-bar-subtitle"><?php echo h($settings['school_name'] ?? 'अस्माकं कक्षा'); ?></span>
        </div>
    </div>
    <div class="app-bar-right">
        <button id="themeToggleBtn" class="icon-btn" title="डार्क / लाइट मोड" aria-label="थीम बदलें">
            <svg id="themeIcon" viewBox="0 0 24 24" width="22" height="22"><path fill="currentColor" d="M12 3a9 9 0 1 0 9 9c0-.46-.04-.92-.1-1.36a5.389 5.389 0 0 1-4.4 2.26 5.403 5.403 0 0 1-3.16-9.77A8.98 8.98 0 0 0 12 3z"/></svg>
        </button>
        <a href="api/logout.php" class="icon-btn" title="लॉगआउट" aria-label="लॉगआउट" onclick="return confirm('क्या आप लॉगआउट करना चाहते हैं?');">
            <svg viewBox="0 0 24 24" width="22" height="22"><path fill="currentColor" d="M16 17v-3H9v-4h7V7l5 5-5 5M14 2a2 2 0 0 1 2 2v2h-2V4H5v16h9v-2h2v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9z"/></svg>
        </a>
    </div>
</header>

<main class="app-content" id="appContent">
