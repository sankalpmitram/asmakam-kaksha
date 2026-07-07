<?php
/**
 * api/logout.php
 * Destroys the session and redirects to the login page.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

do_logout();
header('Location: ../index.php?page=login');
exit;
