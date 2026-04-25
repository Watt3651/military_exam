#!/bin/zsh
set -euo pipefail

ROOT_DIR=${0:A:h:h}
BACKUP_PATH=${1:-}
ALLOW_PRODUCTION_SYNC=${ALLOW_PRODUCTION_SYNC:-0}
SYNC_ENV_FILE=${SYNC_ENV_FILE:-}

load_env_file() {
  local env_file=$1
  if [[ -z "$env_file" ]]; then
    return
  fi
  if [[ ! -f "$env_file" ]]; then
    echo "Sync env file not found: $env_file" >&2
    exit 1
  fi
  set -a
  source "$env_file"
  set +a
}

load_env_file "$SYNC_ENV_FILE"

if [[ -z "$BACKUP_PATH" ]]; then
  echo "Usage: ./scripts/restore-readiness-sync-backup.sh path/to/backup.json" >&2
  exit 1
fi

cd "$ROOT_DIR"

php -r '
require "config.php";
require "functions.php";

$backupPath = $argv[1];
$allowProduction = ((string) getenv("ALLOW_PRODUCTION_SYNC")) === "1";
$result = restoreReadinessSyncBackup($backupPath, db(), null, $allowProduction, "cli");
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
' "$BACKUP_PATH"