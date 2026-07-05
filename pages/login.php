<?php
/**
 * login.php
 * Public login screen. Submits credentials to api/login.php via fetch.
 */
$settings = read_json('settings.json');
?>
<!DOCTYPE html>
<html lang="hi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
<meta name="theme-color" content="#FF9933">
<title>लॉगिन | अस्माकं कक्षा</title>
<link rel="manifest" href="manifest.json">
<link rel="icon" href="assets/icons/icon-192.png">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="theme-light login-body">
<div id="toast" class="toast" role="status" aria-live="polite"></div>
<div class="login-wrapper">
    <div class="login-card">
        <div class="login-logo">
            <?php if (!empty($settings['school_logo'])): ?>
                <img src="<?php echo h($settings['school_logo']); ?>" alt="लोगो">
            <?php else: ?>
                <div class="login-logo-placeholder">अ</div>
            <?php endif; ?>
        </div>
        <h1 class="login-title">अस्माकं कक्षा</h1>
        <p class="login-subtitle">कक्षा प्रबंधन प्रणाली</p>

        <form id="loginForm" class="login-form" autocomplete="off">
            <div class="form-group">
                <label for="username">उपयोक्ता नाम</label>
                <input type="text" id="username" name="username" required autofocus placeholder="उपयोक्ता नाम प्रविष्ट करें">
            </div>
            <div class="form-group">
                <label for="password">कूटशब्द</label>
                <input type="password" id="password" name="password" required placeholder="कूटशब्द प्रविष्ट करें">
            </div>
            <button type="submit" class="btn btn-primary btn-block" id="loginBtn">प्रवेश करें</button>
            <p class="login-error" id="loginError"></p>
        </form>
        <p class="login-hint">डिफ़ॉल्ट: teacher / teacher@123</p>
    </div>
</div>
<script src="assets/js/login.js"></script>
</body>
</html>
