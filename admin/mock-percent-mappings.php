<?php
require_once '../config.php';
require_once '../functions.php';
applySecurityHeaders();
requirePermission('manage:api');

$message = '';
$error = '';
$items = getItemsDefinition();
$transformOptions = mockTransformTypeOptions();
$targetOptions = [];
foreach ($items as $rowId => $rowDef) {
    $rowLabel = (string) ($rowDef['label'] ?? $rowId);
    foreach (['personnel', 'material', 'tactic'] as $colId) {
        foreach (($rowDef[$colId] ?? []) as $itemIndex => $item) {
            $itemData = normalizeItem($item);
            $targetOptions[] = [
                'value' => $rowId . '|' . $colId . '|' . (int) $itemIndex,
                'label' => $rowLabel . ' / ' . $colId . ' / ' . $itemData['label'] . ' (#' . ((int) $itemIndex + 1) . ')',
            ];
        }
    }
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrfRequest();
        $targetPath = trim((string) ($_POST['target_path'] ?? ''));
        [$rowId, $colId, $itemIndexRaw] = array_pad(explode('|', $targetPath, 3), 3, '');
        $itemIndex = $itemIndexRaw === '' ? -1 : (int) $itemIndexRaw;
    $transformType = trim((string) ($_POST['transform_type'] ?? 'percent_threshold'));
    $transformPayload = trim((string) ($_POST['transform_payload'] ?? ''));

        if (($_POST['action'] ?? '') === 'create') {
            createMockPercentMetricMapping(
                trim((string) ($_POST['metric_key'] ?? '')),
                $rowId,
                $colId,
                $itemIndex,
        isset($_POST['is_active']),
        $transformType,
        $transformPayload
            );
            $message = 'สร้าง mock percent mapping เรียบร้อยแล้ว';
        }

        if (($_POST['action'] ?? '') === 'update') {
            updateMockPercentMetricMapping(
                (int) ($_POST['mapping_id'] ?? 0),
                trim((string) ($_POST['metric_key'] ?? '')),
                $rowId,
                $colId,
                $itemIndex,
                isset($_POST['is_active']),
                $transformType,
                $transformPayload
            );
            $message = 'อัปเดต mock percent mapping เรียบร้อยแล้ว';
        }

        if (($_POST['action'] ?? '') === 'delete') {
            deleteMockPercentMetricMapping((int) ($_POST['mapping_id'] ?? 0));
            $message = 'ลบ mock percent mapping เรียบร้อยแล้ว';
        }

          if (($_POST['action'] ?? '') === 'import') {
            $importFormat = strtolower(trim((string) ($_POST['import_format'] ?? 'json')));
            $importPayload = (string) ($_POST['import_payload'] ?? '');
            $replaceExisting = isset($_POST['replace_existing']);
            $summary = importMockPercentMetricMappings($importPayload, $importFormat, $replaceExisting);
            $message = 'import mapping เรียบร้อยแล้ว: สร้างใหม่ ' . (int) $summary['created'] . ' รายการ, อัปเดต ' . (int) $summary['updated'] . ' รายการ';
          }
    }

        if (($_GET['action'] ?? '') === 'export') {
          $format = strtolower(trim((string) ($_GET['format'] ?? 'json')));
          $content = exportMockPercentMetricMappings($format);
          header('Content-Type: ' . ($format === 'csv' ? 'text/csv' : 'application/json') . '; charset=UTF-8');
          header('Content-Disposition: attachment; filename="mock-percent-mappings.' . ($format === 'csv' ? 'csv' : 'json') . '"');
          echo $content;
          exit;
        }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$mappings = getMockPercentMetricMappings(true);
      $collisionWarnings = mockPercentTargetCollisionWarnings();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mock Percent Mappings — <?= SITE_NAME ?></title>
