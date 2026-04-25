<?php
require_once 'config.php';
require_once 'functions.php';

applySecurityHeaders();

$allItems = getItemsDefinition();
$items = readinessVisibleItemsDefinition($allItems);
$currentUser = currentUser();
$units = getUnits();
$visibleUnits = $units;
if ($currentUser && !currentUserCanAny(['view:all_units', 'edit:all_units'])) {
    $ownUnit = getUnitById($currentUser['unit_id'] ?? null);
    $visibleUnits = $ownUnit ? [$ownUnit] : [];
}
$defaultUnitId = $currentUser['unit_id'] ?? ($visibleUnits[0]['id'] ?? null);
$selectedUnitId = selectedUnitIdFromRequest($defaultUnitId);
$validUnitIds = array_map(static fn(array $unit): int => (int) $unit['id'], $visibleUnits);
if ($selectedUnitId !== null && !in_array($selectedUnitId, $validUnitIds, true)) {
  $selectedUnitId = $defaultUnitId;
}

$selectedUnit = getUnitById($selectedUnitId);
$tvMode = filter_var($_GET['tv'] ?? '0', FILTER_VALIDATE_BOOLEAN);
$normalQuery = http_build_query(array_filter([
  'unit_id' => $selectedUnitId,
], static fn($value): bool => $value !== null && $value !== ''));
$tvQuery = http_build_query(array_filter([
  'unit_id' => $selectedUnitId,
  'tv' => 1,
], static fn($value): bool => $value !== null && $value !== ''));
$dashboardToggleHref = '?' . ($tvMode ? $normalQuery : $tvQuery);
$unitSummary = getUnitDashboardSummary($visibleUnits);
$data  = loadData($selectedUnitId);
$rows  = $data['rows'] ?? [];

