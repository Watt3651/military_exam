<?php
require_once '../config.php';
require_once '../functions.php';

applySecurityHeaders();
requirePermission('manage:users');

$message = '';
$error = '';
$issuedReset = null;
$units = getUnits(false);
$roles = roleOptions();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrfRequest();

        if (($_POST['action'] ?? '') === 'create') {
            createUser(
                trim((string) ($_POST['username'] ?? '')),
                trim((string) ($_POST['display_name'] ?? '')),
                (string) ($_POST['password'] ?? ''),
                (string) ($_POST['role'] ?? 'viewer'),
                ($_POST['unit_id'] ?? '') === '' ? null : (int) $_POST['unit_id'],
                isset($_POST['is_active'])
            );
            $message = 'สร้างผู้ใช้เรียบร้อยแล้ว';
        }

        if (($_POST['action'] ?? '') === 'update') {
            updateUser(
                (int) $_POST['user_id'],
                trim((string) ($_POST['display_name'] ?? '')),
                (string) ($_POST['role'] ?? 'viewer'),
                ($_POST['unit_id'] ?? '') === '' ? null : (int) $_POST['unit_id'],
                isset($_POST['is_active']),
                trim((string) ($_POST['password'] ?? ''))
            );
            $message = 'อัปเดตผู้ใช้เรียบร้อยแล้ว';
        }

        if (($_POST['action'] ?? '') === 'issue_reset') {
            $issuedReset = issuePasswordResetForUser((int) $_POST['user_id']);
            $message = 'ออก password reset token เรียบร้อยแล้ว';
        }
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$users = getUsers();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>จัดการผู้ใช้ — <?= SITE_NAME ?></title>
<link rel="stylesheet" href="../app.css.php">
<style>
.grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px}
.grid .full{grid-column:span 5}
.reset-link{display:inline-block;margin-top:4px;word-break:break-all}
@media(max-width:900px){.grid{grid-template-columns:1fr 1fr}.grid .full{grid-column:span 2}table{display:block;overflow-x:auto;white-space:nowrap}}
</style>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="topbar">
  <div>
    <div style="font-weight:600">จัดการผู้ใช้</div>
    <div style="font-size:12px;opacity:.8"><?= SITE_NAME ?></div>
  </div>
  <div style="display:flex;gap:14px;flex-wrap:wrap">
    <a href="../index.php">Dashboard</a>
    <?php if (userCanEditReadiness(currentUser())): ?><a href="../input.php">กรอกข้อมูล</a><?php endif; ?>
    <a href="units.php">จัดการหน่วย</a>
    <?php if (currentUserCan('manage:items')): ?><a href="readiness-items.php">รายการประเมิน</a><?php endif; ?>
    <?php if (currentUserCan('manage:api')): ?><a href="api-clients.php">API Clients</a><?php endif; ?>
    <?php if (currentUserCan('manage:api')): ?><a href="mock-percent-mappings.php">Mock API Map</a><?php endif; ?>
    <?php if (currentUserCan('view:audit')): ?><a href="audit-log.php">Audit Log</a><?php endif; ?>
    <a href="../logout.php">ออกจากระบบ</a>
  </div>
</div>

