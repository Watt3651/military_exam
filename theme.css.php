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

a{
  color:var(--theme-link);
}

a:hover{
  color:var(--theme-link-hover);
}

.auth-shell{
  min-height:100vh;
  display:flex;
  align-items:center;
  justify-content:center;
  padding:24px;
  background:
    radial-gradient(circle at top, rgba(147,197,253,.18) 0%, rgba(147,197,253,.05) 28%, transparent 55%),
    linear-gradient(180deg, var(--theme-page-bg) 0%, var(--theme-surface-alt) 100%);
}

.auth-card{
  width:100%;
  max-width:430px;
  background:var(--theme-surface);
  border:1px solid var(--theme-border-soft);
  border-radius:18px;
  padding:2rem 2.25rem;
  box-shadow:0 22px 60px rgba(8,21,35,.10);
}

.auth-card.auth-card-sm{
  max-width:380px;
}

.auth-header{
  text-align:center;
  margin-bottom:1.5rem;
}

.auth-icon{
  width:58px;
  height:58px;
  margin:0 auto;
  border-radius:18px;
  display:flex;
  align-items:center;
  justify-content:center;
  background:linear-gradient(135deg, var(--theme-surface-muted) 0%, var(--theme-surface-muted-end) 100%);
  color:var(--theme-primary);
  box-shadow:inset 0 1px 0 rgba(255,255,255,.72);
}

.auth-title{
  font-size:20px;
  font-weight:600;
  line-height:1.4;
  color:var(--theme-text);
  margin-top:12px;
}

.auth-subtitle{
  font-size:12px;
  color:var(--theme-text-soft);
  margin-top:6px;
}

.form-label{
  display:block;
  font-size:13px;
  color:var(--theme-text-muted);
  margin-bottom:4px;
  margin-top:14px;
}

.form-input{
  width:100%;
  padding:10px 12px;
  border:1px solid var(--theme-border-strong);
  border-radius:8px;
  font-size:14px;
  font-family:inherit;
  color:var(--theme-text);
  background:var(--theme-surface);
  outline:none;
}

.form-input:focus{
  border-color:var(--theme-primary);
  box-shadow:0 0 0 3px rgba(28,79,128,0.12);
}

.btn-primary{
  display:block;
  width:100%;
  margin-top:1.25rem;
  padding:11px;
  background:var(--theme-primary);
  color:var(--theme-primary-text);
  border:none;
  border-radius:8px;
  font-size:14px;
  font-family:inherit;
  font-weight:500;
  cursor:pointer;
  text-align:center;
}

.btn-primary:hover{
  background:var(--theme-primary-hover);
}

.alert-ok,
.alert-error{
  border-radius:8px;
  padding:9px 12px;
  font-size:13px;
  margin-top:12px;
}

.alert-ok{
  background:var(--theme-success-bg);
  color:var(--theme-success-text);
}

.alert-error{
  background:var(--theme-danger-bg);
  color:var(--theme-danger-text);
}

.muted-copy{
  margin-top:12px;
  font-size:12px;
  color:var(--theme-text-soft);
  line-height:1.5;
}

.subtle-copy{
  font-size:13px;
  color:var(--theme-text-muted);
}

.back-link{
  display:block;
  text-align:center;
  margin-top:14px;
  font-size:13px;
  color:var(--theme-text-soft);
  text-decoration:none;
}

.back-link:hover{
  color:var(--theme-link-hover);
}

@media(max-width:560px){
  .auth-shell{
    padding:16px;
  }

  .auth-card,
  .auth-card.auth-card-sm{
    padding:1.5rem 1.25rem;
    border-radius:16px;
  }
}