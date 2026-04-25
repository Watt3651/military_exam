<?php
require_once 'config.php';
require_once 'functions.php';

applySecurityHeaders();
requireAnyPermission(['edit:own_unit', 'edit:all_units']);

$user = currentUser();
$allItems = getItemsDefinition();
$items = readinessVisibleItemsDefinition($allItems);
$availableUnits = currentUserCan('edit:all_units') ? getUnits() : array_values(array_filter([getUnitById($user['unit_id'])]));
$defaultUnitId = currentUserCan('edit:all_units') ? ($availableUnits[0]['id'] ?? null) : ($user['unit_id'] ?? null);
$selectedUnitId = selectedUnitIdFromRequest($defaultUnitId);
if (!canAccessUnit($selectedUnitId)) {
    http_response_code(403);
    exit('ไม่มีสิทธิ์แก้ไขหน่วยนี้');
}

$selectedUnit = getUnitById($selectedUnitId);
$data = loadData($selectedUnitId);
$rows = $data['rows'] ?? [];
$integrationRows = $data['integration_rows'] ?? [];
$overrideRows = $data['override_rows'] ?? [];
$integrationPresent = $data['integration_present'] ?? [];
$overridePresent = $data['override_present'] ?? [];
$resolvedSources = $data['resolved_sources'] ?? [];
$saved = false;
$error = '';

function inputStatusText(int $value): string {
  return match ($value) {
    2 => 'พร้อม',
    1 => 'ตรวจสอบ',
    default => 'ยังไม่ประเมิน',
  };
}