<link rel="stylesheet" href="../app.css.php">
<style>
.grid{display:grid;grid-template-columns:1.1fr 1.1fr 0.9fr auto;gap:12px}
.hint{font-size:12px;color:var(--theme-text-soft);margin-top:8px;line-height:1.6}
.inline-form{display:grid;grid-template-columns:1.1fr 1.1fr 0.9fr auto auto;gap:10px;align-items:end}
.actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.warn-list{background:var(--theme-warning-bg, #FAEEDA);color:var(--theme-warning-text, #633806);border:1px solid var(--theme-border-soft);border-radius:10px;padding:12px 16px;margin-bottom:16px}
.io-grid{display:grid;grid-template-columns:1fr auto;gap:12px;align-items:start}
.io-grid textarea{min-height:180px}
@media(max-width:900px){.grid,.inline-form{grid-template-columns:1fr}table{display:block;overflow-x:auto;white-space:nowrap}}
</style>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="topbar">
  <div>
    <div style="font-weight:600">Mock Percent Mappings</div>
    <div style="font-size:12px;opacity:.8"><?= SITE_NAME ?></div>
  </div>
  <div style="display:flex;gap:14px;flex-wrap:wrap">
    <a href="../index.php">Dashboard</a>
    <?php if (userCanEditReadiness(currentUser())): ?><a href="../input.php">กรอกข้อมูล</a><?php endif; ?>
    <a href="users.php">ผู้ใช้</a>
    <a href="units.php">หน่วย</a>
    <?php if (currentUserCan('manage:items')): ?><a href="readiness-items.php">รายการประเมิน</a><?php endif; ?>
    <?php if (currentUserCan('manage:items')): ?><a href="manual-overrides.php">Manual Overrides</a><?php endif; ?>
    <a href="api-clients.php">API Clients</a>
    <?php if (currentUserCan('view:audit')): ?><a href="audit-log.php">Audit Log</a><?php endif; ?>
    <a href="../logout.php">ออกจากระบบ</a>
  </div>
</div>

<div class="container">
  <?php if ($message): ?><div class="msg ok"><?= htmlspecialchars($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($collisionWarnings): ?>
  <div class="warn-list">
    <strong>พบ metric key หลายตัวชน target เดียวกัน</strong><br>
    <?php foreach ($collisionWarnings as $warning): ?>
      <?= htmlspecialchars($warning['row_id'] . '.' . $warning['col_id'] . '[' . ((int) $warning['item_index'] + 1) . ']') ?>
      ← <?= htmlspecialchars(implode(', ', $warning['metric_keys'])) ?><br>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="section">
    <h2>เพิ่ม Metric Mapping</h2>
    <form method="POST">
      <?= csrfInput() ?>
      <input type="hidden" name="action" value="create">
      <div class="grid">
        <div>
          <label>Metric Key</label>
          <input type="text" name="metric_key" placeholder="เช่น support.efficiency.material.ssot_pon_kr" required>
        </div>
        <div>
          <label>Target Item</label>
          <select name="target_path" required>
            <option value="">- เลือกรายการประเมิน -</option>
            <?php foreach ($targetOptions as $option): ?>
            <option value="<?= htmlspecialchars($option['value']) ?>"><?= htmlspecialchars($option['label']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Transform</label>
          <select name="transform_type" required>
            <?php foreach ($transformOptions as $value => $label): ?>
            <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
          </select>
          <textarea name="transform_payload" placeholder='JSON payload เช่น {"map":{"green":2,"yellow":1,"red":0}}'></textarea>
        </div>
        <div class="actions">
          <label style="display:flex;align-items:center;gap:8px;margin:0"><input type="checkbox" name="is_active" checked style="width:auto"> เปิดใช้งาน</label>
          <button type="submit">บันทึก</button>
        </div>
      </div>
      <div class="hint">เพิ่ม metric key ใหม่ได้จากหน้านี้โดยไม่ต้องแก้ `config.php` ทุกครั้ง ระบบจะใช้ค่าในฐานข้อมูลเป็นหลัก และ fallback ไปที่ `MOCK_PERCENT_METRIC_MAP` เมื่อยังไม่มีการตั้งค่าใน DB</div>
    </form>
  </div>

  <div class="section">
    <h2>รายการ Mapping</h2>
    <table>
      <thead>
        <tr>
          <th>Metric Key</th>
          <th>Target</th>
          <th>สถานะ</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($mappings as $mapping): ?>
        <?php $selectedTarget = (string) $mapping['row_id'] . '|' . (string) $mapping['col_id'] . '|' . (int) $mapping['item_index']; ?>
        <tr>
          <td colspan="3">
            <form method="POST" class="inline-form">
              <?= csrfInput() ?>
              <input type="hidden" name="mapping_id" value="<?= (int) $mapping['id'] ?>">
              <input type="hidden" name="action" value="update">
              <div>
                <label>Metric Key</label>
                <input type="text" name="metric_key" value="<?= htmlspecialchars((string) $mapping['metric_key']) ?>" required>
              </div>
              <div>
                <label>Target Item</label>
                <select name="target_path" required>
                  <?php foreach ($targetOptions as $option): ?>
                  <option value="<?= htmlspecialchars($option['value']) ?>" <?= $option['value'] === $selectedTarget ? 'selected' : '' ?>><?= htmlspecialchars($option['label']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label>Transform</label>
                <select name="transform_type" required>
                  <?php foreach ($transformOptions as $value => $label): ?>
                  <option value="<?= htmlspecialchars($value) ?>" <?= (string) ($mapping['transform_type'] ?? 'percent_threshold') === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                  <?php endforeach; ?>
                </select>
                <textarea name="transform_payload" placeholder='JSON payload'><?= htmlspecialchars((string) ($mapping['transform_payload'] ?? '')) ?></textarea>
              </div>
              <div class="actions">
                <label style="display:flex;align-items:center;gap:8px;margin:0"><input type="checkbox" name="is_active" <?= (int) $mapping['is_active'] === 1 ? 'checked' : '' ?> style="width:auto"> เปิดใช้งาน</label>
                <button type="submit">อัปเดต</button>
              </div>
              <div class="actions">
                <button type="submit" formaction="mock-percent-mappings.php" name="action" value="delete" onclick="return confirm('ยืนยันการลบ mapping นี้?')">ลบ</button>
              </div>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div class="hint">metric key ที่ปิดใช้งานจะไม่ถูกใช้โดย endpoint `api/v1/mock-percent-ingest.php` อีกต่อไป</div>
  </div>

  <div class="section">
    <h2>Import / Export Mapping</h2>
    <div class="actions" style="margin-bottom:12px">
      <a href="mock-percent-mappings.php?action=export&amp;format=json">Export JSON</a>
      <a href="mock-percent-mappings.php?action=export&amp;format=csv">Export CSV</a>
    </div>
    <form method="POST">
      <?= csrfInput() ?>
      <input type="hidden" name="action" value="import">
      <div class="io-grid">
        <div>
          <label>Import Payload</label>
          <textarea name="import_payload" placeholder="วาง JSON array หรือ CSV ที่มี header: metric_key,row_id,col_id,item_index,transform_type,transform_payload,is_active"></textarea>
        </div>
        <div class="actions" style="align-self:end">
          <div>
            <label>Format</label>
            <select name="import_format">
              <option value="json">JSON</option>
              <option value="csv">CSV</option>
            </select>
          </div>
          <label style="display:flex;align-items:center;gap:8px;margin:0"><input type="checkbox" name="replace_existing" style="width:auto"> ลบของเดิมก่อน import</label>
          <button type="submit">Import</button>
        </div>
      </div>
    </form>
  </div>
</div>
</body>
</html>
