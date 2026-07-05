<?php
/**
 * auth.php
 * Session based authentication for the single teacher login.
 */

require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    // Harden session cookie settings.
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => $secure
    ]);
    session_start();
}

/**
 * Returns true if a teacher is currently logged in.
 */
function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

/**
 * Redirect to login page if not authenticated. Used on protected pages.
 */
function require_login() {
    if (!is_logged_in()) {
        header('Location: index.php?page=login');
        exit;
    }
}

/**
 * For API endpoints: stop with a JSON 401 response if not authenticated.
 */
function require_login_api() {
    if (!is_logged_in()) {
        json_response(false, 'सत्र समाप्त हो गया है। कृपया पुनः लॉगिन करें।', null, 401);
    }
}

/**
 * Attempt to log a user in. Returns true/false.
 */
function attempt_login($username, $password) {
    $users = read_json('users.json');
    foreach ($users as $user) {
        if (strtolower($user['username']) === strtolower($username)) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['name'] = $user['name'] ?? $user['username'];
                session_regenerate_id(true);
                return true;
            }
            return false;
        }
    }
    return false;
}

/**
 * Log the current user out.
 */
function do_logout() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
