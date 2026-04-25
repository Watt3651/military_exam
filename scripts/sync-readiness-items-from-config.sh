#!/bin/zsh
set -euo pipefail

ROOT_DIR=${0:A:h:h}
MODE=${1:-preview}
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

if [[ "$MODE" != "preview" && "$MODE" != "apply" ]]; then
  echo "Usage: ./scripts/sync-readiness-items-from-config.sh [preview|apply]" >&2
  exit 1
fi

cd "$ROOT_DIR"

php -r '
require "config.php";
require "functions.php";

$mode = $argv[1];
$allowProduction = ((string) getenv("ALLOW_PRODUCTION_SYNC")) === "1";

if ($mode === "apply" && APP_ENV === "production" && !$allowProduction) {
    fwrite(STDERR, "Refusing to sync readiness items against APP_ENV=production. Set ALLOW_PRODUCTION_SYNC=1 only if you really intend to.\n");
    exit(1);
}

$pdo = db();
$diffData = buildReadinessItemsDiff($pdo);
$summary = [
    "mode" => $mode,
    "app_env" => APP_ENV,
    "summary" => $diffData["summary"],
    "diff" => $diffData["diff"],
    "impact" => $diffData["impact"],
];

if ($mode === "apply") {
    $result = applyReadinessItemsSyncWithBackup($pdo, null, $allowProduction, "cli");
    $summary["applied"] = (bool) ($result["applied"] ?? false);
    $summary["no_changes"] = (bool) ($result["no_changes"] ?? false);
    $summary["backup"] = $result["backup"] ?? null;
    $summary["summary"] = $result["summary"] ?? $summary["summary"];
} else {
    $summary["applied"] = false;
}

echo json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
' "$MODE"