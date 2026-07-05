<?php
/**
 * footer.php
 * Closes main content, renders bottom navigation bar and loads scripts.
 */
$currentPage = $_GET['page'] ?? 'dashboard';
$navItems = [
    ['key' => 'dashboard',  'label' => 'मुख्यपटलम्', 'icon' => 'home'],
    ['key' => 'students',   'label' => 'छात्राः',    'icon' => 'students'],
    ['key' => 'classes',    'label' => 'कक्षाः',      'icon' => 'classes'],
    ['key' => 'attendance', 'label' => 'उपस्थितिः',   'icon' => 'attendance'],
    ['key' => 'reports',    'label' => 'प्रतिवेदनम्', 'icon' => 'reports'],
];
$icons = [
    'home' => '<path fill="currentColor" d="M12 3l9 8h-3v9h-5v-6H11v6H6v-9H3z"/>',
    'students' => '<path fill="currentColor" d="M12 2 1 7l11 5 9-4.09V17h2V7L12 2zM5 13.18v4.72L12 21l7-3.1v-4.72l-7 3.1-7-3.1z"/>',
    'classes' => '<path fill="currentColor" d="M4 4h16v2H4V4zm0 5h16v2H4V9zm0 5h10v2H4v-2zm0 5h10v2H4v-2z"/>',
    'attendance' => '<path fill="currentColor" d="M9 11l3 3L22 4l-1.4-1.4L12 11.2 9 8.2 2 15l1.4 1.4L9 11zM2 20h20v2H2z"/>',
    'reports' => '<path fill="currentColor" d="M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2zm2 12h2v4H7v-4zm4-6h2v10h-2V9zm4 3h2v7h-2v-7z"/>',
];
?>
</main>

<nav class="bottom-nav" id="bottomNav">
    <?php foreach ($navItems as $item): ?>
        <a href="index.php?page=<?php echo $item['key']; ?>"
           class="nav-item <?php echo $currentPage === $item['key'] ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24" width="22" height="22"><?php echo $icons[$item['icon']]; ?></svg>
            <span><?php echo h($item['label']); ?></span>
        </a>
    <?php endforeach; ?>
    <a href="index.php?page=settings"
       class="nav-item <?php echo $currentPage === 'settings' ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24" width="22" height="22"><path fill="currentColor" d="M19.14 12.94a7.14 7.14 0 0 0 .06-.94 7.14 7.14 0 0 0-.06-.94l2.03-1.58a.5.5 0 0 0 .12-.64l-1.92-3.32a.5.5 0 0 0-.6-.22l-2.39.96a7.3 7.3 0 0 0-1.63-.94L14.4 2.8a.5.5 0 0 0-.5-.4h-3.8a.5.5 0 0 0-.5.4l-.36 2.46a7.3 7.3 0 0 0-1.63.94l-2.39-.96a.5.5 0 0 0-.6.22L2.7 8.84a.5.5 0 0 0 .12.64l2.03 1.58c-.04.31-.06.62-.06.94s.02.63.06.94L2.82 14.5a.5.5 0 0 0-.12.64l1.92 3.32a.5.5 0 0 0 .6.22l2.39-.96c.5.4 1.04.72 1.63.94l.36 2.46a.5.5 0 0 0 .5.4h3.8a.5.5 0 0 0 .5-.4l.36-2.46c.59-.22 1.13-.54 1.63-.94l2.39.96a.5.5 0 0 0 .6-.22l1.92-3.32a.5.5 0 0 0-.12-.64l-2.03-1.58zM12 15.5a3.5 3.5 0 1 1 0-7 3.5 3.5 0 0 1 0 7z"/></svg>
        <span>विन्यासः</span>
    </a>
</nav>

<div id="modalRoot"></div>

<script src="assets/js/app.js"></script>
<?php
$pageScripts = [
    'dashboard'  => 'dashboard.js',
    'students'   => 'students.js',
    'classes'    => 'classes.js',
    'attendance' => 'attendance.js',
    'attendance_history' => 'attendance_history.js',
    'reports'    => 'reports.js',
    'settings'   => 'settings.js',
];
if (isset($pageScripts[$currentPage])):
?>
<script src="assets/js/<?php echo $pageScripts[$currentPage]; ?>"></script>
<?php endif; ?>
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
        navigator.serviceWorker.register('service-worker.js').catch(function (err) {
            console.warn('Service worker registration failed:', err);
        });
    });
}
</script>
</body>
</html>
