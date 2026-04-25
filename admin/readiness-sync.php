<?php
require_once '../config.php';
require_once '../functions.php';

applySecurityHeaders();
requirePermission('manage:items');

$currentUser = currentUser();
$message = '';
$error = '';

if (isset($_GET['download_backup'])) {
    $fileName = basename((string) $_GET['download_backup']);
    $filePath = readinessSyncBackupDirectory() . '/' . $fileName;
    if (!is_file($filePath) || !is_readable($filePath)) {
        http_response_code(404);
        exit('ไม่พบไฟล์ backup');
    }

    header('Content-Type: application/json; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . rawurlencode($fileName) . '"');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrfRequest();
        $action = (string) ($_POST['action'] ?? '');
        $diffData = buildReadinessItemsDiff();

        if ($action === 'export_backup') {
            $backup = exportReadinessSyncBackup(db(), $diffData, $currentUser, 'web-admin');
            writeAuditLog(db(), $currentUser['id'] ?? null, $currentUser['name'] ?? null, 'READINESS_SYNC_BACKUP_EXPORT', 'readiness_sync', $backup['file_path_relative'], [
                'source' => 'web-admin',
                'diff' => $diffData['diff'],
                'impact' => $diffData['impact'],
            ]);
            $message = 'สร้าง backup เรียบร้อยแล้ว: ' . $backup['file_path_relative'];
        }

        if ($action === 'apply_sync') {
            $confirmProduction = isset($_POST['confirm_production']);
            $confirmImpact = isset($_POST['confirm_impact']);

            if (appIsProduction() && !$confirmProduction) {
                throw new InvalidArgumentException('ระบบอยู่ใน production ต้องติ๊กยืนยันก่อน apply sync');
            }

            if (($diffData['impact']['has_usage_risk'] ?? false) && !$confirmImpact) {
                throw new InvalidArgumentException('มีรายการที่กระทบข้อมูลจริง กรุณาติ๊กยืนยันผลกระทบก่อน apply sync');
            }

            $result = applyReadinessItemsSyncWithBackup(db(), $currentUser, $confirmProduction, 'web-admin');
            if ($result['no_changes']) {
                $message = 'ไม่พบความเปลี่ยนแปลงสำหรับการ sync แต่ได้สร้าง backup ไว้แล้วที่ ' . ($result['backup']['file_path_relative'] ?? '');
            } else {
                $message = 'apply sync เรียบร้อยแล้ว และสร้าง backup ไว้ที่ ' . ($result['backup']['file_path_relative'] ?? '');
            }
        }
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$diffData = buildReadinessItemsDiff();
$recentBackups = listReadinessSyncBackups(10);
$h = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$formatBytes = static function (int $bytes): string {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    }
    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }
    return number_format($bytes) . ' B';
};
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sync Config — <?= SITE_NAME ?></title>
<link rel="stylesheet" href="../app.css.php">
<style>
.stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
.stat{border:1px solid var(--theme-border-soft);border-radius:10px;padding:14px;background:var(--theme-surface-soft)}
.stat .k{font-size:12px;color:var(--theme-text-muted)}.stat .v{font-size:24px;font-weight:600;margin-top:4px}
.actions{display:flex;gap:12px;flex-wrap:wrap;align-items:center}
.checkboxes{display:flex;gap:14px;flex-wrap:wrap;margin-top:12px;font-size:13px}
.hint{font-size:12px;color:var(--theme-text-soft);line-height:1.7}
.muted{color:var(--theme-text-soft);font-size:12px}
@media(max-width:1000px){.stats{grid-template-columns:1fr 1fr}table{display:block;overflow-x:auto;white-space:nowrap}}
@media(max-width:700px){.stats{grid-template-columns:1fr}}
</style>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="topbar">
  <div>
    <div style="font-weight:600">Sync รายการจาก Config</div>
    <div style="font-size:12px;opacity:.8"><?= SITE_NAME ?></div>
  </div>
  <div style="display:flex;gap:14px;flex-wrap:wrap">
    <a href="../index.php">Dashboard</a>
    <?php if (userCanEditReadiness($currentUser)): ?><a href="../input.php">กรอกข้อมูล</a><?php endif; ?>
    <a href="readiness-display-settings.php">การแสดงผลมิติ</a>
    <a href="readiness-items.php">จัดการรายการ</a>
    <?php if (currentUserCan('manage:users')): ?><a href="users.php">ผู้ใช้</a><?php endif; ?>
    <?php if (currentUserCan('manage:units')): ?><a href="units.php">หน่วย</a><?php endif; ?>
    <?php if (currentUserCan('manage:api')): ?><a href="api-clients.php">API Clients</a><?php endif; ?>
    <?php if (currentUserCan('manage:api')): ?><a href="mock-percent-mappings.php">Mock API Map</a><?php endif; ?>
    <?php if (currentUserCan('view:audit')): ?><a href="audit-log.php">Audit Log</a><?php endif; ?>
    <a href="../logout.php">ออกจากระบบ</a>
  </div>
