<?php
require_once 'config.php';
require_once 'functions.php';

applySecurityHeaders();
ensureSessionStarted();
if (!empty($_SESSION['user_id'])) {
  header('Location: ' . defaultLandingPath());
    exit;
}

$error = '';
$message = '';
if (isset($_GET['logged_out'])) {
    $message = 'ออกจากระบบเรียบร้อยแล้ว';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfRequest();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $attempt = attemptLogin($username, $password);
    if (!empty($attempt['ok'])) {
        $user = $attempt['user'];
        startUserSession($user);
        writeAuditLog(db(), (int) $user['id'], $user['display_name'], 'LOGIN', 'user', $user['username']);
      header('Location: ' . defaultLandingPath($user));
        exit;
    }
    $error = (string) ($attempt['message'] ?? 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง');
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>เข้าสู่ระบบ — <?= SITE_NAME ?></title>
<style>
.login-title{font-size:18px}
</style>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="theme.css.php">
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="auth-shell">
<div class="auth-card auth-card-sm">
  <div class="auth-header">
    <div class="auth-icon">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
      </svg>
    </div>
    <div class="auth-title login-title">เข้าสู่ระบบเจ้าหน้าที่</div>
    <div class="auth-subtitle"><?= SITE_NAME ?></div>
  </div>
  <?php if ($message): ?>
    <div class="alert-ok"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="POST">
    <?= csrfInput() ?>
    <label class="form-label" for="username">Username</label>
    <input class="form-input" id="username" type="text" name="username" autocomplete="username" required>

    <label class="form-label" for="password">Password</label>
    <input class="form-input" id="password" type="password" name="password" autocomplete="current-password" required>

    <button class="btn-primary" type="submit">เข้าสู่ระบบ</button>
  </form>
  <div class="muted-copy">นโยบายรหัสผ่าน: <?= htmlspecialchars(passwordPolicyText()) ?></div>
</div>
</div>
</body>
</html>
