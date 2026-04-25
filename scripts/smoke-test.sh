#!/bin/zsh
set -euo pipefail

ROOT_DIR=${0:A:h:h}
SMOKE_ENV_FILE=${SMOKE_ENV_FILE:-}
SMOKE_AUTO_PREPARE=${SMOKE_AUTO_PREPARE:-0}
SMOKE_AUTO_CLEANUP=${SMOKE_AUTO_CLEANUP:-0}
SMOKE_ALLOW_PRODUCTION=${SMOKE_ALLOW_PRODUCTION:-0}
APP_URL=${APP_URL:-http://127.0.0.1:18080}
SMOKE_ADMIN_USER=${SMOKE_ADMIN_USER:-smoke_admin}
SMOKE_ADMIN_PASS=${SMOKE_ADMIN_PASS:-SmokeAdmin1!}
SMOKE_API_CLIENT_KEY=${SMOKE_API_CLIENT_KEY:-smoke-client-fixed}
SMOKE_API_CLIENT_SECRET=${SMOKE_API_CLIENT_SECRET:-SmokeSecret1!}
SMOKE_TEST_MOCK_PERCENT=${SMOKE_TEST_MOCK_PERCENT:-1}
SMOKE_MOCK_PERCENT_KEY=${SMOKE_MOCK_PERCENT_KEY:-support.efficiency.material.ssot_pon_kr}
SMOKE_CURL_RETRY_COUNT=${SMOKE_CURL_RETRY_COUNT:-5}
COOKIE_JAR=$(mktemp)
LOGIN_HTML=$(mktemp)
INPUT_HTML=$(mktemp)
DASHBOARD_HTML=$(mktemp)
AUDIT_HTML=$(mktemp)
API_RESPONSE=$(mktemp)
MOCK_API_RESPONSE=$(mktemp)
PREPARED_ENV_FILE=$(mktemp)

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

run_php() {
  (cd "$ROOT_DIR" && php "$@")
}

app_env() {
  (cd "$ROOT_DIR" && php -r 'require "config.php"; echo APP_ENV;')
}

curl_with_retry() {
  curl --retry "$SMOKE_CURL_RETRY_COUNT" --retry-delay 1 --retry-connrefused "$@"
}

cleanup() {
  local exit_code=$?
  if [[ "$SMOKE_AUTO_CLEANUP" == "1" ]]; then
    SMOKE_ENV_FILE=${SMOKE_ENV_FILE:-} \
    SMOKE_ALLOW_PRODUCTION=$SMOKE_ALLOW_PRODUCTION \
    SMOKE_FIXTURE_USERNAMES=${SMOKE_FIXTURE_USERNAMES:-$SMOKE_ADMIN_USER} \
    SMOKE_FIXTURE_CLIENT_KEYS=${SMOKE_FIXTURE_CLIENT_KEYS:-$SMOKE_API_CLIENT_KEY} \
    "$ROOT_DIR/scripts/cleanup-smoke-fixtures.sh" >/dev/null 2>&1 || true
  fi
  rm -f "$COOKIE_JAR" "$LOGIN_HTML" "$INPUT_HTML" "$DASHBOARD_HTML" "$AUDIT_HTML" "$API_RESPONSE" "$MOCK_API_RESPONSE" "$PREPARED_ENV_FILE"
  return $exit_code
}
trap cleanup EXIT

load_env_file "$SMOKE_ENV_FILE"

if [[ "$SMOKE_AUTO_PREPARE" == "1" ]]; then
  SMOKE_ENV_FILE=${SMOKE_ENV_FILE:-} \
  SMOKE_ALLOW_PRODUCTION=$SMOKE_ALLOW_PRODUCTION \
  SMOKE_OUTPUT_ENV_FILE="$PREPARED_ENV_FILE" \
  "$ROOT_DIR/scripts/prepare-smoke-fixtures.sh"
  load_env_file "$PREPARED_ENV_FILE"
fi

APP_ENV_CHECK=$(app_env)
if [[ "$APP_ENV_CHECK" == "production" && "$SMOKE_ALLOW_PRODUCTION" != "1" ]]; then
  echo "Refusing to run smoke test against APP_ENV=production. Set SMOKE_ALLOW_PRODUCTION=1 only if you really intend to." >&2
  exit 1
fi

extract_csrf() {
  perl -ne 'print $1 if /name="csrf_token" value="([^"]+)"/' "$1" | head -n 1
}

assert_contains() {
  local file=$1
  local expected=$2
  if ! grep -q "$expected" "$file"; then
    echo "Expected to find '$expected' in $file" >&2
    exit 1
  fi
}

echo "[1/5] Fetch login page"
curl_with_retry -fsSL -c "$COOKIE_JAR" "$APP_URL/login.php" -o "$LOGIN_HTML"
LOGIN_CSRF=$(extract_csrf "$LOGIN_HTML")
if [[ -z "$LOGIN_CSRF" ]]; then
  echo "Unable to extract CSRF token from login page" >&2
  exit 1
fi

echo "[2/5] Submit login form"
curl_with_retry -fsSL -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
  -d "csrf_token=$LOGIN_CSRF" \
  -d "username=$SMOKE_ADMIN_USER" \
  -d "password=$SMOKE_ADMIN_PASS" \
  "$APP_URL/login.php" -o "$INPUT_HTML"
assert_contains "$INPUT_HTML" "หน้ากรอกข้อมูลเจ้าหน้าที่"

echo "[3/5] Check dashboard and audit log"
curl_with_retry -fsSL -b "$COOKIE_JAR" "$APP_URL/index.php" -o "$DASHBOARD_HTML"
assert_contains "$DASHBOARD_HTML" "ภาพรวมสถานะความพร้อม"
curl_with_retry -fsSL -b "$COOKIE_JAR" "$APP_URL/admin/audit-log.php" -o "$AUDIT_HTML"
assert_contains "$AUDIT_HTML" "Audit Log"

echo "[4/5] Resolve API client key"
if [[ -z "$SMOKE_API_CLIENT_KEY" ]]; then
  echo "Unable to resolve API client key" >&2
  exit 1
fi
PAYLOAD=$(cd "$ROOT_DIR" && php -r 'require "config.php"; require "functions.php"; $rows = emptyRowsStructure(); $rows["develop"]["personnel"][0] = 2; echo json_encode(["unit_code" => DEFAULT_UNIT_CODE, "rows" => $rows], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);')

echo "[5/5] POST API ingest"
curl_with_retry -fsSL \
  -H "Content-Type: application/json" \
  -H "X-Client-Key: $SMOKE_API_CLIENT_KEY" \
  -H "X-Client-Secret: $SMOKE_API_CLIENT_SECRET" \
  -d "$PAYLOAD" \
  "$APP_URL/api/v1/ingest.php" -o "$API_RESPONSE"
assert_contains "$API_RESPONSE" '"ok":true'

if [[ "$SMOKE_TEST_MOCK_PERCENT" == "1" ]]; then
  echo "[6/6] POST API mock percent ingest"
  MOCK_PAYLOAD=$(cd "$ROOT_DIR" && php -r 'require "config.php"; echo json_encode(["unit_code" => DEFAULT_UNIT_CODE, "metrics" => [["key" => getenv("SMOKE_MOCK_PERCENT_KEY") ?: "support.efficiency.material.ssot_pon_kr", "percent" => 85]]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);')
  curl_with_retry -fsSL \
    -H "Content-Type: application/json" \
    -H "X-Client-Key: $SMOKE_API_CLIENT_KEY" \
    -H "X-Client-Secret: $SMOKE_API_CLIENT_SECRET" \
    -d "$MOCK_PAYLOAD" \
    "$APP_URL/api/v1/mock-percent-ingest.php" -o "$MOCK_API_RESPONSE"
  assert_contains "$MOCK_API_RESPONSE" '"ok":true'
  assert_contains "$MOCK_API_RESPONSE" '"score":2'
fi

echo "Smoke test passed"