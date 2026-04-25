<?php
require_once '../config.php';
require_once '../functions.php';
applySecurityHeaders();
requirePermission('manage:units');

$message = '';
$error = '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfRequest();
        if (($_POST['action'] ?? '') === 'create') {
            createUnit(trim((string) ($_POST['code'] ?? '')), trim((string) ($_POST['name'] ?? '')));
            $message = 'เพิ่มหน่วยเรียบร้อยแล้ว';
        }

        if (($_POST['action'] ?? '') === 'update') {
            updateUnit(
                (int) $_POST['unit_id'],
                trim((string) ($_POST['code'] ?? '')),
                trim((string) ($_POST['name'] ?? '')),
                isset($_POST['is_active']) && (int) $_POST['is_active'] === 1
            );
            $message = 'อัปเดตหน่วยเรียบร้อยแล้ว';
        }

        if (($_POST['action'] ?? '') === 'toggle_active') {
            $unitId = (int) ($_POST['unit_id'] ?? 0);
            $nextActiveState = isset($_POST['set_active']);
            $unit = getUnitById($unitId);
            if (!$unit) {
                throw new InvalidArgumentException('ไม่พบหน่วยที่ต้องการเปลี่ยนสถานะ');
            }

            updateUnit($unitId, (string) $unit['code'], (string) $unit['name'], $nextActiveState);
            $message = $nextActiveState ? 'เปิดใช้งานหน่วยเรียบร้อยแล้ว' : 'ปิดใช้งานหน่วยเรียบร้อยแล้ว';
        }
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$units = getUnits(false);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>จัดการหน่วย — <?= SITE_NAME ?></title>
<link rel="stylesheet" href="../app.css.php">
<style>
.grid{display:grid;grid-template-columns:1fr 1.4fr auto;gap:12px;align-items:end}
.unit-row{display:grid;grid-template-columns:1fr 1.6fr 1fr auto;gap:10px;align-items:end}
.unit-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:10px;flex-wrap:wrap}
.unit-warning{margin-top:10px;font-size:13px;color:var(--theme-text-muted)}
@media(max-width:900px){.grid{grid-template-columns:1fr}table{display:block;overflow-x:auto;white-space:nowrap}}
</style>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="topbar">
  <div>
    <div style="font-weight:600">จัดการหน่วย</div>
    <div style="font-size:12px;opacity:.8"><?= SITE_NAME ?></div>
  </div>
  <div style="display:flex;gap:14px;flex-wrap:wrap">
    <a href="../index.php">Dashboard</a>
    <?php if (userCanEditReadiness(currentUser())): ?><a href="../input.php">กรอกข้อมูล</a><?php endif; ?>
    <a href="users.php">จัดการผู้ใช้</a>
    <?php if (currentUserCan('manage:items')): ?><a href="readiness-items.php">รายการประเมิน</a><?php endif; ?>
    <?php if (currentUserCan('manage:api')): ?><a href="api-clients.php">API Clients</a><?php endif; ?>
    <?php if (currentUserCan('manage:api')): ?><a href="mock-percent-mappings.php">Mock API Map</a><?php endif; ?>
    <?php if (currentUserCan('view:audit')): ?><a href="audit-log.php">Audit Log</a><?php endif; ?>
    <a href="../logout.php">ออกจากระบบ</a>
  </div>
</div>

<div class="container container-sm">
  <?php if ($message): ?><div class="msg ok"><?= htmlspecialchars($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="section">
    <h2>เพิ่มหน่วยใหม่</h2>
    <form method="POST" class="grid">
      <?= csrfInput() ?>
      <input type="hidden" name="action" value="create">
      <div>
        <label>Code</label>
        <input type="text" name="code" required>
      </div>
      <div>
        <label>ชื่อหน่วย</label>
        <input type="text" name="name" required>
      </div>
      <button type="submit">เพิ่มหน่วย</button>
    </form>
  </div>

  <div class="section">
    <h2>รายการหน่วย</h2>
    <div class="msg warn">การปิดใช้งานจะทำให้หน่วยนี้หายจากหน้าใช้งานทั่วไป แต่ข้อมูลเดิมและประวัติที่ผูกกับหน่วยยังคงอยู่</div>
    <table>
      <thead>
        <tr>
          <th>Code</th>
          <th>รายละเอียด</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($units as $unit): ?>
        <tr>
          <td><?= htmlspecialchars($unit['code']) ?></td>
          <td>
            <form method="POST" class="unit-row">
              <?= csrfInput() ?>
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="unit_id" value="<?= (int) $unit['id'] ?>">
              <input type="hidden" name="is_active" value="<?= (int) $unit['is_active'] === 1 ? 1 : 0 ?>">
              <div>
                <label>Code</label>
                <input type="text" name="code" value="<?= htmlspecialchars($unit['code']) ?>" required>
              </div>
              <div>
                <label>ชื่อหน่วย</label>
                <input type="text" name="name" value="<?= htmlspecialchars($unit['name']) ?>" required>
              </div>
              <div>
                <label>สถานะ</label>
                <div style="display:flex;align-items:center;height:40px;font-weight:600;color:<?= (int) $unit['is_active'] === 1 ? 'var(--theme-success-text)' : 'var(--theme-danger-text)' ?>">
                  <?= (int) $unit['is_active'] === 1 ? 'เปิดใช้งาน' : 'ปิดใช้งาน' ?>
                </div>
              </div>
              <button type="submit">อัปเดต</button>
            </form>
            <div class="unit-actions">
              <form method="POST" <?= (int) $unit['is_active'] === 1 ? 'onsubmit="return confirm(\'ยืนยันปิดใช้งานหน่วยนี้? หน่วยจะหายจากหน้าใช้งานทั่วไป แต่ข้อมูลเดิมยังคงอยู่\')"' : '' ?>>
                <?= csrfInput() ?>
                <input type="hidden" name="action" value="toggle_active">
                <input type="hidden" name="unit_id" value="<?= (int) $unit['id'] ?>">
                <?php if ((int) $unit['is_active'] === 1): ?>
                <button type="submit" class="btn-secondary">ปิดใช้งาน</button>
                <?php else: ?>
                <input type="hidden" name="set_active" value="1">
                <button type="submit">เปิดใช้งานอีกครั้ง</button>
                <?php endif; ?>
              </form>
            </div>
            <div class="unit-warning">
              <?php if ((int) $unit['is_active'] === 1): ?>
              ปิดใช้งานแล้วหน่วยนี้จะไม่แสดงในหน้า Dashboard และหน้ากรอกข้อมูลทั่วไป แต่ข้อมูล readiness เดิมยังคงอยู่
              <?php else: ?>
              หน่วยนี้ถูกปิดใช้งานอยู่ จึงไม่แสดงในหน้าใช้งานทั่วไปจนกว่าจะเปิดใช้งานอีกครั้ง
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>