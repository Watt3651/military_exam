#!/bin/zsh
set -euo pipefail

ROOT_DIR=${0:A:h:h}
APP_URL=${APP_URL:-http://127.0.0.1:18080}
MOCK_API_CLIENT_KEY=${MOCK_API_CLIENT_KEY:-default-ingestion-client}
MOCK_API_CLIENT_SECRET=${MOCK_API_CLIENT_SECRET:-change-me-in-production}
MOCK_UNIT_CODE=${MOCK_UNIT_CODE:-fleet}
MOCK_METRIC_KEY=${MOCK_METRIC_KEY:-support.efficiency.material.ssot_pon_kr}
MOCK_METRIC_FIELD=${MOCK_METRIC_FIELD:-percent}
MOCK_METRIC_VALUE=${MOCK_METRIC_VALUE:-}
MOCK_PERCENT=${MOCK_PERCENT:-85}

if [[ -z "$MOCK_METRIC_VALUE" ]]; then
  MOCK_METRIC_VALUE=$MOCK_PERCENT
fi

if [[ "$MOCK_METRIC_FIELD" == "percent" ]]; then
  if [[ ! "$MOCK_METRIC_VALUE" =~ '^[0-9]+([.][0-9]+)?$' ]]; then
    echo "MOCK_METRIC_VALUE must be numeric when MOCK_METRIC_FIELD=percent" >&2
    exit 1
  fi

  if (( $(printf '%.0f' "$MOCK_METRIC_VALUE") < 0 )) || (( $(printf '%.0f' "$MOCK_METRIC_VALUE") > 100 )); then
    echo "MOCK_METRIC_VALUE must be between 0 and 100 when MOCK_METRIC_FIELD=percent" >&2
    exit 1
  fi
fi

PAYLOAD=$(cd "$ROOT_DIR" && \
MOCK_UNIT_CODE="$MOCK_UNIT_CODE" \
MOCK_METRIC_KEY="$MOCK_METRIC_KEY" \
MOCK_METRIC_FIELD="$MOCK_METRIC_FIELD" \
MOCK_METRIC_VALUE="$MOCK_METRIC_VALUE" \
MOCK_PERCENT="$MOCK_PERCENT" \
php -r '
$unitCode = getenv("MOCK_UNIT_CODE") ?: "fleet";
$metricKey = getenv("MOCK_METRIC_KEY") ?: "support.efficiency.material.ssot_pon_kr";
$metricField = getenv("MOCK_METRIC_FIELD") ?: "percent";
$metricValue = getenv("MOCK_METRIC_VALUE");
if ($metricValue === false || $metricValue === "") {
    $metricValue = getenv("MOCK_PERCENT");
}
$value = $metricValue;
if ($metricField === "percent" && is_numeric($metricValue)) {
    $value = (float) $metricValue;
} elseif ($metricField === "score" && is_numeric($metricValue)) {
    $value = (int) $metricValue;
}
$metric = ["key" => $metricKey, $metricField => $value];
echo json_encode([
  "unit_code" => $unitCode,
  "metrics" => [$metric],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
')

curl -fsSL \
  -H "Content-Type: application/json" \
  -H "X-Client-Key: $MOCK_API_CLIENT_KEY" \
  -H "X-Client-Secret: $MOCK_API_CLIENT_SECRET" \
  -d "$PAYLOAD" \
  "$APP_URL/api/v1/mock-percent-ingest.php"

echo
