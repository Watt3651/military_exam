<?php
require_once 'config.php';
require_once 'functions.php';

applySecurityHeaders();
ensureSessionStarted();

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$error = '';
$message = '';
$user = $token !== '' ? findUserByPasswordResetToken($token) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfRequest();
    try {
        $user = completePasswordReset(
            $token,
            (string) ($_POST['password'] ?? ''),
            (string) ($_POST['password_confirm'] ?? '')
        );
        $message = 'รีเซ็ตรหัสผ่านเรียบร้อยแล้ว กรุณาเข้าสู่ระบบด้วยรหัสผ่านใหม่';
    } catch (Throwable $e) {
        $error = $e->getMessage();
        $user = $token !== '' ? findUserByPasswordResetToken($token) : null;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reset Password — <?= SITE_NAME ?></title>
<style>
.auth-account{font-size:13px;color:var(--theme-text-muted);margin-top:4px}
</style>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="theme.css.php">
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="auth-shell">
<div class="auth-card">
  <div class="auth-header">
    <div class="auth-icon">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="5" y="11" width="14" height="10" rx="2"></rect>
        <path d="M8 11V8a4 4 0 1 1 8 0v3"></path>
      </svg>
    </div>
    <div class="auth-title">รีเซ็ตรหัสผ่าน</div>
    <div class="auth-subtitle"><?= SITE_NAME ?></div>
  </div>

  <?php if ($message): ?><div class="alert-ok"><?= htmlspecialchars($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <?php if ($user && !$message): ?>
  <form method="POST">
    <?= csrfInput() ?>
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
    <div class="auth-account">บัญชี: <strong><?= htmlspecialchars($user['username']) ?></strong></div>
    <label class="form-label">รหัสผ่านใหม่</label>
    <input class="form-input" type="password" name="password" required>
    <label class="form-label">ยืนยันรหัสผ่านใหม่</label>
    <input class="form-input" type="password" name="password_confirm" required>
    <div class="muted-copy">นโยบายรหัสผ่าน: <?= htmlspecialchars(passwordPolicyText()) ?></div>
    <button class="btn-primary" type="submit">บันทึกรหัสผ่านใหม่</button>
  </form>
  <?php elseif (!$message): ?>
  <div class="alert-error">ลิงก์ reset ไม่ถูกต้องหรือหมดอายุ</div>
  <?php endif; ?>

  <a class="back-link" href="login.php">กลับหน้าเข้าสู่ระบบ</a>
</div>
</div>
</body>
</html>