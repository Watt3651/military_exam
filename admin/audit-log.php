<?php
require_once '../config.php';
require_once '../functions.php';
applySecurityHeaders();
requirePermission('view:audit');

$filters = [
    'action' => trim((string) ($_GET['action'] ?? '')),
    'entity_type' => trim((string) ($_GET['entity_type'] ?? '')),
    'search' => trim((string) ($_GET['search'] ?? '')),
];

$logs = getAuditLogs($filters, 250);
$actions = auditActionOptions();
$entityTypes = auditEntityTypeOptions();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Audit Log — <?= SITE_NAME ?></title>
<link rel="stylesheet" href="../app.css.php">
<style>
.filters{display:grid;grid-template-columns:1fr 1fr 1.2fr auto;gap:12px;align-items:end}
.details{max-width:380px;white-space:pre-wrap;word-break:break-word;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:12px;color:var(--theme-text-muted)}
@media(max-width:1100px){.filters{grid-template-columns:1fr}table{display:block;overflow-x:auto;white-space:nowrap}}
</style>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="topbar">
  <div>
    <div style="font-weight:600">Audit Log</div>
    <div style="font-size:12px;opacity:.8"><?= SITE_NAME ?></div>
  </div>
  <div style="display:flex;gap:14px;flex-wrap:wrap">
    <a href="../index.php">Dashboard</a>
    <?php if (userCanEditReadiness(currentUser())): ?><a href="../input.php">กรอกข้อมูล</a><?php endif; ?>
    <?php if (currentUserCan('manage:users')): ?><a href="users.php">ผู้ใช้</a><?php endif; ?>
    <?php if (currentUserCan('manage:units')): ?><a href="units.php">หน่วย</a><?php endif; ?>
    <?php if (currentUserCan('manage:items')): ?><a href="readiness-items.php">รายการประเมิน</a><?php endif; ?>
    <?php if (currentUserCan('manage:api')): ?><a href="api-clients.php">API Clients</a><?php endif; ?>
    <?php if (currentUserCan('manage:api')): ?><a href="mock-percent-mappings.php">Mock API Map</a><?php endif; ?>
    <a href="../logout.php">ออกจากระบบ</a>
  </div>
</div>

<div class="container container-xl">
  <div class="panel">
    <form method="GET" class="filters">
      <div>
        <label>Action</label>
        <select name="action">
          <option value="">ทั้งหมด</option>
          <?php foreach ($actions as $action): ?>
          <option value="<?= htmlspecialchars($action) ?>" <?= $filters['action'] === $action ? 'selected' : '' ?>><?= htmlspecialchars($action) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Entity Type</label>
        <select name="entity_type">
          <option value="">ทั้งหมด</option>
          <?php foreach ($entityTypes as $entityType): ?>
          <option value="<?= htmlspecialchars($entityType) ?>" <?= $filters['entity_type'] === $entityType ? 'selected' : '' ?>><?= htmlspecialchars($entityType) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>ค้นหา</label>
        <input type="text" name="search" value="<?= htmlspecialchars($filters['search']) ?>" placeholder="actor, entity key, details">
      </div>
      <button type="submit">กรองข้อมูล</button>
    </form>
  </div>

  <div class="panel">
    <table>
      <thead>
        <tr>
          <th>เวลา</th>
          <th>Action</th>
          <th>Entity</th>
          <th>Actor</th>
          <th>IP</th>
          <th>Request ID</th>
          <th>Details</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($logs as $log): ?>
        <tr>
          <td><?= htmlspecialchars($log['created_at']) ?></td>
          <td><?= htmlspecialchars($log['action']) ?></td>
          <td><?= htmlspecialchars($log['entity_type'] . ':' . $log['entity_key']) ?></td>
          <td><?= htmlspecialchars((string) ($log['actor_name'] ?? '-')) ?></td>
          <td><?= htmlspecialchars((string) ($log['ip_address'] ?? '-')) ?></td>
          <td><?= htmlspecialchars((string) ($log['request_id'] ?? '-')) ?></td>
          <td class="details"><?= htmlspecialchars((string) ($log['details'] ?? '')) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>