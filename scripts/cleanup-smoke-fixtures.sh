#!/bin/zsh
set -euo pipefail

ROOT_DIR=${0:A:h:h}
SMOKE_ENV_FILE=${SMOKE_ENV_FILE:-}
SMOKE_ALLOW_PRODUCTION=${SMOKE_ALLOW_PRODUCTION:-0}
SMOKE_CLEANUP_MODE=${SMOKE_CLEANUP_MODE:-delete}
SMOKE_FIXTURE_USERNAMES=${SMOKE_FIXTURE_USERNAMES:-smoke_admin,lockout_smoke}
SMOKE_FIXTURE_CLIENT_KEYS=${SMOKE_FIXTURE_CLIENT_KEYS:-smoke-client-fixed}

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
  echo "Refusing to clean smoke fixtures against APP_ENV=production. Set SMOKE_ALLOW_PRODUCTION=1 only if you really intend to." >&2
  exit 1
fi

cd "$ROOT_DIR"
SMOKE_CLEANUP_MODE="$SMOKE_CLEANUP_MODE" \
SMOKE_FIXTURE_USERNAMES="$SMOKE_FIXTURE_USERNAMES" \
SMOKE_FIXTURE_CLIENT_KEYS="$SMOKE_FIXTURE_CLIENT_KEYS" \
php -r '
require "config.php";
require "functions.php";

function csv_values(string $value): array {
    return array_values(array_filter(array_map(static fn(string $part): string => trim($part), explode(",", $value)), static fn(string $part): bool => $part !== ""));
}

function placeholders(int $count): string {
    return implode(",", array_fill(0, $count, "?"));
}

function fetch_all_map(PDO $pdo, string $sql, array $params): array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

$mode = (string) getenv("SMOKE_CLEANUP_MODE");
$usernames = csv_values((string) getenv("SMOKE_FIXTURE_USERNAMES"));
$clientKeys = csv_values((string) getenv("SMOKE_FIXTURE_CLIENT_KEYS"));
$pdo = db();

$users = [];
if ($usernames) {
    $users = fetch_all_map($pdo, "SELECT id, username, display_name FROM users WHERE username IN (" . placeholders(count($usernames)) . ")", $usernames);
}
$clients = [];
if ($clientKeys) {
    $clients = fetch_all_map($pdo, "SELECT id, client_key, name FROM api_clients WHERE client_key IN (" . placeholders(count($clientKeys)) . ")", $clientKeys);
}

$userIds = array_map(static fn(array $row): int => (int) $row["id"], $users);
$displayNames = array_map(static fn(array $row): string => (string) $row["display_name"], $users);
$clientIds = array_map(static fn(array $row): int => (int) $row["id"], $clients);
$clientNames = array_map(static fn(array $row): string => (string) $row["name"], $clients);

if ($mode === "disable") {
    if ($userIds) {
        $stmt = $pdo->prepare("UPDATE users SET is_active = 0, failed_login_attempts = 0, last_failed_login_at = NULL, locked_until = NULL, password_reset_token_hash = NULL, password_reset_token_expires_at = NULL, updated_at = ? WHERE id IN (" . placeholders(count($userIds)) . ")");
        $stmt->execute(array_merge([nowString()], $userIds));
    }
    if ($clientIds) {
        $stmt = $pdo->prepare("UPDATE api_clients SET is_active = 0, updated_at = ? WHERE id IN (" . placeholders(count($clientIds)) . ")");
        $stmt->execute(array_merge([nowString()], $clientIds));
    }

    echo json_encode([
        "mode" => $mode,
        "disabled_users" => array_values($usernames),
        "disabled_clients" => array_values($clientKeys),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($clientIds) {
    $stmt = $pdo->prepare("DELETE FROM api_request_log WHERE client_id IN (" . placeholders(count($clientIds)) . ")");
    $stmt->execute($clientIds);
}
if ($clientKeys) {
    $stmt = $pdo->prepare("DELETE FROM api_request_log WHERE client_key IN (" . placeholders(count($clientKeys)) . ")");
    $stmt->execute($clientKeys);
}

if ($userIds) {
    $stmt = $pdo->prepare("DELETE FROM readiness_entries WHERE updated_by_user_id IN (" . placeholders(count($userIds)) . ")");
    $stmt->execute($userIds);
}
if ($clientNames) {
    $stmt = $pdo->prepare("DELETE FROM readiness_entries WHERE source = ? AND source_name IN (" . placeholders(count($clientNames)) . ")");
    $stmt->execute(array_merge(["api"], $clientNames));
}

$auditClauses = [];
$auditParams = [];
if ($userIds) {
    $auditClauses[] = "user_id IN (" . placeholders(count($userIds)) . ")";
    $auditParams = array_merge($auditParams, $userIds);
}
if ($displayNames) {
    $auditClauses[] = "actor_name IN (" . placeholders(count($displayNames)) . ")";
    $auditParams = array_merge($auditParams, $displayNames);
}
$entityKeys = array_merge($usernames, $clientKeys);
if ($entityKeys) {
    $auditClauses[] = "entity_key IN (" . placeholders(count($entityKeys)) . ")";
    $auditParams = array_merge($auditParams, $entityKeys);
}
if ($auditClauses) {
    $stmt = $pdo->prepare("DELETE FROM audit_log WHERE " . implode(" OR ", $auditClauses));
    $stmt->execute($auditParams);
}

if ($clientKeys) {
    $stmt = $pdo->prepare("DELETE FROM api_clients WHERE client_key IN (" . placeholders(count($clientKeys)) . ")");
    $stmt->execute($clientKeys);
}
if ($usernames) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE username IN (" . placeholders(count($usernames)) . ")");
    $stmt->execute($usernames);
}

echo json_encode([
    "mode" => $mode,
    "deleted_users" => array_values($usernames),
    "deleted_clients" => array_values($clientKeys),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
'