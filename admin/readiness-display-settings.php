<?php
require_once '../config.php';
require_once '../functions.php';

applySecurityHeaders();
requirePermission('manage:items');

$currentUser = currentUser();
$message = '';
$error = '';
$rowOptions = readinessRowOptions();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrfRequest();
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'save') {
            $visibleRows = array_values(array_map('strval', (array) ($_POST['visible_rows'] ?? [])));
            $calculatedRows = array_values(array_map('strval', (array) ($_POST['calculated_rows'] ?? [])));
      $readyThreshold = (float) ($_POST['ready_threshold'] ?? READINESS_READY_THRESHOLD);
      $warningThreshold = (float) ($_POST['warning_threshold'] ?? READINESS_WARNING_THRESHOLD);
      updateReadinessDisplaySettings($visibleRows, $calculatedRows, $readyThreshold, $warningThreshold, $currentUser, db());
      $message = 'บันทึกการตั้งค่าการแสดงผลมิติและเกณฑ์สถานะเรียบร้อยแล้ว';
        }

        if ($action === 'reset_override') {
            clearReadinessDisplaySettingsOverride($currentUser, db());
            $message = 'ล้างค่าที่ override ในฐานข้อมูลแล้ว ระบบจะกลับไปใช้ env/default';
        }
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$settings = getReadinessDisplaySettings(db());
$readyThresholdValue = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string) ($_POST['ready_threshold'] ?? $settings['ready_threshold']) : (string) $settings['ready_threshold'];
$warningThresholdValue = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string) ($_POST['warning_threshold'] ?? $settings['warning_threshold']) : (string) $settings['warning_threshold'];
$visibleLookup = array_fill_keys($settings['visible_rows'], true);
$calculatedLookup = array_fill_keys($settings['calculated_rows'], true);
$h = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>การแสดงผลและเกณฑ์สถานะ — <?= SITE_NAME ?></title>
<link rel="stylesheet" href="../app.css.php">
<style>
.hint{font-size:12px;color:var(--theme-text-muted);line-height:1.7}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.setting-card{border:1px solid var(--theme-border-soft);border-radius:10px;padding:14px;background:var(--theme-surface-soft)}
.setting-card h3{font-size:14px;margin-bottom:10px}
.threshold-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.threshold-card{border:1px solid var(--theme-border-soft);border-radius:10px;padding:14px;background:var(--theme-surface-soft)}
.threshold-card input{max-width:180px}
.row-item{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;padding:8px 0;border-bottom:1px solid var(--theme-border-table)}
.row-item:last-child{border-bottom:none}
.row-meta{font-size:12px;color:var(--theme-text-soft);margin-top:3px}
.actions{display:flex;gap:12px;flex-wrap:wrap}
.source{font-size:12px;color:var(--theme-text-muted);margin-top:6px}
@media(max-width:800px){.grid,.threshold-grid{grid-template-columns:1fr}}
</style>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="topbar">
  <div>
    <div style="font-weight:600">การแสดงผลและเกณฑ์สถานะ</div>
    <div style="font-size:12px;opacity:.8"><?= SITE_NAME ?></div>
  </div>
  <div style="display:flex;gap:14px;flex-wrap:wrap">
    <a href="../index.php">Dashboard</a>
    <?php if (userCanEditReadiness($currentUser)): ?><a href="../input.php">กรอกข้อมูล</a><?php endif; ?>
    <a href="readiness-items.php">จัดการรายการ</a>
    <a href="readiness-sync.php">Sync Config</a>
    <?php if (currentUserCan('manage:users')): ?><a href="users.php">ผู้ใช้</a><?php endif; ?>
    <?php if (currentUserCan('manage:units')): ?><a href="units.php">หน่วย</a><?php endif; ?>
    <?php if (currentUserCan('manage:api')): ?><a href="api-clients.php">API Clients</a><?php endif; ?>
    <?php if (currentUserCan('manage:api')): ?><a href="mock-percent-mappings.php">Mock API Map</a><?php endif; ?>
    <?php if (currentUserCan('view:audit')): ?><a href="audit-log.php">Audit Log</a><?php endif; ?>
    <a href="../logout.php">ออกจากระบบ</a>
  </div>
</div>

