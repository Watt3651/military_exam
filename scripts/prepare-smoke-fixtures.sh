#!/bin/zsh
set -euo pipefail

ROOT_DIR=${0:A:h:h}
SMOKE_ENV_FILE=${SMOKE_ENV_FILE:-}
SMOKE_OUTPUT_ENV_FILE=${SMOKE_OUTPUT_ENV_FILE:-}
SMOKE_ALLOW_PRODUCTION=${SMOKE_ALLOW_PRODUCTION:-0}
SMOKE_ADMIN_USER=${SMOKE_ADMIN_USER:-smoke_admin}
SMOKE_ADMIN_PASS=${SMOKE_ADMIN_PASS:-SmokeAdmin1!}
SMOKE_API_CLIENT_NAME=${SMOKE_API_CLIENT_NAME:-Smoke Client}
SMOKE_API_CLIENT_KEY=${SMOKE_API_CLIENT_KEY:-smoke-client-fixed}
SMOKE_API_CLIENT_SECRET=${SMOKE_API_CLIENT_SECRET:-SmokeSecret1!}

load_env_file() {
  local env_file=$1
  if [[ -z "$env_file" ]]; then
    return
  fi
  if [[ ! -f "$env_file" ]]; then
    echo "Smoke env file not found: $env_file" >&2
    exit 1
  fi
  set -a
  source "$env_file"
  set +a
}

load_env_file "$SMOKE_ENV_FILE"

APP_ENV_CHECK=$(cd "$ROOT_DIR" && php -r 'require "config.php"; echo APP_ENV;')
if [[ "$APP_ENV_CHECK" == "production" && "$SMOKE_ALLOW_PRODUCTION" != "1" ]]; then
  echo "Refusing to prepare smoke fixtures against APP_ENV=production. Set SMOKE_ALLOW_PRODUCTION=1 only if you really intend to." >&2
  exit 1
fi

RESULT=$(cd "$ROOT_DIR" && \
  SMOKE_ADMIN_USER="$SMOKE_ADMIN_USER" \
  SMOKE_ADMIN_PASS="$SMOKE_ADMIN_PASS" \
  SMOKE_API_CLIENT_NAME="$SMOKE_API_CLIENT_NAME" \
  SMOKE_API_CLIENT_KEY="$SMOKE_API_CLIENT_KEY" \
  SMOKE_API_CLIENT_SECRET="$SMOKE_API_CLIENT_SECRET" \
  php -r '
require "config.php";
require "functions.php";

$unitId = getUnitIdByCode(DEFAULT_UNIT_CODE);
if ($unitId === null) {
    throw new RuntimeException("Default unit not found");
}

$username = (string) getenv("SMOKE_ADMIN_USER");
$password = (string) getenv("SMOKE_ADMIN_PASS");
$clientName = (string) getenv("SMOKE_API_CLIENT_NAME");
$clientKey = (string) getenv("SMOKE_API_CLIENT_KEY");
$clientSecret = (string) getenv("SMOKE_API_CLIENT_SECRET");

$user = findUserByUsername($username);
if (!$user) {
    createUser($username, "Smoke Admin", $password, "admin", $unitId, true);
    $userAction = "created";
} else {
    updateUser((int) $user["id"], "Smoke Admin", "admin", $unitId, true, $password);
    $userAction = "updated";
}

$client = findApiClientByClientKey($clientKey);
$pdo = db();
if (!$client) {
    $stmt = $pdo->prepare("INSERT INTO api_clients (name, client_key, token_hash, unit_id, allowed_ips, rate_limit_per_minute, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $now = nowString();
    $stmt->execute([$clientName, $clientKey, password_hash($clientSecret, PASSWORD_DEFAULT), $unitId, "", API_DEFAULT_RATE_LIMIT_PER_MINUTE, 1, $now, $now]);
    $clientAction = "created";
} else {
    $stmt = $pdo->prepare("UPDATE api_clients SET name = ?, token_hash = ?, unit_id = ?, allowed_ips = ?, rate_limit_per_minute = ?, is_active = 1, updated_at = ? WHERE client_key = ?");
    $stmt->execute([$clientName, password_hash($clientSecret, PASSWORD_DEFAULT), $unitId, "", API_DEFAULT_RATE_LIMIT_PER_MINUTE, nowString(), $clientKey]);
    $clientAction = "updated";
}

echo json_encode([
    "user_action" => $userAction,
    "client_action" => $clientAction,
    "smoke_admin_user" => $username,
    "smoke_admin_pass" => $password,
    "smoke_api_client_key" => $clientKey,
    "smoke_api_client_secret" => $clientSecret,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
')

if [[ -n "$SMOKE_OUTPUT_ENV_FILE" ]]; then
  php -r '
$data = json_decode($argv[1], true, 512, JSON_THROW_ON_ERROR);
$lines = [
    "SMOKE_ADMIN_USER=" . $data["smoke_admin_user"],
    "SMOKE_ADMIN_PASS=" . $data["smoke_admin_pass"],
    "SMOKE_API_CLIENT_KEY=" . $data["smoke_api_client_key"],
    "SMOKE_API_CLIENT_SECRET=" . $data["smoke_api_client_secret"],
];
file_put_contents($argv[2], implode(PHP_EOL, $lines) . PHP_EOL);
' "$RESULT" "$SMOKE_OUTPUT_ENV_FILE"
fi

echo "$RESULT"