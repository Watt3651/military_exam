<?php
require_once '../config.php';
require_once '../functions.php';
applySecurityHeaders();
requirePermission('manage:api');

$message = '';
$error = '';
$issuedCredentials = null;
$units = getUnits(false);

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfRequest();
        if (($_POST['action'] ?? '') === 'create') {
      $issuedCredentials = createApiClient(
                trim((string) ($_POST['name'] ?? '')),
                trim((string) ($_POST['token'] ?? '')),
                ($_POST['unit_id'] ?? '') === '' ? null : (int) $_POST['unit_id'],
        isset($_POST['is_active']),
        trim((string) ($_POST['allowed_ips'] ?? '')),
        ($_POST['rate_limit_per_minute'] ?? '') === '' ? null : (int) $_POST['rate_limit_per_minute']
            );
            $message = 'สร้าง API client เรียบร้อยแล้ว';
        }

        if (($_POST['action'] ?? '') === 'update') {
      $issuedCredentials = updateApiClient(
                (int) $_POST['client_id'],
                trim((string) ($_POST['name'] ?? '')),
                ($_POST['unit_id'] ?? '') === '' ? null : (int) $_POST['unit_id'],
                isset($_POST['is_active']),
        trim((string) ($_POST['token'] ?? '')),
        trim((string) ($_POST['allowed_ips'] ?? '')),
        ($_POST['rate_limit_per_minute'] ?? '') === '' ? null : (int) $_POST['rate_limit_per_minute']
            );
            $message = 'อัปเดต API client เรียบร้อยแล้ว';
        }
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$clients = getApiClients();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>จัดการ API Clients — <?= SITE_NAME ?></title>
<link rel="stylesheet" href="../app.css.php">
<style>
.grid{display:grid;grid-template-columns:1.4fr 1.2fr 1.2fr auto;gap:12px}
.hint{font-size:12px;color:var(--theme-text-soft);margin-top:8px}
@media(max-width:900px){.grid{grid-template-columns:1fr}table{display:block;overflow-x:auto;white-space:nowrap}}
</style>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="topbar">
  <div>
    <div style="font-weight:600">จัดการ API Clients</div>
    <div style="font-size:12px;opacity:.8"><?= SITE_NAME ?></div>
  </div>
  <div style="display:flex;gap:14px;flex-wrap:wrap">
    <a href="../index.php">Dashboard</a>
    <?php if (userCanEditReadiness(currentUser())): ?><a href="../input.php">กรอกข้อมูล</a><?php endif; ?>
    <a href="users.php">ผู้ใช้</a>
    <a href="units.php">หน่วย</a>
    <?php if (currentUserCan('manage:items')): ?><a href="readiness-items.php">รายการประเมิน</a><?php endif; ?>
    <?php if (currentUserCan('manage:items')): ?><a href="manual-overrides.php">Manual Overrides</a><?php endif; ?>
    <a href="mock-percent-mappings.php">Mock API Map</a>
    <?php if (currentUserCan('view:audit')): ?><a href="audit-log.php">Audit Log</a><?php endif; ?>
    <a href="../logout.php">ออกจากระบบ</a>
  </div>
</div>