</div>

<div class="container container-wide">
  <?php if ($message): ?><div class="msg ok"><?= $h($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="msg err"><?= $h($error) ?></div><?php endif; ?>

  <div class="section">
    <h2>สถานะปัจจุบัน</h2>
    <div class="stats">
      <div class="stat"><div class="k">DB items</div><div class="v"><?= (int) ($diffData['summary']['db_item_count'] ?? 0) ?></div></div>
      <div class="stat"><div class="k">Config items</div><div class="v"><?= (int) ($diffData['summary']['config_item_count'] ?? 0) ?></div></div>
      <div class="stat"><div class="k">ต้องอัปเดต/เพิ่ม/ปิดใช้งาน</div><div class="v"><?= (int) ($diffData['diff']['to_add_count'] ?? 0) + (int) ($diffData['diff']['to_update_count'] ?? 0) + (int) ($diffData['diff']['to_deactivate_count'] ?? 0) ?></div></div>
      <div class="stat"><div class="k">Usage risk</div><div class="v"><?= (int) ($diffData['impact']['usage_risk_count'] ?? 0) ?></div></div>
    </div>
    <div class="hint" style="margin-top:12px">
      หน้านี้ใช้ preview diff ระหว่าง `ITEMS` ใน config กับ `readiness_items` ในฐานข้อมูล, สร้าง backup ก่อนเปลี่ยนจริง และ apply sync แบบมี guard<br>
      การ restore ทำผ่าน shell script เพื่อหลีกเลี่ยงการกู้คืนผิดพลาดจากหน้าเว็บ: <code>./scripts/restore-readiness-sync-backup.sh path/to/backup.json</code>
    </div>
  </div>

  <?php if (($diffData['impact']['has_usage_risk'] ?? false)): ?>
  <div class="section">
    <div class="msg warn" style="margin-bottom:0">พบรายการที่มีข้อมูลใช้งานอยู่แล้วใน `readiness_entries` และจะถูกอัปเดตหรือปิดใช้งานจำนวน <?= (int) ($diffData['impact']['usage_risk_count'] ?? 0) ?> รายการ ควรตรวจสอบก่อน apply ทุกครั้ง</div>
  </div>
  <?php endif; ?>

  <div class="section">
    <h2>ดำเนินการ</h2>
    <div class="actions">
      <form method="POST">
        <?= csrfInput() ?>
        <input type="hidden" name="action" value="export_backup">
        <button class="btn-secondary" type="submit">Export Backup</button>
      </form>
      <form method="POST">
        <?= csrfInput() ?>
        <input type="hidden" name="action" value="apply_sync">
        <div class="checkboxes">
          <?php if (appIsProduction()): ?><label><input type="checkbox" name="confirm_production" value="1"> ยืนยันว่าเข้าใจว่ากำลัง apply บน production</label><?php endif; ?>
          <?php if (($diffData['impact']['has_usage_risk'] ?? false)): ?><label><input type="checkbox" name="confirm_impact" value="1"> ยืนยันว่ารับทราบผลกระทบต่อข้อมูลจริง</label><?php endif; ?>
        </div>
        <button style="margin-top:12px" class="btn-danger" type="submit" onclick="return confirm('ยืนยันการ apply sync จาก config ลงฐานข้อมูล? ระบบจะสร้าง backup ก่อนทุกครั้ง')">Apply Sync พร้อม Backup</button>
      </form>
    </div>
  </div>

  <div class="section">
    <h2>Diff Preview</h2>
    <h3>เพิ่มใหม่ <span class="pill"><?= (int) ($diffData['diff']['to_add_count'] ?? 0) ?></span></h3>
    <table>
      <thead><tr><th>Key</th><th>Label</th><th>URL</th></tr></thead>
      <tbody>
        <?php foreach (($diffData['diff']['to_add'] ?? []) as $change): $item = $change['config']; ?>
        <tr>
          <td><?= $h($change['key']) ?></td>
          <td><?= $h($item['label'] ?? '') ?></td>
          <td><?= $h($item['url'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($diffData['diff']['to_add'])): ?><tr><td colspan="3" class="muted">ไม่มีรายการ</td></tr><?php endif; ?>
      </tbody>
    </table>

    <h3 style="margin-top:18px">อัปเดต <span class="pill"><?= (int) ($diffData['diff']['to_update_count'] ?? 0) ?></span></h3>
    <table>
      <thead><tr><th>Key</th><th>DB</th><th>Config</th><th>Impact</th></tr></thead>
      <tbody>
        <?php foreach (($diffData['diff']['to_update'] ?? []) as $change): ?>
        <tr>
          <td><?= $h($change['key']) ?></td>
          <td>
            <div><strong>label:</strong> <?= $h($change['db']['label'] ?? '') ?></div>
            <div><strong>url:</strong> <?= $h($change['db']['url'] ?? '') ?></div>
            <div><strong>active:</strong> <?= (int) ($change['db']['is_active'] ?? 0) ?></div>
          </td>
          <td>
            <div><strong>label:</strong> <?= $h($change['config']['label'] ?? '') ?></div>
            <div><strong>url:</strong> <?= $h($change['config']['url'] ?? '') ?></div>
          </td>
          <td>
            <span class="pill <?= ((int) ($change['usage']['entry_count'] ?? 0) > 0) ? 'risk' : '' ?>">
              entries <?= (int) ($change['usage']['entry_count'] ?? 0) ?> / units <?= (int) ($change['usage']['unit_count'] ?? 0) ?>
            </span>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($diffData['diff']['to_update'])): ?><tr><td colspan="4" class="muted">ไม่มีรายการ</td></tr><?php endif; ?>
      </tbody>
    </table>

    <h3 style="margin-top:18px">ปิดใช้งาน <span class="pill"><?= (int) ($diffData['diff']['to_deactivate_count'] ?? 0) ?></span></h3>
    <table>
      <thead><tr><th>Key</th><th>Label</th><th>URL</th><th>Impact</th></tr></thead>
      <tbody>
        <?php foreach (($diffData['diff']['to_deactivate'] ?? []) as $change): ?>
        <tr>
          <td><?= $h($change['key']) ?></td>
          <td><?= $h($change['db']['label'] ?? '') ?></td>
          <td><?= $h($change['db']['url'] ?? '') ?></td>
          <td>
            <span class="pill <?= ((int) ($change['usage']['entry_count'] ?? 0) > 0) ? 'risk' : '' ?>">
              entries <?= (int) ($change['usage']['entry_count'] ?? 0) ?> / units <?= (int) ($change['usage']['unit_count'] ?? 0) ?>
            </span>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($diffData['diff']['to_deactivate'])): ?><tr><td colspan="4" class="muted">ไม่มีรายการ</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="section">
    <h2>Backups ล่าสุด</h2>
    <table>
      <thead><tr><th>เวลา</th><th>ไฟล์</th><th>ขนาด</th><th>ดำเนินการ</th></tr></thead>
      <tbody>
        <?php foreach ($recentBackups as $backup): ?>
        <tr>
          <td><?= $h($backup['modified_at']) ?></td>
          <td>
            <div><?= $h($backup['file_name']) ?></div>
            <div class="muted"><?= $h($backup['file_path_relative']) ?></div>
          </td>
          <td><?= $h($formatBytes((int) ($backup['size_bytes'] ?? 0))) ?></td>
          <td>
            <a href="?download_backup=<?= rawurlencode((string) $backup['file_name']) ?>">ดาวน์โหลด</a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$recentBackups): ?><tr><td colspan="4" class="muted">ยังไม่มี backup</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>