function inputResolvedSourceText(string $source): string {
  return match ($source) {
    'integration_mock' => 'Mock API',
    'api' => 'API',
    'override' => 'Manual Override',
    'none' => 'ไม่มีข้อมูล',
    default => $source,
  };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rows'])) {
    verifyCsrfRequest();
  $overrideChanges = [];
  $validationErrors = [];
    foreach ($items as $rowId => $rowDef) {
        foreach (['personnel', 'material', 'tactic'] as $col) {
            $count = count($rowDef[$col]);
            for ($index = 0; $index < $count; $index++) {
        $value = (string) ($_POST['rows'][$rowId][$col][$index] ?? 'system');
        if ($value === 'system') {
          $overrideChanges[$rowId][$col][$index] = null;
          continue;
        }
        if (!in_array($value, ['0', '1', '2'], true)) {
          $validationErrors[] = $rowId . '/' . $col . '/' . $index;
          continue;
        }

        $overrideChanges[$rowId][$col][$index] = (int) $value;
            }
        }
    }

    if ($validationErrors) {
        $error = 'ข้อมูลที่ส่งมาไม่ถูกต้อง: ' . implode(', ', $validationErrors);
    } else {
    persistReadinessOverrides(db(), $selectedUnitId, $overrideChanges, $user['id'], $user['name']);
        $data = loadData($selectedUnitId);
        $rows = $data['rows'] ?? [];
    $integrationRows = $data['integration_rows'] ?? [];
    $overrideRows = $data['override_rows'] ?? [];
    $integrationPresent = $data['integration_present'] ?? [];
    $overridePresent = $data['override_present'] ?? [];
    $resolvedSources = $data['resolved_sources'] ?? [];
        $saved = true;
    }
}

    $overall = calcOverall($rows);
    $colScores = [];
    $colContributions = [];
    foreach (COL_WEIGHTS as $col => $w) {
      $colScores[$col] = calcColScore($col, $rows);
      $colContributions[$col] = calcColContribution($col, $rows);
    }
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>กรอกข้อมูล — <?= SITE_NAME ?></title>
<link rel="stylesheet" href="app.css.php">
<style>
.topbar-right{display:flex;align-items:center;gap:16px;font-size:13px;flex-wrap:wrap}
.topbar-right span{opacity:.8}
.alert-success{background:var(--theme-success-bg);color:var(--theme-success-text);border-radius:10px;padding:12px 16px;font-size:13px;margin-bottom:16px;display:flex;align-items:center;gap:8px}
table{background:var(--theme-surface);border-radius:10px;overflow:hidden;border:1px solid var(--theme-border-soft);margin-bottom:24px}
th.col-left{text-align:left}
tr:last-child td{border-bottom:none}
.dim-cell{font-weight:600;font-size:13px}
.dim-weight{font-size:11px;color:var(--theme-text-soft);font-weight:400;margin-top:2px}
.item-row{display:flex;align-items:flex-start;gap:8px;padding:6px 0;border-bottom:1px solid var(--theme-border-row-soft)}
.item-row:last-child{border-bottom:none}
.item-name{flex:1;font-size:12px;color:var(--theme-text);padding-top:4px}
.item-name[href]{color:var(--theme-link);text-decoration:none}
.item-name[href]:hover{color:var(--theme-link-hover);text-decoration:underline}
.item-controls{display:flex;flex-direction:column;align-items:flex-end;gap:4px;min-width:210px}
.item-meta{font-size:11px;color:var(--theme-text-soft);text-align:right;line-height:1.45}
select.status-select{font-size:12px;padding:4px 8px;border:1px solid var(--theme-border-strong);border-radius:6px;background:var(--theme-surface);color:var(--theme-text);cursor:pointer;font-family:inherit}
select.status-select:focus{outline:none;border-color:var(--theme-primary)}
select.status-select.s-ready{background:var(--theme-success-bg);color:var(--theme-success-text);border-color:var(--theme-success-border)}
select.status-select.s-check{background:var(--theme-info-bg);color:var(--theme-topbar-bg);border-color:var(--theme-info-border)}
.cell-score{font-size:11px;color:var(--theme-text-soft);margin-top:8px;text-align:right}
.row-score-cell{text-align:center;vertical-align:middle;min-width:160px}
.row-score-box{display:flex;align-items:center;justify-content:center;min-height:92px;padding:14px 12px;border-radius:18px;background:linear-gradient(180deg,var(--theme-score-box-bg-start) 0%,var(--theme-score-box-bg-end) 100%);border:1px solid var(--theme-score-box-border);box-shadow:inset 0 1px 0 rgba(255,255,255,.75)}
.row-score-num{font-size:36px;font-weight:700;line-height:1;letter-spacing:-0.03em}
.summary-row td{background:var(--theme-surface-soft);font-weight:600;border-top:1px solid var(--theme-border-soft)}
.config-note{font-size:12px;color:var(--theme-text-muted);margin:-4px 0 12px;line-height:1.6}
.submit-bar{background:var(--theme-surface);border-top:1px solid var(--theme-border-soft);padding:16px 24px;display:flex;align-items:center;gap:16px;position:sticky;bottom:0;flex-wrap:wrap}
.btn-save{padding:11px 28px;font-size:14px;font-weight:500}
.btn-dash{padding:11px 20px;background:var(--theme-surface);color:var(--theme-text-muted);border:1px solid var(--theme-border-strong);border-radius:8px;font-size:14px;font-family:inherit;cursor:pointer;text-decoration:none;display:inline-block}
.btn-dash:hover{background:var(--theme-surface-alt)}
.save-hint{font-size:12px;color:var(--theme-text-soft)}
@media(min-width:1600px){
  .container{max-width:1480px}
  .row-score-box{min-height:108px}
  .row-score-num{font-size:44px}
}
</style>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="topbar">
  <div>
    <div class="topbar-title"><?= SITE_NAME ?></div>
    <div class="topbar-sub">หน้ากรอกข้อมูลเจ้าหน้าที่<?= $selectedUnit ? ' — ' . htmlspecialchars($selectedUnit['name']) : '' ?></div>
  </div>
  <div class="topbar-right">
    <span>ผู้ใช้: <?= htmlspecialchars($user['name']) ?></span>
    <a href="index.php">Dashboard</a>
    <?php if (currentUserCan('manage:users')): ?>
    <a href="admin/users.php">ผู้ใช้</a>
    <?php endif; ?>
    <?php if (currentUserCan('manage:units')): ?>
    <a href="admin/units.php">หน่วย</a>
    <?php endif; ?>
    <?php if (currentUserCan('manage:items')): ?>
    <a href="admin/readiness-items.php">รายการประเมิน</a>
    <a href="admin/readiness-display-settings.php">การแสดงผลมิติ</a>
    <a href="admin/manual-overrides.php">Manual Overrides</a>
    <?php endif; ?>
    <?php if (currentUserCan('manage:api')): ?>
    <a href="admin/api-clients.php">API</a>
    <a href="admin/mock-percent-mappings.php">Mock API Map</a>
    <?php endif; ?>
    <?php if (currentUserCan('view:audit')): ?>
    <a href="admin/audit-log.php">Audit</a>
    <?php endif; ?>
    <a href="logout.php">ออกจากระบบ</a>
  </div>
</div>

