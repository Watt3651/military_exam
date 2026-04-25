<?php
require_once '../config.php';
require_once '../functions.php';

applySecurityHeaders();
requirePermission('manage:items');

$message = '';
$error = '';
$rowOptions = readinessRowOptions();
$columnOptions = readinessColumnOptions();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrfRequest();

        if (($_POST['action'] ?? '') === 'create') {
            createReadinessItem(
                (string) ($_POST['row_id'] ?? ''),
                (string) ($_POST['col_id'] ?? ''),
                trim((string) ($_POST['label'] ?? '')),
                trim((string) ($_POST['url'] ?? ''))
            );
            $message = 'เพิ่มรายการเรียบร้อยแล้ว';
        }

        if (($_POST['action'] ?? '') === 'update') {
            updateReadinessItem(
                (int) ($_POST['item_id'] ?? 0),
                trim((string) ($_POST['label'] ?? '')),
                trim((string) ($_POST['url'] ?? ''))
            );
            $message = 'อัปเดตรายการเรียบร้อยแล้ว';
        }

        if (($_POST['action'] ?? '') === 'delete') {
            deleteReadinessItem((int) ($_POST['item_id'] ?? 0));
            $message = 'ลบรายการเรียบร้อยแล้ว';
        }

        if (($_POST['action'] ?? '') === 'move') {
          moveReadinessItem((int) ($_POST['item_id'] ?? 0), (string) ($_POST['direction'] ?? ''));
          $message = 'ปรับลำดับรายการเรียบร้อยแล้ว';
        }
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$items = getReadinessItems();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>จัดการรายการประเมิน — <?= SITE_NAME ?></title>
<link rel="stylesheet" href="../app.css.php">
<style>
.grid{display:grid;grid-template-columns:1fr 1fr 1.6fr 1.6fr auto;gap:12px;align-items:end}
.inline-actions{margin-top:8px;display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap}
.hint{font-size:12px;color:var(--theme-text-soft);line-height:1.6}
@media(max-width:1100px){.grid{grid-template-columns:1fr}table{display:block;overflow-x:auto;white-space:nowrap}}
</style>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="topbar">
  <div>
    <div style="font-weight:600">จัดการรายการประเมิน</div>
    <div style="font-size:12px;opacity:.8"><?= SITE_NAME ?></div>
  </div>
  <div style="display:flex;gap:14px;flex-wrap:wrap">
    <a href="../index.php">Dashboard</a>
    <?php if (userCanEditReadiness(currentUser())): ?><a href="../input.php">กรอกข้อมูล</a><?php endif; ?>
    <a href="readiness-display-settings.php">การแสดงผลมิติ</a>
    <a href="readiness-sync.php">Sync Config</a>
    <?php if (currentUserCan('manage:users')): ?><a href="users.php">ผู้ใช้</a><?php endif; ?>
    <?php if (currentUserCan('manage:units')): ?><a href="units.php">หน่วย</a><?php endif; ?>
    <?php if (currentUserCan('manage:api')): ?><a href="api-clients.php">API Clients</a><?php endif; ?>
    <?php if (currentUserCan('manage:api')): ?><a href="mock-percent-mappings.php">Mock API Map</a><?php endif; ?>
    <?php if (currentUserCan('view:audit')): ?><a href="audit-log.php">Audit Log</a><?php endif; ?>
    <a href="../logout.php">ออกจากระบบ</a>
  </div>
</div>

<div class="container container-lg">
  <?php if ($message): ?><div class="msg ok"><?= htmlspecialchars($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="section">
    <h2>แนวทางใช้งาน</h2>
    <div class="hint">
      ข้อมูลในหน้านี้เป็น source of truth สำหรับรายการในตาราง `readiness_items` ที่หน้า Dashboard, Input และ API validation ใช้งานอยู่จริง<br>
      ถ้าต้องการ seed หรือ override จาก `ITEMS` ใน config ให้ตั้งค่า `READINESS_SYNC_ITEMS_FROM_CONFIG=1` ชั่วคราว แล้ว reload หนึ่งครั้ง จากนั้นควรปิดกลับเป็น `0`<br>
      การ sync จาก config เหมาะกับการ seed ครั้งแรกหรือแก้ label/url เป็นหลัก ถ้ามีข้อมูลใช้งานแล้วไม่ควร reorder หรือลบ item จาก config ตรงๆ<br>
      ถ้าต้องการ preview diff, export backup หรือ apply sync แบบมี guard ให้ใช้หน้า <a href="readiness-sync.php">Sync Config</a>
    </div>
  </div>

  <div class="section">
    <h2>เพิ่มรายการใหม่</h2>
    <form method="POST">
      <?= csrfInput() ?>
      <input type="hidden" name="action" value="create">
      <div class="grid">
        <div>
          <label>มิติ</label>
          <select name="row_id">
            <?php foreach ($rowOptions as $rowId => $rowLabel): ?>
            <option value="<?= htmlspecialchars($rowId) ?>"><?= htmlspecialchars($rowLabel) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>องค์ประกอบ</label>
          <select name="col_id">
            <?php foreach ($columnOptions as $colId => $colLabel): ?>
            <option value="<?= htmlspecialchars($colId) ?>"><?= htmlspecialchars($colLabel) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>ชื่อรายการ</label>
          <input type="text" name="label" required>
        </div>
        <div>
          <label>URL</label>
          <input type="text" name="url" placeholder="เว้นว่างได้">
        </div>
        <button type="submit">เพิ่มรายการ</button>
      </div>
    </form>
  </div>

  <div class="section">
    <h2>รายการปัจจุบัน</h2>
    <table>
      <thead>
        <tr>
          <th>มิติ</th>
          <th>องค์ประกอบ</th>
          <th>ลำดับ</th>
          <th>รายละเอียด</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $item): ?>
        <tr>
          <td><?= htmlspecialchars($rowOptions[$item['row_id']] ?? (string) $item['row_id']) ?></td>
          <td><?= htmlspecialchars($columnOptions[$item['col_id']] ?? (string) $item['col_id']) ?></td>
          <td><?= (int) $item['item_index'] ?></td>
          <td>
            <form method="POST" style="display:grid;grid-template-columns:1.5fr 1.5fr auto auto;gap:10px;align-items:end">
              <?= csrfInput() ?>
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
              <div>
                <label>ชื่อรายการ</label>
                <input type="text" name="label" value="<?= htmlspecialchars($item['label']) ?>" required>
              </div>
              <div>
                <label>URL</label>
                <input type="text" name="url" value="<?= htmlspecialchars((string) ($item['url'] ?? '')) ?>">
              </div>
              <button type="submit">อัปเดต</button>
            </form>
            <div class="inline-actions">
              <form method="POST">
                <?= csrfInput() ?>
                <input type="hidden" name="action" value="move">
                <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                <input type="hidden" name="direction" value="up">
                <button class="btn-secondary" type="submit">เลื่อนขึ้น</button>
              </form>
              <form method="POST">
                <?= csrfInput() ?>
                <input type="hidden" name="action" value="move">
                <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                <input type="hidden" name="direction" value="down">
                <button class="btn-secondary" type="submit">เลื่อนลง</button>
              </form>
              <form method="POST">
                <?= csrfInput() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                <button class="btn-danger" type="submit" onclick="return confirm('ยืนยันการลบรายการนี้? ระบบจะลบค่าประเมินของรายการนี้ทุกหน่วยด้วย')">ลบรายการ</button>
              </form>
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