$overall     = calcOverall($rows);
$overallSt   = statusLabel($overall);
$colScores   = [];
$colContributions = [];
foreach (COL_WEIGHTS as $col => $w) {
    $colScores[$col] = calcColScore($col, $rows);
    $colContributions[$col] = calcColContribution($col, $rows);
}
$rowScores = [];
$rowContributions = [];
foreach (array_keys($items) as $row) {
    $rowScores[$row] = calcRowScore($row, $rows);
    $rowContributions[$row] = calcRowContribution($row, $rows);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= SITE_NAME ?></title>
<link rel="stylesheet" href="app.css.php">
<style>
body.tv-mode{background:radial-gradient(circle at top,var(--theme-page-bg-tv-start) 0%,var(--theme-page-bg-tv-mid) 42%,var(--theme-page-bg-tv-end) 100%);min-height:100vh}
.dashboard-toolbar{display:flex;justify-content:flex-end;align-items:center;margin-bottom:16px}
.tv-toggle{display:inline-flex;align-items:center;gap:8px;padding:10px 16px;border-radius:999px;background:var(--theme-navy-800);color:var(--theme-primary-text);text-decoration:none;font-size:13px;font-weight:600;box-shadow:0 12px 28px rgba(8,21,35,.28)}
.tv-toggle:hover{background:var(--theme-navy-650)}
.tv-toggle svg{flex-shrink:0}

/* Overall card */
.overall-card{background:linear-gradient(135deg,var(--theme-navy-950) 0%,var(--theme-navy-850) 46%,var(--theme-navy-600) 100%);border:1px solid rgba(216,169,58,.18);border-radius:22px;padding:28px 30px;margin-bottom:24px;display:flex;align-items:center;gap:24px;flex-wrap:wrap;box-shadow:0 24px 60px rgba(8,21,35,.30);color:var(--theme-primary-text);position:relative;overflow:hidden}
.overall-card::after{content:'';position:absolute;right:-80px;top:-90px;width:240px;height:240px;border-radius:50%;background:rgba(255,255,255,.10)}
.overall-card>*{position:relative;z-index:1}
.overall-main{display:flex;align-items:center;gap:18px;min-width:280px}
.overall-icon{width:76px;height:76px;border-radius:22px;background:linear-gradient(135deg,rgba(247,207,113,.34) 0%,rgba(216,169,58,.18) 52%,rgba(255,255,255,.10) 100%);display:flex;align-items:center;justify-content:center;backdrop-filter:blur(6px);box-shadow:inset 0 0 0 1px rgba(247,207,113,.28),0 12px 26px rgba(8,21,35,.22)}
.overall-icon svg{stroke-width:1.95;filter:drop-shadow(0 1px 0 rgba(8,21,35,.28))}
.overall-score{font-size:58px;font-weight:700;line-height:1;letter-spacing:-0.03em}
.overall-label{font-size:14px;color:rgba(255,255,255,.78);margin-bottom:6px}
.overall-bar-wrap{flex:1;min-width:240px;height:16px;background:rgba(255,255,255,.18);border-radius:999px;overflow:hidden;box-shadow:inset 0 1px 2px rgba(0,0,0,.18)}
.overall-bar{height:100%;border-radius:999px;transition:width .5s ease;background:linear-gradient(90deg,var(--theme-accent-gold-strong) 0%,var(--theme-accent-gold) 100%)!important}
.status-pill{display:inline-block;padding:8px 16px;border-radius:999px;font-size:13px;font-weight:600;background:rgba(255,255,255,.16)!important;color:var(--theme-primary-text)!important;border:1px solid rgba(255,255,255,.18)}
.updated{font-size:12px;color:rgba(255,255,255,.82);margin-left:auto}

/* Summary grid */
.summary-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:20px}
.metric-card{--accent:var(--theme-accent-sky);--accent-strong:var(--theme-accent-sky-strong);--card-border:rgba(147,197,253,.16);--card-bg-start:var(--theme-navy-850);--card-bg-end:var(--theme-navy-700);--card-ring:rgba(147,197,253,.07);background:linear-gradient(180deg,var(--card-bg-start) 0%,var(--card-bg-end) 100%);border:1px solid var(--card-border);border-radius:18px;padding:18px 18px 20px;box-shadow:0 16px 40px rgba(8,21,35,.24);min-height:168px;display:flex;flex-direction:column;justify-content:space-between;position:relative;overflow:hidden}
.metric-card::before{content:'';position:absolute;left:0;top:0;width:100%;height:5px;background:linear-gradient(90deg,var(--accent) 0%,var(--accent-strong) 100%)}
.metric-card::after{content:'';position:absolute;right:-28px;bottom:-42px;width:110px;height:110px;border-radius:50%;background:var(--card-ring)}
.metric-card>*{position:relative;z-index:1}
.metric-header{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:18px}
.metric-label{font-size:13px;color:rgba(219,234,254,.80);line-height:1.5}
.metric-value{font-size:46px;font-weight:700;line-height:1;letter-spacing:-0.03em;color:var(--theme-text-inverse-soft)}
.metric-unit{font-size:22px;font-weight:500}
.metric-icon{width:56px;height:56px;border-radius:18px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--accent) 0%,var(--accent-strong) 100%);color:var(--theme-navy-950);border:1px solid rgba(255,255,255,.18);box-shadow:inset 0 1px 0 rgba(255,255,255,.22),0 14px 28px rgba(8,21,35,.26)}
.metric-icon svg{stroke-width:1.95;filter:drop-shadow(0 1px 0 rgba(8,21,35,.18))}
.metric-bar-wrap{height:8px;background:rgba(255,255,255,.10);border-radius:999px;overflow:hidden;margin-top:10px}
.metric-bar{height:100%;border-radius:999px}
.metric-status{font-size:12px;margin-top:8px;color:rgba(219,234,254,.78)}
.metric-card.theme-personnel{--accent:var(--theme-accent-teal);--accent-strong:var(--theme-accent-teal-strong);--card-border:rgba(45,212,191,.18);--card-bg-start:var(--theme-navy-900);--card-bg-end:var(--theme-navy-700);--card-ring:rgba(45,212,191,.10)}
.metric-card.theme-material{--accent:var(--theme-accent-gold);--accent-strong:var(--theme-accent-gold-strong);--card-border:rgba(216,169,58,.22);--card-bg-start:var(--theme-navy-900);--card-bg-end:var(--theme-navy-700);--card-ring:rgba(216,169,58,.12)}
.metric-card.theme-tactic{--accent:var(--theme-accent-slate);--accent-strong:var(--theme-accent-slate-strong);--card-border:rgba(148,163,184,.20);--card-bg-start:var(--theme-navy-950);--card-bg-end:var(--theme-navy-700);--card-ring:rgba(148,163,184,.11)}
.metric-card.theme-personnel .metric-icon{background:linear-gradient(145deg,rgba(94,234,212,.95) 0%,rgba(45,212,191,.82) 58%,rgba(12,34,56,.22) 100%);color:#072a2c;box-shadow:inset 0 1px 0 rgba(255,255,255,.28),0 14px 28px rgba(6,78,73,.24)}
.metric-card.theme-material .metric-icon{background:linear-gradient(145deg,rgba(247,207,113,.98) 0%,rgba(216,169,58,.88) 56%,rgba(89,59,10,.24) 100%);color:#342106;box-shadow:inset 0 1px 0 rgba(255,255,255,.26),0 14px 28px rgba(99,56,6,.24)}
.metric-card.theme-tactic .metric-icon{background:linear-gradient(145deg,rgba(226,232,240,.95) 0%,rgba(148,163,184,.82) 54%,rgba(8,21,35,.28) 100%);color:#081523;box-shadow:inset 0 1px 0 rgba(255,255,255,.24),0 14px 28px rgba(15,41,66,.26)}
.config-note{font-size:12px;color:var(--theme-text-muted);margin:-4px 0 12px;line-height:1.6}

/* Detail table */
.table-wrap{overflow-x:auto;margin-bottom:24px}
table{width:100%;border-collapse:collapse;font-size:13px;background:var(--theme-surface);border-radius:10px;overflow:hidden;border:1px solid var(--theme-border-soft)}
th{background:var(--theme-surface-alt);font-weight:600;font-size:12px;color:var(--theme-text-muted);padding:10px 12px;text-align:center;border-bottom:1px solid var(--theme-border-soft);white-space:nowrap}
th.col-left{text-align:left}
td{padding:10px 12px;border-bottom:1px solid var(--theme-border-row);vertical-align:top}
td.dim-cell{font-weight:600;font-size:13px;white-space:nowrap}
td.dim-weight{font-size:11px;color:var(--theme-text-soft);font-weight:400}
tr:last-child td{border-bottom:none}
.item-list{list-style:none;padding:0}
.item-list li{display:flex;align-items:center;gap:6px;padding:2px 0;font-size:12px}
.item-list a{color:var(--theme-link);text-decoration:none}
.item-list a:hover{color:var(--theme-link-hover);text-decoration:underline}
.dot{width:7px;height:7px;border-radius:50%;flex-shrink:0}
.dot-ready{background:var(--theme-ready-bar)}
.dot-check{background:var(--theme-check-bar)}
.dot-none{background:var(--theme-neutral-dot)}
.cell-score{font-size:11px;color:var(--theme-text-soft);margin-top:6px}
.row-score-cell{text-align:center;vertical-align:middle;min-width:160px}
.row-score-box{display:flex;align-items:center;justify-content:center;min-height:92px;padding:14px 12px;border-radius:18px;background:linear-gradient(180deg,var(--theme-score-box-bg-start) 0%,var(--theme-score-box-bg-end) 100%);border:1px solid var(--theme-score-box-border);box-shadow:inset 0 1px 0 rgba(255,255,255,.75)}
.row-score-num{font-size:36px;font-weight:700;line-height:1;letter-spacing:-0.03em}
.summary-row td{background:var(--theme-surface-soft);font-weight:600;border-top:1px solid var(--theme-border-soft)}
.page-footer{text-align:center;font-size:12px;color:var(--theme-text-soft);padding-bottom:2rem}

/* Responsive */
@media(max-width:600px){
  .summary-grid{grid-template-columns:1fr 1fr}
  .overall-card{flex-direction:column;align-items:flex-start;gap:12px}
}
@media(min-width:1600px){
  .container{max-width:1480px}
  .overall-card{padding:36px 38px}
  .overall-score{font-size:72px}
  .metric-card{min-height:188px;padding:22px 22px 24px}
  .metric-value{font-size:58px}
  .row-score-box{min-height:108px}
  .row-score-num{font-size:44px}
}
body.tv-mode .topbar{display:none}
body.tv-mode .container{max-width:min(96vw,1920px);padding:28px 32px 40px}
body.tv-mode .dashboard-toolbar{position:sticky;top:14px;z-index:20;margin-bottom:22px}
body.tv-mode .tv-toggle{margin-left:auto;background:var(--theme-navy-800);box-shadow:0 18px 40px rgba(8,21,35,.34)}
body.tv-mode .section-title{font-size:22px;font-weight:700;margin-bottom:18px;padding-bottom:12px;padding-left:16px;color:#ffffff;border-bottom-color:rgba(255,255,255,.22);border-left:4px solid var(--theme-accent-gold);letter-spacing:.01em;text-shadow:0 2px 10px rgba(8,21,35,.45)}
body.tv-mode .summary-grid{gap:20px}
body.tv-mode .metric-card{min-height:230px;padding:24px 24px 28px;border-radius:24px}
body.tv-mode .metric-card::before{height:6px}
body.tv-mode .metric-label{font-size:18px}
body.tv-mode .metric-value{font-size:68px}
body.tv-mode .metric-icon{width:74px;height:74px;border-radius:22px}
body.tv-mode .metric-icon svg{stroke-width:2.2;filter:drop-shadow(0 1.5px 0 rgba(8,21,35,.22))}
body.tv-mode .overall-card{padding:40px 42px;border-radius:28px;box-shadow:0 30px 80px rgba(8,21,35,.40)}
body.tv-mode .overall-main{gap:22px}
body.tv-mode .overall-icon{width:92px;height:92px;border-radius:28px}
body.tv-mode .overall-icon svg{stroke-width:2.25;filter:drop-shadow(0 1.5px 0 rgba(8,21,35,.26))}
body.tv-mode .overall-label{font-size:20px}
body.tv-mode .overall-score{font-size:88px}
body.tv-mode .overall-bar-wrap{height:20px}
body.tv-mode .status-pill{padding:10px 18px;font-size:16px}
body.tv-mode .updated{font-size:15px}
body.tv-mode .unit-toolbar{display:none}
body.tv-mode .table-wrap{overflow:visible}
body.tv-mode table{font-size:16px;border-radius:18px}
body.tv-mode th{font-size:15px;padding:16px}
body.tv-mode td{padding:16px 14px}
body.tv-mode .dim-cell{font-size:16px}
body.tv-mode .dim-weight{font-size:13px}
body.tv-mode .item-list li{font-size:15px;padding:4px 0}
body.tv-mode .cell-score{font-size:13px;margin-top:10px}
body.tv-mode .row-score-box{min-height:132px;border-radius:22px;background:linear-gradient(180deg,var(--theme-surface-tv-row-start) 0%,var(--theme-surface-tv-row-end) 100%)}
body.tv-mode .row-score-num{font-size:54px}
body.tv-mode .config-note{display:none}
body.tv-mode .page-footer{display:none}
</style>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body class="<?= $tvMode ? 'tv-mode' : '' ?>">

<div class="topbar">
  <div>
    <div class="topbar-title"><?= SITE_NAME ?></div>
    <div class="topbar-sub">Dashboard สถานะความพร้อม<?= $selectedUnit ? ' — ' . htmlspecialchars($selectedUnit['name']) : '' ?></div>
  </div>
  <div class="topbar-right">
    <?php if ($currentUser): ?>
      <?php if (userCanEditReadiness($currentUser)): ?>
      <a href="input.php">กรอกข้อมูล</a>
      <?php endif; ?>
      <?php if (currentUserCan('manage:users')): ?>
      <a href="admin/users.php">จัดการผู้ใช้</a>
      <?php endif; ?>
      <?php if (currentUserCan('manage:units')): ?>
      <a href="admin/units.php">จัดการหน่วย</a>
      <?php endif; ?>
      <?php if (currentUserCan('manage:items')): ?>
      <a href="admin/readiness-items.php">รายการประเมิน</a>
      <a href="admin/readiness-display-settings.php">การแสดงผลมิติ</a>
      <a href="admin/manual-overrides.php">Manual Overrides</a>
      <?php endif; ?>
      <?php if (currentUserCan('manage:api')): ?>
      <a href="admin/api-clients.php">API Clients</a>
      <a href="admin/mock-percent-mappings.php">Mock API Map</a>
      <?php endif; ?>
      <?php if (currentUserCan('view:audit')): ?>
      <a href="admin/audit-log.php">Audit Log</a>
      <?php endif; ?>
      <a href="logout.php">ออกจากระบบ</a>
    <?php else: ?>
      <a href="login.php">เข้าสู่ระบบเจ้าหน้าที่ →</a>
    <?php endif; ?>
  </div>
</div>

<div class="container">
  <div class="dashboard-toolbar">
    <a class="tv-toggle" href="<?= htmlspecialchars($dashboardToggleHref, ENT_QUOTES, 'UTF-8') ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <?php if ($tvMode): ?>
        <path d="M8 3H5a2 2 0 0 0-2 2v3"></path>
        <path d="M16 3h3a2 2 0 0 1 2 2v3"></path>
        <path d="M8 21H5a2 2 0 0 1-2-2v-3"></path>
        <path d="M16 21h3a2 2 0 0 0 2-2v-3"></path>
        <?php else: ?>
        <path d="M8 3H5a2 2 0 0 0-2 2v3"></path>
        <path d="M16 3h3a2 2 0 0 1 2 2v3"></path>
        <path d="M8 21H5a2 2 0 0 1-2-2v-3"></path>
        <path d="M16 21h3a2 2 0 0 0 2-2v-3"></path>
        <path d="M9 9h6v6H9z"></path>
        <?php endif; ?>
      </svg>
      <?= $tvMode ? 'ออกจากโหมดทีวี' : 'โหมดทีวีเต็มจอ' ?>
    </a>
  </div>

  <?php if ($unitSummary): ?>
  <div class="section-title">ภาพรวมแยกตามหน่วย</div>
  <div class="summary-grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">
    <?php foreach ($unitSummary as $summary): ?>
    <a href="?unit_id=<?= (int) $summary['unit']['id'] ?><?= $tvMode ? '&amp;tv=1' : '' ?>" style="text-decoration:none;color:inherit">
      <div class="metric-card" style="height:100%">
        <div class="metric-label"><?= htmlspecialchars($summary['unit']['name']) ?></div>
        <div class="metric-value" style="color:<?= barColor($summary['overall']) ?>"><?= number_format($summary['overall'],2) ?><span class="metric-unit">%</span></div>
        <div class="metric-bar-wrap"><div class="metric-bar" style="width:<?= number_format($summary['overall'],2) ?>%;background:<?= barColor($summary['overall']) ?>"></div></div>
        <div class="metric-status" style="color:<?= $summary['status']['color'] ?>"><?= $summary['status']['text'] ?></div>
        <div style="font-size:11px;color:var(--theme-text-soft);margin-top:8px">ล่าสุด: <?= htmlspecialchars((string) ($summary['updated_at'] ?? '-')) ?></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if ($visibleUnits): ?>
  <div class="unit-toolbar" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px">
  <div style="font-size:13px;color:var(--theme-text-muted)">หน่วยที่แสดงผล: <strong><?= htmlspecialchars($selectedUnit['name'] ?? '-') ?></strong></div>
    <form method="GET" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <?php if ($tvMode): ?>
      <input type="hidden" name="tv" value="1">
      <?php endif; ?>
      <label for="unit_id" style="font-size:13px;color:var(--theme-text-muted)">เลือกหน่วย</label>
      <select id="unit_id" name="unit_id" style="padding:8px 10px;border:1px solid var(--theme-border-strong);border-radius:8px;font-family:inherit;background:var(--theme-surface);color:var(--theme-text)">
        <?php foreach ($visibleUnits as $unit): ?>
          <option value="<?= (int) $unit['id'] ?>" <?= (int) $unit['id'] === (int) $selectedUnitId ? 'selected' : '' ?>><?= htmlspecialchars($unit['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" style="padding:8px 14px;border:none;border-radius:8px;background:var(--theme-primary);color:var(--theme-primary-text);font-family:inherit;cursor:pointer">แสดงผล</button>
    </form>
  </div>
  <?php endif; ?>

  <!-- Overall -->
  <div class="section-title">ภาพรวมสถานะความพร้อม</div>
  <div class="overall-card">
    <div class="overall-main">
      <div class="overall-icon" aria-hidden="true">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 19c1 .7 1.8 1 3 1s2-.3 3-1 1.8-1 3-1 2 .3 3 1 1.8 1 3 1 2-.3 3-1"></path>
            <path d="M5 15h14"></path>
            <path d="M7 15l1.4-4h7.2L17 15"></path>
            <path d="M10 11V8h4v3"></path>
            <path d="M12 8V5"></path>
            <path d="M9 17h6"></path>
        </svg>
      </div>
      <div>
        <div class="overall-label">คะแนนความพร้อมรวม</div>
        <div class="overall-score"><?= number_format($overall,2) ?><span style="font-size:24px;font-weight:500">%</span></div>
      </div>
    </div>
    <div class="overall-bar-wrap">
      <div class="overall-bar" style="width:<?= number_format($overall,2) ?>%"></div>
    </div>
    <span class="status-pill" style="background:<?= $overallSt['bg'] ?>;color:<?= $overallSt['color'] ?>"><?= $overallSt['text'] ?></span>
    <?php if (!empty($data['updated_at'])): ?>
    <div class="updated">อัปเดตล่าสุด: <?= htmlspecialchars($data['updated_at']) ?><br>โดย: <?= htmlspecialchars((string) $data['updated_by']) ?></div>
    <?php endif; ?>
  </div>

  <!-- Col scores -->
  <div class="summary-grid">
    <?php
    $colLabels = ['personnel'=>'องค์บุคคล','material'=>'องค์วัตถุ','tactic'=>'องค์ยุทธวิธี'];
    foreach ($colScores as $col => $score):
      $contribution = $colContributions[$col] ?? 0;
      $themeClass = 'theme-' . $col;
    ?>
    <div class="metric-card <?= $themeClass ?>">
      <div class="metric-header">
        <div class="metric-label"><?= $colLabels[$col] ?> (<?= round(COL_WEIGHTS[$col]*100) ?>%)</div>
        <div class="metric-icon" aria-hidden="true">
          <?php if ($col === 'personnel'): ?>
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="9" cy="8" r="2.5"></circle>
            <circle cx="16" cy="9" r="2"></circle>
            <path d="M4.5 19a4.5 4.5 0 0 1 9 0"></path>
            <path d="M13 19a3.5 3.5 0 0 1 7 0"></path>
            <path d="M12 4l1 1.8 2 .3-1.4 1.4.3 2-1.9-.9-1.9.9.3-2L9 6.1l2-.3L12 4z"></path>
          </svg>
          <?php elseif ($col === 'material'): ?>
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="3"></circle>
            <path d="M12 2.5v3"></path>
            <path d="M12 18.5v3"></path>
            <path d="M4.9 4.9l2.1 2.1"></path>
            <path d="M17 17l2.1 2.1"></path>
            <path d="M2.5 12h3"></path>
            <path d="M18.5 12h3"></path>
            <path d="M4.9 19.1L7 17"></path>
            <path d="M17 7l2.1-2.1"></path>
            <path d="M8.8 8.8l-1.6-1.6"></path>
            <path d="M15.2 15.2l1.6 1.6"></path>
          </svg>
          <?php else: ?>
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 12l6-6"></path>
            <path d="M18 6v4h-4"></path>
            <path d="M12 12H4"></path>
            <path d="M12 12V20"></path>
            <path d="M4 12a8 8 0 0 1 8-8"></path>
            <path d="M12 4a8 8 0 0 1 8 8"></path>
            <circle cx="12" cy="12" r="1.5"></circle>
          </svg>
          <?php endif; ?>
        </div>
      </div>
      <div class="metric-value"><?= number_format($contribution,2) ?><span class="metric-unit">%</span></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Detail table -->
  <div class="section-title">รายละเอียดแต่ละมิติ</div>
  <div class="config-note">คอลัมน์สรุปและแถวสรุปท้ายตารางจะแสดงเฉพาะคะแนนรวมจริงที่ถูกคูณน้ำหนักแล้ว เพื่อให้อ่านค่าได้ตรงและชัดเจนขึ้น</div>
  <?php if (READINESS_VISIBLE_ROWS !== '' || READINESS_CALCULATED_ROWS !== ''): ?>
  <div class="config-note">
    มิติที่แสดง: <strong><?= htmlspecialchars(implode(', ', array_map(static fn(string $rowId): string => $items[$rowId]['label'] ?? $rowId, array_keys($items))), ENT_QUOTES, 'UTF-8') ?></strong><br>
    มิติที่ใช้คำนวณ: <strong><?= htmlspecialchars(implode(', ', array_map(static fn(string $rowId): string => $allItems[$rowId]['label'] ?? $rowId, readinessCalculatedRowIds())), ENT_QUOTES, 'UTF-8') ?></strong>
  </div>
  <?php endif; ?>
  <div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th class="col-left" style="width:140px">มิติ / น้ำหนัก</th>
        <th>องค์บุคคล <span style="font-weight:400">(20%)</span></th>
        <th>องค์วัตถุ <span style="font-weight:400">(50%)</span></th>
        <th>องค์ยุทธวิธี <span style="font-weight:400">(30%)</span></th>
        <th style="width:170px">คะแนนรวมจริง</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($items as $rowId => $rowDef):
      $rowContribution = $rowContributions[$rowId] ?? 0;
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
        <?php foreach (['personnel','material','tactic'] as $col):
          $vals = $rows[$rowId][$col] ?? [];
          $cs   = calcCellScore($vals);
        ?>
        <td>
          <ul class="item-list">
          <?php foreach ($rowDef[$col] as $i => $item):
            $v   = $vals[$i] ?? 0;
            $cls = $v===2 ? 'dot-ready' : ($v===1 ? 'dot-check' : 'dot-none');
            $lbl = $v===2 ? 'พร้อม' : ($v===1 ? 'ตรวจสอบ' : '—');
          ?>
            <li><span class="dot <?= $cls ?>"></span><?= renderItemName($item) ?> <span style="color:var(--theme-text-soft);font-size:11px">(<?= $lbl ?>)</span></li>
          <?php endforeach; ?>
          </ul>
          <div class="cell-score">คะแนน: <strong><?= number_format($cs,2) ?>%</strong></div>
        </td>
        <?php endforeach; ?>
        <td class="row-score-cell">
          <div class="row-score-box">
            <div class="row-score-num" style="color:<?= barColor($rowContribution) ?>"><?= number_format($rowContribution,2) ?>%</div>
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
            <div class="row-score-num" style="color:<?= barColor($colContribution) ?>"><?= number_format($colContribution,2) ?>%</div>
          </div>
        </td>
        <?php endforeach; ?>
        <td class="row-score-cell">
          <div class="row-score-box" style="background:linear-gradient(135deg,var(--theme-score-box-highlight-start) 0%,var(--theme-score-box-highlight-end) 100%);border-color:var(--theme-score-box-highlight-start);box-shadow:0 18px 34px rgba(12,68,124,.18)">
            <div class="row-score-num" style="color:var(--theme-primary-text)"><?= number_format($overall,2) ?>%</div>
          </div>
        </td>
      </tr>
    </tfoot>
  </table>
  </div>

  <div class="page-footer">
    <?= SITE_NAME ?> — ระบบสถานะความพร้อมภายใน กองเรือยุทธการ
  </div>
</div>
</body>
</html>