<?php if (currentUserCan('edit:all_units') && $availableUnits): ?>
<div class="container" style="padding-bottom:0">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px">
    <div style="font-size:13px;color:var(--theme-text-muted)">หน่วยที่กำลังแก้ไข: <strong><?= htmlspecialchars($selectedUnit['name'] ?? '-') ?></strong></div>
    <form method="GET" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <label for="unit-select" style="font-size:13px;color:var(--theme-text-muted)">เลือกหน่วย</label>
      <select id="unit-select" name="unit_id" style="padding:8px 10px;border:1px solid var(--theme-border-strong);border-radius:8px;font-family:inherit;background:var(--theme-surface);color:var(--theme-text)">
        <?php foreach ($availableUnits as $unit): ?>
        <option value="<?= (int) $unit['id'] ?>" <?= (int) $unit['id'] === (int) $selectedUnitId ? 'selected' : '' ?>><?= htmlspecialchars($unit['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" style="padding:8px 14px;border:none;border-radius:8px;background:var(--theme-primary);color:var(--theme-primary-text);font-family:inherit;cursor:pointer">เปลี่ยนหน่วย</button>
    </form>
  </div>
</div>
<?php endif; ?>

<form method="POST">
<div class="container">
  <input type="hidden" name="unit_id" value="<?= (int) $selectedUnitId ?>">
  <?= csrfInput() ?>

  <?php if ($saved): ?>
  <div class="alert-success">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="<?= htmlspecialchars(themeColor('success_text'), ENT_QUOTES, 'UTF-8') ?>" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
    บันทึกการตั้งค่า manual override เรียบร้อยแล้ว — <?= htmlspecialchars((string) ($data['updated_at'] ?? date('Y-m-d H:i:s'))) ?> โดย <?= htmlspecialchars($user['name']) ?>
  </div>
  <?php endif; ?>

  <?php if ($error): ?>
  <div style="background:var(--theme-danger-bg);color:var(--theme-danger-text);border-radius:10px;padding:12px 16px;font-size:13px;margin-bottom:16px">
    <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <div class="section-title">กรอกสถานะความพร้อมแต่ละรายการ</div>
  <div class="config-note">คอลัมน์สรุปและแถวสรุปท้ายตารางจะแสดงเฉพาะคะแนนรวมจริงที่ถูกคูณน้ำหนักแล้ว เพื่อให้อ่านค่าได้ตรงและชัดเจนขึ้น</div>
  <div class="config-note">ค่าจากระบบเป็นค่าเริ่มต้นของ Dashboard หากต้องการแก้เฉพาะรายการให้เลือก 0-2 เพื่อสร้าง manual override และเลือก “ใช้ค่าจากระบบ” เมื่อต้องการล้าง override นั้น</div>
  <?php if (READINESS_VISIBLE_ROWS !== '' || READINESS_CALCULATED_ROWS !== ''): ?>
  <div class="config-note">
    มิติที่แสดง: <strong><?= htmlspecialchars(implode(', ', array_map(static fn(string $rowId): string => $items[$rowId]['label'] ?? $rowId, array_keys($items))), ENT_QUOTES, 'UTF-8') ?></strong><br>
    มิติที่ใช้คำนวณ: <strong><?= htmlspecialchars(implode(', ', array_map(static fn(string $rowId): string => $allItems[$rowId]['label'] ?? $rowId, readinessCalculatedRowIds())), ENT_QUOTES, 'UTF-8') ?></strong>
  </div>
  <?php endif; ?>

  <div style="overflow-x:auto">
    <table>
      <thead>
        <tr>
          <th class="col-left" style="width:130px">มิติ</th>
          <th>องค์บุคคล (20%)</th>
          <th>องค์วัตถุ (50%)</th>
          <th>องค์ยุทธวิธี (30%)</th>
          <th>คะแนนรวมจริง</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $rowId => $rowDef):
          $rowContribution = calcRowContribution($rowId, $rows);
        ?>
        <tr>
          <td>
            <div class="dim-cell"><?= $rowDef['label'] ?></div>
            <div class="dim-weight">
              น้ำหนักตั้งต้น <?= round(((float) (ROW_WEIGHTS[$rowId] ?? 0)) * 100) ?>%
              <?php if (readinessRowIsCalculated($rowId)): ?>
                • ใช้คำนวณ <?= number_format(readinessRowEffectiveWeight($rowId) * 100, 2) ?>%
              <?php else: ?>
                • ไม่รวมในสูตร
              <?php endif; ?>
            </div>
          </td>
          <?php foreach (['personnel', 'material', 'tactic'] as $col):
            $values = $rows[$rowId][$col] ?? [];
            $cellScore = calcCellScore($values);
          ?>
          <td>
            <?php foreach ($rowDef[$col] as $index => $item):
              $value = (int) ($values[$index] ?? 0);
              $systemValue = (int) ($integrationRows[$rowId][$col][$index] ?? 0);
              $overrideValue = (int) ($overrideRows[$rowId][$col][$index] ?? 0);
              $hasIntegration = (bool) ($integrationPresent[$rowId][$col][$index] ?? false);
              $hasOverride = (bool) ($overridePresent[$rowId][$col][$index] ?? false);
              $resolvedSource = (string) ($resolvedSources[$rowId][$col][$index] ?? 'none');
              $selectedValue = $hasOverride ? (string) $overrideValue : 'system';
              $displayScore = $hasOverride ? $overrideValue : ($hasIntegration ? $systemValue : $value);
              $selectClass = $displayScore === 2 ? 's-ready' : ($displayScore === 1 ? 's-check' : '');
              $systemSourceLabel = $hasIntegration ? inputResolvedSourceText($resolvedSource === 'override' ? 'api' : $resolvedSource) : '';
              $systemLabel = $hasIntegration
                ? 'ใช้ค่าจากระบบ (' . inputStatusText($systemValue) . ' จาก ' . $systemSourceLabel . ')'
                : 'ใช้ค่าจากระบบ (ยังไม่มีค่า)';
              $metaText = $hasOverride
                ? 'Override ปัจจุบัน: ' . inputStatusText($overrideValue) . ($hasIntegration ? ' | ค่าระบบ: ' . inputStatusText($systemValue) : ' | ค่าระบบ: ยังไม่มีค่า')
                : ($hasIntegration ? 'ค่าที่แสดงมาจาก ' . inputResolvedSourceText($resolvedSource) . ': ' . inputStatusText($systemValue) : 'ยังไม่มีค่าจากระบบ');
            ?>
            <div class="item-row">
              <?= renderItemName($item, 'item-name') ?>
              <div class="item-controls">
                <select class="status-select <?= $selectClass ?>" name="rows[<?= $rowId ?>][<?= $col ?>][<?= $index ?>]" onchange="updateSelect(this)">
                  <option value="system" data-score="<?= $hasIntegration ? $systemValue : 0 ?>" <?= $selectedValue === 'system' ? 'selected' : '' ?>><?= htmlspecialchars($systemLabel) ?></option>
                  <option value="0" data-score="0" <?= $selectedValue === '0' ? 'selected' : '' ?>>Override: ยังไม่ประเมิน</option>
                  <option value="1" data-score="1" <?= $selectedValue === '1' ? 'selected' : '' ?>>Override: ตรวจสอบ</option>
                  <option value="2" data-score="2" <?= $selectedValue === '2' ? 'selected' : '' ?>>Override: พร้อม</option>
                </select>
                <div class="item-meta"><?= htmlspecialchars($metaText) ?></div>
              </div>
            </div>
            <?php endforeach; ?>
            <div class="cell-score">คะแนน: <strong><?= number_format($cellScore, 2) ?>%</strong></div>
          </td>
          <?php endforeach; ?>
          <td class="row-score-cell">
            <div class="row-score-box">
              <div class="row-score-num" style="color:<?= barColor($rowContribution) ?>"><?= number_format($rowContribution, 2) ?>%</div>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr class="summary-row">
          <td>
            <div class="dim-cell">รวมตามแกนแนวตั้ง</div>
            <div class="dim-weight">สรุปผลขององค์ประกอบแต่ละแกน</div>
          </td>
          <?php foreach (['personnel', 'material', 'tactic'] as $col):
            $colContribution = $colContributions[$col] ?? 0;
          ?>
          <td class="row-score-cell">
            <div class="row-score-box">
              <div class="row-score-num" style="color:<?= barColor($colContribution) ?>"><?= number_format($colContribution, 2) ?>%</div>
            </div>
          </td>
          <?php endforeach; ?>
          <td class="row-score-cell">
            <div class="row-score-box" style="background:linear-gradient(135deg,var(--theme-score-box-highlight-start) 0%,var(--theme-score-box-highlight-end) 100%);border-color:var(--theme-score-box-highlight-start);box-shadow:0 18px 34px rgba(12,68,124,.18)">
              <div class="row-score-num" style="color:var(--theme-primary-text)"><?= number_format($overall, 2) ?>%</div>
            </div>
          </td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

<div class="submit-bar">
  <button class="btn-save" type="submit">บันทึก override</button>
  <a class="btn-dash" href="index.php">ดู Dashboard</a>
  <span class="save-hint">Dashboard จะใช้ค่าจากระบบเป็นหลัก และใช้ manual override เฉพาะช่องที่คุณเลือกไว้</span>
</div>
</form>

<script>
function updateSelect(el) {
    el.className = 'status-select';
    const score = Number(el.options[el.selectedIndex]?.dataset.score || 0);
    if (score === 2) el.classList.add('s-ready');
    if (score === 1) el.classList.add('s-check');
}
</script>
</body>
</html>
