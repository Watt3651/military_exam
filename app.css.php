<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: text/css; charset=UTF-8');
?>
:root{<?= themeCssVariables() ?>}

*{box-sizing:border-box;margin:0;padding:0}

body{
  font-family:'Sarabun',sans-serif;
  background:var(--theme-page-bg);
  color:var(--theme-text);
  font-size:15px;
}

.topbar{
  background:var(--theme-topbar-bg);
  color:var(--theme-primary-text);
  padding:14px 24px;
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:12px;
  flex-wrap:wrap;
}

.topbar-title{
  font-size:16px;
  font-weight:600;
}

.topbar-sub{
  font-size:12px;
  opacity:0.75;
  margin-top:2px;
}

.topbar a{
  color:var(--theme-topbar-link);
  text-decoration:none;
  font-size:13px;
}

.topbar a:hover{
  color:var(--theme-topbar-link-hover);
}

.container{
  max-width:1200px;
  margin:0 auto;
  padding:24px 20px;
}

.container.container-narrow{
  max-width:1080px;
}

.container.container-sm{
  max-width:1100px;
}

.container.container-lg{
  max-width:1280px;
}

.container.container-wide{
  max-width:1320px;
}

.container.container-xl{
  max-width:1360px;
}

.section,
.panel{
  background:var(--theme-surface);
  border:1px solid var(--theme-border-soft);
  border-radius:12px;
  padding:18px 20px;
  margin-bottom:18px;
}

.section-title{
  font-size:15px;
  font-weight:600;
  color:var(--theme-text);
  margin-bottom:14px;
  padding-bottom:8px;
  border-bottom:1px solid var(--theme-border-soft);
}

.section h2,
.panel h2{
  font-size:16px;
  margin-bottom:14px;
}

.section h3,
.panel h3{
  font-size:14px;
  margin-bottom:10px;
}

label,
.form-label{
  display:block;
  font-size:12px;
  color:var(--theme-text-muted);
  margin-bottom:4px;
}

input,
select,
textarea,
.form-control{
  width:100%;
  padding:9px 10px;
  border:1px solid var(--theme-border-strong);
  border-radius:8px;
  font-family:inherit;
  background:var(--theme-surface);
  color:var(--theme-text);
}

button,
.btn{
  padding:10px 16px;
  background:var(--theme-primary);
  color:var(--theme-primary-text);
  border:none;
  border-radius:8px;
  font-family:inherit;
  cursor:pointer;
}

button:hover,
.btn:hover{
  background:var(--theme-primary-hover);
}

.btn-secondary{
  background:var(--theme-neutral-button);
}

.btn-secondary:hover{
  background:var(--theme-text-muted);
}

.btn-danger{
  background:var(--theme-danger-button);
}

.btn-danger:hover{
  background:#7f2525;
}

table{
  width:100%;
  border-collapse:collapse;
  font-size:13px;
}

th,
td{
  padding:10px;
  border-bottom:1px solid var(--theme-border-table);
  text-align:left;
  vertical-align:top;
}

th{
  font-size:12px;
  color:var(--theme-text-muted);
  white-space:nowrap;
}

.msg{
  padding:10px 12px;
  border-radius:8px;
  margin-bottom:12px;
  font-size:13px;
}

.ok{
  background:var(--theme-success-bg);
  color:var(--theme-success-text);
}

.err{
  background:var(--theme-danger-bg);
  color:var(--theme-danger-text);
}

.warn{
  background:var(--theme-warning-bg);
  color:var(--theme-warning-text);
}

.hint,
.muted,
.source,
.row-meta,
.save-hint{
  font-size:12px;
  color:var(--theme-text-soft);
  line-height:1.6;
}

code{
  font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
  background:var(--theme-surface-code);
  padding:2px 6px;
  border-radius:6px;
}

.pill{
  display:inline-block;
  padding:3px 8px;
  border-radius:999px;
  font-size:12px;
  background:var(--theme-surface-muted);
  color:var(--theme-primary);
}

.pill.risk{
  background:var(--theme-danger-bg);
  color:var(--theme-danger-text);
}

@media(max-width:1100px){
  table.responsive-table{
    display:block;
    overflow-x:auto;
    white-space:nowrap;
  }
}