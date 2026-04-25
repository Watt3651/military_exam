<?php
require_once 'config.php';
require_once 'functions.php';

applySecurityHeaders();
ensureSessionStarted();

$user = currentUser();
if ($user) {
    writeAuditLog(db(), (int) $user['id'], $user['name'], 'LOGOUT', 'user', $user['username']);
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
}

session_unset();
session_destroy();

header('Location: login.php?logged_out=1', true, 302);
exit;