<div class="container">
  <?php if ($message): ?><div class="msg ok"><?= htmlspecialchars($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($issuedReset): ?>
  <div class="msg ok">
    Reset link สำหรับ <?= htmlspecialchars($issuedReset['user']['username']) ?><br>
    <a class="reset-link" href="<?= htmlspecialchars($issuedReset['url']) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($issuedReset['url']) ?></a><br>
    หมดอายุ: <?= htmlspecialchars($issuedReset['expires_at']) ?>
  </div>
  <?php endif; ?>

  <div class="section">
    <h2>เพิ่มผู้ใช้ใหม่</h2>
    <form method="POST">
      <?= csrfInput() ?>
      <input type="hidden" name="action" value="create">
      <div class="grid">
        <div>
          <label>Username</label>
          <input type="text" name="username" required>
        </div>
        <div>
          <label>ชื่อแสดงผล</label>
          <input type="text" name="display_name" required>
        </div>
        <div>
          <label>Role</label>
          <select name="role">
            <?php foreach ($roles as $roleKey => $roleLabel): ?>
            <option value="<?= htmlspecialchars($roleKey) ?>"><?= htmlspecialchars($roleLabel) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>หน่วย</label>
          <select name="unit_id">
            <option value="">- ไม่ระบุ -</option>
            <?php foreach ($units as $unit): ?>
            <option value="<?= (int) $unit['id'] ?>"><?= htmlspecialchars($unit['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>รหัสผ่าน</label>
          <input type="password" name="password" required>
        </div>
        <div class="full" style="font-size:12px;color:var(--theme-text-soft)">นโยบายรหัสผ่าน: <?= htmlspecialchars(passwordPolicyText()) ?></div>
        <div class="full" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
          <label style="display:flex;align-items:center;gap:8px;margin:0"><input type="checkbox" name="is_active" checked style="width:auto"> เปิดใช้งาน</label>
          <button type="submit">บันทึกผู้ใช้</button>
        </div>
      </div>
    </form>
  </div>

  <div class="section">
    <h2>รายการผู้ใช้</h2>
    <table>
      <thead>
        <tr>
          <th>Username</th>
          <th>รายละเอียด</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $managedUser): ?>
        <tr>
          <td><?= htmlspecialchars($managedUser['username']) ?></td>
          <td>
            <form method="POST" style="display:grid;grid-template-columns:1.2fr 1fr 1fr 1fr 1fr auto;gap:10px;align-items:end">
              <?= csrfInput() ?>
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="user_id" value="<?= (int) $managedUser['id'] ?>">
              <div>
                <label>ชื่อแสดงผล</label>
                <input type="text" name="display_name" value="<?= htmlspecialchars($managedUser['display_name']) ?>" required>
              </div>
              <div>
                <label>Role</label>
                <select name="role">
                  <?php foreach ($roles as $roleKey => $roleLabel): ?>
                  <option value="<?= htmlspecialchars($roleKey) ?>" <?= $managedUser['role'] === $roleKey ? 'selected' : '' ?>><?= htmlspecialchars($roleLabel) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label>หน่วย</label>
                <select name="unit_id">
                  <option value="">- ไม่ระบุ -</option>
                  <?php foreach ($units as $unit): ?>
                  <option value="<?= (int) $unit['id'] ?>" <?= (int) ($managedUser['unit_id'] ?? 0) === (int) $unit['id'] ? 'selected' : '' ?>><?= htmlspecialchars($unit['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label>รหัสผ่านใหม่</label>
                <input type="password" name="password" placeholder="เว้นว่างถ้าไม่เปลี่ยน">
              </div>
              <div>
                <label>สถานะ</label>
                <label style="display:flex;align-items:center;gap:8px;height:40px"><input type="checkbox" name="is_active" <?= (int) $managedUser['is_active'] === 1 ? 'checked' : '' ?> style="width:auto"> เปิดใช้งาน</label>
              </div>
              <button type="submit">อัปเดต</button>
            </form>
            <form method="POST" style="margin-top:8px;display:flex;gap:10px;align-items:center;flex-wrap:wrap">
              <?= csrfInput() ?>
              <input type="hidden" name="action" value="issue_reset">
              <input type="hidden" name="user_id" value="<?= (int) $managedUser['id'] ?>">
              <button type="submit">ออก Reset Link</button>
              <span style="font-size:12px;color:var(--theme-text-soft)">failed: <?= (int) ($managedUser['failed_login_attempts'] ?? 0) ?> | lock until: <?= htmlspecialchars((string) ($managedUser['locked_until'] ?? '-')) ?></span>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>