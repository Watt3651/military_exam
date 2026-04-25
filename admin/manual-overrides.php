<?php
require_once '../config.php';
require_once '../functions.php';

applySecurityHeaders();
requirePermission('manage:items');

$currentUser = currentUser();
$message = '';
$error = '';

$units = getUnits();
$unitMap = [];
foreach ($units as $unit) {
    $unitMap[(int) $unit['id']] = $unit;
}

$defaultUnitId = 0;
$requestedUnitId = isset($_REQUEST['unit_id']) ? (int) $_REQUEST['unit_id'] : $defaultUnitId;
$selectedUnitId = ($requestedUnitId > 0 && isset($unitMap[$requestedUnitId])) ? $requestedUnitId : 0;

$items = getItemsDefinition();
$rowOptions = readinessRowOptions();
$columnOptions = readinessColumnOptions();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrfRequest();
        $pdo = db();
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'clear_selected') {
            $ids = array_values(array_filter(array_map('intval', (array) ($_POST['override_ids'] ?? [])), static fn(int $id): bool => $id > 0));
            if (!$ids) {
                throw new InvalidArgumentException('กรุณาเลือกรายการ override ที่ต้องการลบ');
            }

            $placeholders = implode(', ', array_fill(0, count($ids), '?'));
            $params = $ids;
            $sql = 'DELETE FROM readiness_overrides WHERE id IN (' . $placeholders . ')';
            if ($selectedUnitId > 0) {
                $sql .= ' AND unit_id = ?';
                $params[] = $selectedUnitId;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            writeAuditLog($pdo, $currentUser['id'] ?? null, $currentUser['name'] ?? null, 'MANUAL_OVERRIDE_CLEAR_SELECTED', 'unit', (string) ($selectedUnitId ?: 'all'), [
                'override_ids' => $ids,
                'unit_id' => $selectedUnitId ?: null,
            ]);
            $message = 'ลบ manual override ที่เลือกเรียบร้อยแล้ว';
        }

        if ($action === 'clear_unit') {
            if ($selectedUnitId <= 0) {
                throw new InvalidArgumentException('กรุณาเลือกหน่วยก่อนล้าง override ทั้งหน่วย');
            }

            $stmt = $pdo->prepare('DELETE FROM readiness_overrides WHERE unit_id = ?');
            $stmt->execute([$selectedUnitId]);

            writeAuditLog($pdo, $currentUser['id'] ?? null, $currentUser['name'] ?? null, 'MANUAL_OVERRIDE_CLEAR_UNIT', 'unit', (string) $selectedUnitId, [
                'unit_id' => $selectedUnitId,
            ]);
            $message = 'ล้าง manual override ของหน่วยที่เลือกเรียบร้อยแล้ว';
        }
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$params = [];
$sql = 'SELECT ro.*, un.name AS unit_name, COALESCE(u.display_name, ro.source_name, ?) AS actor_name
        FROM readiness_overrides ro
        LEFT JOIN units un ON un.id = ro.unit_id
        LEFT JOIN users u ON u.id = ro.updated_by_user_id';
if ($selectedUnitId > 0) {
    $sql .= ' WHERE ro.unit_id = ?';
    $params[] = $selectedUnitId;
}
$sql .= ' ORDER BY ro.updated_at DESC, ro.id DESC';

$stmt = db()->prepare($sql);
$stmt->execute(array_merge(['manual override'], $params));
$overrides = $stmt->fetchAll();

$totalCount = count($overrides);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Manual Overrides — <?= SITE_NAME ?></title>
<link rel="stylesheet" href="../app.css.php">
<style>
.toolbar{display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap}
.hint{font-size:12px;color:var(--theme-text-soft);line-height:1.6}
.actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
.pill{display:inline-block;padding:2px 8px;border-radius:999px;font-size:11px;background:var(--theme-surface-soft);border:1px solid var(--theme-border-soft);color:var(--theme-text-soft)}
table td{vertical-align:middle}
.empty{padding:20px;text-align:center;color:var(--theme-text-soft)}
@media(max-width:980px){table{display:block;overflow-x:auto;white-space:nowrap}}
</style>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="topbar">
  <div>
    <div style="font-weight:600">Manual Overrides</div>
    <div style="font-size:12px;opacity:.8"><?= SITE_NAME ?></div>
  </div>
  <div style="display:flex;gap:14px;flex-wrap:wrap">
    <a href="../index.php">Dashboard</a>
    <?php if (userCanEditReadiness(currentUser())): ?><a href="../input.php">กรอกข้อมูล</a><?php endif; ?>
    <a href="readiness-items.php">รายการประเมิน</a>
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

<div class="container container-wide">
  <?php if ($message): ?><div class="msg ok"><?= htmlspecialchars($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="section">
    <div class="toolbar">
      <div>
        <h2>จัดการ Manual Override</h2>
        <div class="hint">หน้านี้ใช้ตรวจสอบรายการ override ที่ทับค่าจาก integration และสามารถล้าง override เป็นรายรายการหรือทั้งหน่วยได้</div>
      </div>
      <form method="GET" class="actions">
        <label>หน่วย</label>
        <select name="unit_id">
          <option value="0" <?= $selectedUnitId === 0 ? 'selected' : '' ?>>ทุกหน่วย</option>
          <?php foreach ($units as $unit): ?>
          <option value="<?= (int) $unit['id'] ?>" <?= (int) $unit['id'] === $selectedUnitId ? 'selected' : '' ?>><?= htmlspecialchars($unit['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit">กรอง</button>
      </form>
    </div>
    <div class="hint" style="margin-top:10px">จำนวนรายการที่พบ: <strong><?= (int) $totalCount ?></strong></div>
  </div>

  <form method="POST" class="section">
    <?= csrfInput() ?>
    <input type="hidden" name="unit_id" value="<?= (int) $selectedUnitId ?>">
    <div class="actions" style="margin-bottom:12px">
      <button type="submit" name="action" value="clear_selected" onclick="return confirm('ยืนยันลบ override ที่เลือก?')">ลบรายการที่เลือก</button>
      <button type="submit" name="action" value="clear_unit" onclick="return confirm('ยืนยันล้าง override ทั้งหน่วยที่เลือก?')" <?= $selectedUnitId > 0 ? '' : 'disabled' ?>>ล้างทั้งหน่วยที่เลือก</button>
      <span class="pill">เลือกหน่วยก่อนจึงจะใช้ปุ่มล้างทั้งหน่วยได้</span>
    </div>

    <table>
      <thead>
        <tr>
          <th style="width:40px"></th>
          <th>หน่วย</th>
          <th>มิติ</th>
          <th>องค์ประกอบ</th>
          <th>รายการ</th>
          <th>ค่า Override</th>
          <th>อัปเดตล่าสุด</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$overrides): ?>
        <tr><td colspan="7" class="empty">ไม่พบ manual override ตามเงื่อนไขที่เลือก</td></tr>
        <?php endif; ?>
        <?php foreach ($overrides as $override): ?>
        <?php
          $rowId = (string) $override['row_id'];
          $colId = (string) $override['col_id'];
          $itemIndex = (int) $override['item_index'];
          $itemDef = normalizeItem($items[$rowId][$colId][$itemIndex] ?? '');
        ?>
        <tr>
          <td><input type="checkbox" name="override_ids[]" value="<?= (int) $override['id'] ?>" style="width:auto"></td>
          <td><?= htmlspecialchars((string) ($override['unit_name'] ?? ('#' . (int) $override['unit_id']))) ?></td>
          <td><?= htmlspecialchars((string) ($rowOptions[$rowId] ?? $rowId)) ?></td>
          <td><?= htmlspecialchars((string) ($columnOptions[$colId] ?? $colId)) ?></td>
          <td><?= htmlspecialchars($itemDef['label']) ?> <span class="pill">#<?= $itemIndex + 1 ?></span></td>
          <td><strong><?= (int) $override['value'] ?></strong></td>
          <td>
            <?= htmlspecialchars((string) $override['updated_at']) ?><br>
            <span class="hint">โดย <?= htmlspecialchars((string) ($override['actor_name'] ?? '-')) ?></span>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </form>
</div>
</body>
</html>