<div class="container container-narrow">
  <?php if ($message): ?><div class="msg ok"><?= $h($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="msg err"><?= $h($error) ?></div><?php endif; ?>

  <div class="section">
    <h2>แนวทางใช้งาน</h2>
    <div class="hint">
      หน้านี้จะบันทึกการตั้งค่าในฐานข้อมูลโดยตรง และมีผล override ค่า <code>READINESS_VISIBLE_ROWS</code> / <code>READINESS_CALCULATED_ROWS</code> / <code>READINESS_READY_THRESHOLD</code> / <code>READINESS_WARNING_THRESHOLD</code> จาก env<br>
      ถ้ามิติบางตัวถูกเลือกให้แสดง แต่ไม่ถูกเลือกให้คำนวณ ระบบจะแสดงข้อมูลได้ตามปกติ แต่จะไม่รวมในสูตรคะแนน<br>
      เกณฑ์สถานะจะถูกใช้ร่วมกันทั้ง badge สถานะและสีของคะแนนบน Dashboard และ Input<br>
      ถ้ากด "กลับไปใช้ env/default" ระบบจะลบค่า override จากฐานข้อมูล และกลับไปใช้ค่าจาก env หรือค่าปริยายแทน
    </div>
  </div>

  <div class="section">
    <h2>แหล่งค่าปัจจุบัน</h2>
    <div class="hint">
      การแสดงผล: <strong><?= $h($settings['visible_source']) ?></strong><br>
      การคำนวณ: <strong><?= $h($settings['calculated_source']) ?></strong><br>
      เกณฑ์สถานะพร้อม: <strong><?= $h($settings['ready_threshold_source']) ?></strong><br>
      เกณฑ์สถานะปานกลาง: <strong><?= $h($settings['warning_threshold_source']) ?></strong><br>
      มิติที่แสดงตอนนี้: <code><?= $h(implode(',', $settings['visible_rows'])) ?></code><br>
      มิติที่ใช้คำนวณตอนนี้: <code><?= $h(implode(',', $settings['calculated_rows'])) ?></code><br>
      พร้อม: <strong><?= $h(number_format((float) $settings['ready_threshold'], 2)) ?>%</strong> ขึ้นไป<br>
      ปานกลาง: <strong><?= $h(number_format((float) $settings['warning_threshold'], 2)) ?>%</strong> ถึงต่ำกว่า <strong><?= $h(number_format((float) $settings['ready_threshold'], 2)) ?>%</strong><br>
      ต้องปรับปรุง: ต่ำกว่า <strong><?= $h(number_format((float) $settings['warning_threshold'], 2)) ?>%</strong>
    </div>
  </div>

  <form method="POST" class="section">
    <?= csrfInput() ?>
    <input type="hidden" name="action" value="save">

    <div class="threshold-grid" style="margin-bottom:16px">
      <div class="threshold-card">
        <h3>เกณฑ์สถานะพร้อม</h3>
        <div class="source">คะแนนตั้งแต่ค่านี้ขึ้นไปจะแสดงเป็น "พร้อม" และใช้สี success</div>
        <label for="ready-threshold">เปอร์เซ็นต์</label>
        <input id="ready-threshold" type="number" name="ready_threshold" min="0" max="100" step="0.01" value="<?= $h($readyThresholdValue) ?>" required>
      </div>
      <div class="threshold-card">
        <h3>เกณฑ์สถานะปานกลาง</h3>
        <div class="source">คะแนนตั้งแต่ค่านี้ขึ้นไป แต่ยังไม่ถึงเกณฑ์พร้อม จะแสดงเป็น "ปานกลาง"</div>
        <label for="warning-threshold">เปอร์เซ็นต์</label>
        <input id="warning-threshold" type="number" name="warning_threshold" min="0" max="100" step="0.01" value="<?= $h($warningThresholdValue) ?>" required>
      </div>
    </div>

    <div class="grid">
      <div class="setting-card">
        <h3>มิติที่ต้องการแสดง</h3>
        <div class="source">ใช้กำหนดว่า Dashboard และ Input จะโชว์มิติไหนบ้าง</div>
        <?php foreach ($rowOptions as $rowId => $label): ?>
        <label class="row-item">
          <span>
            <strong><?= $h($label) ?></strong>
            <div class="row-meta"><?= $h($rowId) ?></div>
          </span>
          <input type="checkbox" name="visible_rows[]" value="<?= $h($rowId) ?>" <?= isset($visibleLookup[$rowId]) ? 'checked' : '' ?>>
        </label>
        <?php endforeach; ?>
      </div>

      <div class="setting-card">
        <h3>มิติที่ใช้คำนวณ</h3>
        <div class="source">ใช้กำหนดว่ามิติไหนจะถูกนำไปคิดคะแนนรวมจริง โดยระบบจะ normalize น้ำหนักใหม่อัตโนมัติ</div>
        <?php foreach ($rowOptions as $rowId => $label): ?>
        <label class="row-item">
          <span>
            <strong><?= $h($label) ?></strong>
            <div class="row-meta"><?= $h($rowId) ?></div>
          </span>
          <input type="checkbox" name="calculated_rows[]" value="<?= $h($rowId) ?>" <?= isset($calculatedLookup[$rowId]) ? 'checked' : '' ?>>
        </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="actions" style="margin-top:18px">
      <button type="submit">บันทึกการตั้งค่า</button>
    </div>
  </form>

  <form method="POST" class="section">
    <?= csrfInput() ?>
    <input type="hidden" name="action" value="reset_override">
    <h2>กลับไปใช้ env/default</h2>
    <div class="hint" style="margin-bottom:12px">
      ใช้เมื่อต้องการลบค่า override ในฐานข้อมูล แล้วกลับไปใช้ <code>READINESS_VISIBLE_ROWS</code>, <code>READINESS_CALCULATED_ROWS</code>, <code>READINESS_READY_THRESHOLD</code> และ <code>READINESS_WARNING_THRESHOLD</code> จาก env หรือค่าปริยาย
    </div>
    <button class="btn-secondary" type="submit" onclick="return confirm('ยืนยันการล้างค่าที่ override ในฐานข้อมูล?')">กลับไปใช้ env/default</button>
  </form>
</div>
</body>
</html>