<div class="container">
  <?php if ($message): ?><div class="msg ok"><?= htmlspecialchars($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($issuedCredentials && !empty($issuedCredentials['client_key'])): ?>
  <div class="msg ok">
    Client Key: <strong><?= htmlspecialchars($issuedCredentials['client_key']) ?></strong><br>
    Secret: <strong><?= htmlspecialchars((string) ($issuedCredentials['secret'] ?? '')) ?></strong><br>
    เก็บค่านี้ไว้ทันที เพราะระบบจะไม่แสดง secret เดิมซ้ำอีก
  </div>
  <?php endif; ?>

  <div class="section">
    <h2>เพิ่ม API Client</h2>
    <form method="POST">
      <?= csrfInput() ?>
      <input type="hidden" name="action" value="create">
      <div class="grid">
        <div>
          <label>ชื่อ client</label>
          <input type="text" name="name" required>
        </div>
        <div>
          <label>Secret</label>
          <input type="text" name="token" placeholder="เว้นว่างเพื่อให้ระบบ generate ใหม่">
        </div>
        <div>
          <label>หน่วย</label>
          <select name="unit_id">
            <option value="">- ไม่ผูกกับหน่วย -</option>
            <?php foreach ($units as $unit): ?>
            <option value="<?= (int) $unit['id'] ?>"><?= htmlspecialchars($unit['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Allowed IPs</label>
          <input type="text" name="allowed_ips" placeholder="เช่น 10.0.0.1,10.0.0.0/24">
        </div>
        <div>
          <label>Rate Limit / นาที</label>
          <input type="number" min="1" name="rate_limit_per_minute" value="<?= API_DEFAULT_RATE_LIMIT_PER_MINUTE ?>">
        </div>
        <div style="display:flex;align-items:end;gap:12px;flex-wrap:wrap">
          <label style="display:flex;align-items:center;gap:8px;margin:0"><input type="checkbox" name="is_active" checked style="width:auto"> เปิดใช้งาน</label>
          <button type="submit">บันทึก</button>
        </div>
      </div>
      <div class="hint">ระบบจะ generate `client_key` และ secret ที่แข็งแรงให้ได้อัตโนมัติเมื่อเว้นค่าว่าง</div>
    </form>
  </div>

  <div class="section">
    <h2>รายการ API Clients</h2>
    <table>
      <thead>
        <tr>
          <th>ชื่อ</th>
          <th>รายละเอียด</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($clients as $client): ?>
        <tr>
          <td><?= htmlspecialchars($client['name']) ?><div class="hint">Key: <?= htmlspecialchars((string) ($client['client_key'] ?? '-')) ?></div></td>
          <td>
            <form method="POST" style="display:grid;grid-template-columns:1.1fr 1.1fr 1fr 1fr 1fr auto;gap:10px;align-items:end">
              <?= csrfInput() ?>
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="client_id" value="<?= (int) $client['id'] ?>">
              <div>
                <label>ชื่อ client</label>
                <input type="text" name="name" value="<?= htmlspecialchars($client['name']) ?>" required>
              </div>
              <div>
                <label>Secret ใหม่</label>
                <input type="text" name="token" placeholder="เว้นว่างถ้าไม่เปลี่ยน">
              </div>
              <div>
                <label>หน่วย</label>
                <select name="unit_id">
                  <option value="">- ไม่ผูกกับหน่วย -</option>
                  <?php foreach ($units as $unit): ?>
                  <option value="<?= (int) $unit['id'] ?>" <?= (int) ($client['unit_id'] ?? 0) === (int) $unit['id'] ? 'selected' : '' ?>><?= htmlspecialchars($unit['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label>Allowed IPs</label>
                <input type="text" name="allowed_ips" value="<?= htmlspecialchars((string) ($client['allowed_ips'] ?? '')) ?>" placeholder="เช่น 10.0.0.1,10.0.0.0/24">
              </div>
              <div>
                <label>Rate Limit / นาที</label>
                <input type="number" min="1" name="rate_limit_per_minute" value="<?= (int) ($client['rate_limit_per_minute'] ?? API_DEFAULT_RATE_LIMIT_PER_MINUTE) ?>">
              </div>
              <div>
                <label>สถานะ</label>
                <label style="display:flex;align-items:center;gap:8px;height:40px"><input type="checkbox" name="is_active" <?= (int) $client['is_active'] === 1 ? 'checked' : '' ?> style="width:auto"> เปิดใช้งาน</label>
              </div>
              <button type="submit">อัปเดต</button>
            </form>
            <div class="hint">ผูกหน่วยไว้ได้เพื่อบังคับให้ client นี้ส่งข้อมูลได้เฉพาะหน่วยเดียว | last used: <?= htmlspecialchars((string) ($client['last_used_at'] ?? '-')) ?> / IP <?= htmlspecialchars((string) ($client['last_used_ip'] ?? '-')) ?></div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>