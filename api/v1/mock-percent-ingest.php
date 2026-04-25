<?php
require_once '../../config.php';
require_once '../../functions.php';

applySecurityHeaders(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Method not allowed'], 405);
}

try {
    $client = authenticateApiRequest();
    $payload = requestJsonBody();

    if (!isset($payload['metrics']) || !is_array($payload['metrics'])) {
        recordApiRequest($client, 422, (string) ($_SERVER['REQUEST_URI'] ?? '/api/v1/mock-percent-ingest.php'));
        jsonResponse(['ok' => false, 'error' => 'Payload must contain metrics array'], 422);
    }

    $unitId = isset($client['unit_id']) ? (int) $client['unit_id'] : null;
    if ($unitId === null) {
        $unitCode = trim((string) ($payload['unit_code'] ?? ''));
        if ($unitCode === '') {
            recordApiRequest($client, 422, (string) ($_SERVER['REQUEST_URI'] ?? '/api/v1/mock-percent-ingest.php'));
            jsonResponse(['ok' => false, 'error' => 'unit_code is required for unscoped API clients'], 422);
        }
        $unitId = getUnitIdByCode($unitCode);
    }

    if ($unitId === null) {
        recordApiRequest($client, 404, (string) ($_SERVER['REQUEST_URI'] ?? '/api/v1/mock-percent-ingest.php'));
        jsonResponse(['ok' => false, 'error' => 'Unit not found'], 404);
    }

    $rows = loadRowsBySource(db(), $unitId, MOCK_PERCENT_SOURCE);
    $converted = applyMockPercentMetricsToRows($rows, $payload['metrics'], db());
    if ($converted['errors']) {
        recordApiRequest($client, 422, (string) ($_SERVER['REQUEST_URI'] ?? '/api/v1/mock-percent-ingest.php'));
        jsonResponse([
            'ok' => false,
            'error' => 'Validation failed',
            'details' => $converted['errors'],
        ], 422);
    }

    persistRows(db(), $unitId, sanitizeRows((array) $converted['rows']), MOCK_PERCENT_SOURCE, null, (string) $client['name']);

    $unit = getUnitById($unitId);
    recordApiRequest($client, 200, (string) ($_SERVER['REQUEST_URI'] ?? '/api/v1/mock-percent-ingest.php'));
    jsonResponse([
        'ok' => true,
        'message' => 'Mock readiness metrics ingested',
        'unit' => $unit['name'] ?? null,
        'source' => MOCK_PERCENT_SOURCE,
        'client_key' => $client['client_key'] ?? null,
        'applied_metrics' => $converted['applied'],
        'updated_at' => nowString(),
    ]);
} catch (Throwable $e) {
    if (isset($client) && is_array($client)) {
        recordApiRequest($client, 500, (string) ($_SERVER['REQUEST_URI'] ?? '/api/v1/mock-percent-ingest.php'));
    }
    jsonResponse([
        'ok' => false,
        'error' => appDebugEnabled() ? $e->getMessage() : 'Internal server error',
        'request_id' => requestIdentifier(),
    ], 500);
}
