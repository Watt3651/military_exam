<?php
function appEnv(): string {
    return APP_ENV;
}

function appIsProduction(): bool {
    return strtolower(appEnv()) === 'production';
}

function appDebugEnabled(): bool {
    return APP_DEBUG;
}

function isHttpsRequest(): bool {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    if (TRUST_PROXY_HEADERS) {
        $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        return $forwardedProto === 'https';
    }
    return false;
}

function currentRequestIp(): string {
    if (TRUST_PROXY_HEADERS && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwardedFor = explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($forwardedFor[0]);
    }
    return trim((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
}

function currentRequestUserAgent(): string {
    return trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));
}

function requestIdentifier(): string {
    static $requestId = null;
    if ($requestId !== null) {
        return $requestId;
    }

    $requestId = bin2hex(random_bytes(8));
    return $requestId;
}

function applySecurityHeaders(bool $apiMode = false): void {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-XSS-Protection: 0');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

    if ($apiMode) {
        header('Cache-Control: no-store, private');
        header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'");
        return;
    }

    header('Cache-Control: no-store, private');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; script-src 'self' 'unsafe-inline'; connect-src 'self'; frame-ancestors 'self'; form-action 'self'");
}

function themeColor(string $key, string $fallback = ''): string {
    return (string) (APP_THEME[$key] ?? $fallback);
}

function themeCssVariables(): string {
    static $css = null;
    if ($css !== null) {
        return $css;
    }

    $vars = [];
    foreach (APP_THEME as $key => $value) {
        $cssKey = '--theme-' . str_replace('_', '-', $key);
        $vars[] = $cssKey . ':' . $value;
    }

    $css = implode(';', $vars) . ';';
    return $css;
}

function configurePhpSecurity(): void {
    if (!headers_sent()) {
        applySecurityHeaders();
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if (isHttpsRequest()) {
        ini_set('session.cookie_secure', '1');
    }
}

function calcCellScore(array $vals): float {
    $n = count($vals);
    if ($n === 0) {
        return 0;
    }

    $sum = array_sum($vals);
    return ($sum / ($n * 2)) * 100;
}

function readinessAllRowIds(): array {
    return array_values(array_keys(ITEMS));
}

function readinessConfiguredRowIds(string $raw, array $allowedRowIds): array {
    $raw = trim($raw);
    if ($raw === '') {
        return $allowedRowIds;
    }

    $requested = array_values(array_filter(array_map('trim', explode(',', $raw)), static fn(string $value): bool => $value !== ''));
    if (!$requested) {
        return $allowedRowIds;
    }

    $allowedLookup = array_fill_keys($allowedRowIds, true);
    $result = [];
    foreach ($requested as $rowId) {
        if (isset($allowedLookup[$rowId])) {
            $result[] = $rowId;
        }
    }

    return $result ?: $allowedRowIds;
}

function readinessVisibleRowIds(): array {
    if (array_key_exists('readiness_visible_row_ids_cache', $GLOBALS) && is_array($GLOBALS['readiness_visible_row_ids_cache'])) {
        return $GLOBALS['readiness_visible_row_ids_cache'];
    }

    $rowIds = readinessConfiguredRowIds(getAppSetting('readiness_visible_rows', READINESS_VISIBLE_ROWS) ?? '', readinessAllRowIds());
    $GLOBALS['readiness_visible_row_ids_cache'] = $rowIds;
    return $rowIds;
}

function readinessCalculatedRowIds(): array {
    if (array_key_exists('readiness_calculated_row_ids_cache', $GLOBALS) && is_array($GLOBALS['readiness_calculated_row_ids_cache'])) {
        return $GLOBALS['readiness_calculated_row_ids_cache'];
    }

    $visible = readinessVisibleRowIds();
    $configured = readinessConfiguredRowIds(getAppSetting('readiness_calculated_rows', READINESS_CALCULATED_ROWS) ?? '', $visible);
    $lookup = array_fill_keys($configured, true);

    $rowIds = [];
    foreach ($visible as $rowId) {
        if (isset($lookup[$rowId])) {
            $rowIds[] = $rowId;
        }
    }

    $GLOBALS['readiness_calculated_row_ids_cache'] = $rowIds;
    return $rowIds;
}

function readinessEffectiveRowWeights(): array {
    if (array_key_exists('readiness_effective_row_weights_cache', $GLOBALS) && is_array($GLOBALS['readiness_effective_row_weights_cache'])) {
        return $GLOBALS['readiness_effective_row_weights_cache'];
    }

    $calculatedRowIds = readinessCalculatedRowIds();
    $rawTotal = 0.0;
    foreach ($calculatedRowIds as $rowId) {
        $rawTotal += (float) (ROW_WEIGHTS[$rowId] ?? 0);
    }

    $weights = [];
    if ($rawTotal <= 0) {
        return $weights;
    }

    foreach ($calculatedRowIds as $rowId) {
        $weights[$rowId] = ((float) (ROW_WEIGHTS[$rowId] ?? 0)) / $rawTotal;
    }

    $GLOBALS['readiness_effective_row_weights_cache'] = $weights;
    return $weights;
}

function readinessRowEffectiveWeight(string $rowId): float {
    return (float) (readinessEffectiveRowWeights()[$rowId] ?? 0.0);
}

function readinessRowIsCalculated(string $rowId): bool {
    return readinessRowEffectiveWeight($rowId) > 0;
}

function readinessVisibleItemsDefinition(?array $items = null): array {
    $items ??= getItemsDefinition();
    $visibleLookup = array_fill_keys(readinessVisibleRowIds(), true);
    $filtered = [];
    foreach ($items as $rowId => $rowDef) {
        if (isset($visibleLookup[$rowId])) {
            $filtered[$rowId] = $rowDef;
        }
    }
    return $filtered;
}

function calcRowScore(string $rowId, array $rows): float {
    $total = 0;
    foreach (COL_WEIGHTS as $col => $weight) {
        $vals = $rows[$rowId][$col] ?? [];
        $total += $weight * calcCellScore($vals);
    }
    return $total;
}

function calcColScore(string $colId, array $rows): float {
    $total = 0;
    foreach (readinessEffectiveRowWeights() as $row => $weight) {
        $vals = $rows[$row][$colId] ?? [];
        $total += $weight * calcCellScore($vals);
    }
    return $total;
}

function calcRowContribution(string $rowId, array $rows): float {
    return readinessRowEffectiveWeight($rowId) * calcRowScore($rowId, $rows);
}

function calcColContribution(string $colId, array $rows): float {
    return ((float) (COL_WEIGHTS[$colId] ?? 0)) * calcColScore($colId, $rows);
}

function calcOverall(array $rows): float {
    $total = 0;
    foreach (COL_WEIGHTS as $col => $weight) {
        $total += $weight * calcColScore($col, $rows);
    }
    return $total;
}

function readinessThresholdSetting(string $settingKey, float $default, string $label, ?PDO $pdo = null): float {
    $raw = getAppSetting($settingKey, null, $pdo);
    if ($raw === null || trim($raw) === '') {
        $value = $default;
    } else {
        if (!is_numeric($raw)) {
            throw new InvalidArgumentException($label . ' ต้องเป็นตัวเลข');
        }
        $value = (float) $raw;
    }

    if ($value < 0 || $value > 100) {
        throw new InvalidArgumentException($label . ' ต้องอยู่ระหว่าง 0 ถึง 100');
    }

    return $value;
}

function readinessStatusThresholds(?PDO $pdo = null): array {
    $ready = readinessThresholdSetting('readiness_ready_threshold', READINESS_READY_THRESHOLD, 'เกณฑ์สถานะพร้อม', $pdo);
    $warning = readinessThresholdSetting('readiness_warning_threshold', READINESS_WARNING_THRESHOLD, 'เกณฑ์สถานะปานกลาง', $pdo);

    if ($warning >= $ready) {
        throw new InvalidArgumentException('เกณฑ์สถานะปานกลางต้องน้อยกว่าเกณฑ์สถานะพร้อม');
    }

    return [
        'ready' => $ready,
        'warning' => $warning,
        'ready_source' => readinessSettingsSourceLabel('readiness_ready_threshold', $pdo),
        'warning_source' => readinessSettingsSourceLabel('readiness_warning_threshold', $pdo),
    ];
}

function readinessScoreFromPercent(float $percent, ?PDO $pdo = null): int {
    $thresholds = readinessStatusThresholds($pdo);
    if ($percent >= (float) $thresholds['ready']) {
        return 2;
    }
    if ($percent >= (float) $thresholds['warning']) {
        return 1;
    }
    return 0;
}

function mockPercentMetricMapFromConfig(): array {
    $map = [];
    foreach (MOCK_PERCENT_METRIC_MAP as $metricKey => $target) {
        if (!is_array($target)) {
            continue;
        }
        $rowId = trim((string) ($target['row_id'] ?? ''));
        $colId = trim((string) ($target['col_id'] ?? ''));
        $itemIndex = isset($target['item_index']) ? (int) $target['item_index'] : null;
        if ($metricKey === '' || $rowId === '' || $colId === '' || $itemIndex === null) {
            continue;
        }
        $map[(string) $metricKey] = [
            'metric_key' => (string) $metricKey,
            'row_id' => $rowId,
            'col_id' => $colId,
            'item_index' => $itemIndex,
            'transform_type' => 'percent_threshold',
            'transform_payload' => null,
            'is_active' => 1,
            'source' => 'config',
        ];
    }
    return $map;
}

function mockTransformTypeOptions(): array {
    return [
        'percent_threshold' => 'Percent Threshold',
        'enum_map' => 'Enum / Status Map',
        'direct_score' => 'Direct Score (0-2)',
    ];
}

function normalizeMockPercentTransform(string $transformType, ?string $transformPayload): array {
    $transformType = trim($transformType);
    if (!isset(mockTransformTypeOptions()[$transformType])) {
        throw new InvalidArgumentException('transform type ไม่ถูกต้อง');
    }

    $payload = null;
    if ($transformPayload !== null && trim($transformPayload) !== '') {
        $decoded = json_decode($transformPayload, true);
        if (!is_array($decoded)) {
            throw new InvalidArgumentException('transform payload ต้องเป็น JSON object หรือเว้นว่าง');
        }
        $payload = $decoded;
    }

    if ($transformType === 'enum_map') {
        $payload ??= [];
        if (!isset($payload['map']) || !is_array($payload['map']) || !$payload['map']) {
            throw new InvalidArgumentException('enum_map ต้องมี payload.map อย่างน้อย 1 ค่า');
        }
        foreach ($payload['map'] as $status => $score) {
            if (!is_string($status) || trim($status) === '') {
                throw new InvalidArgumentException('enum_map key ต้องเป็นข้อความ');
            }
            if (!is_numeric($score) || (int) $score < 0 || (int) $score > 2) {
                throw new InvalidArgumentException('enum_map score ต้องอยู่ระหว่าง 0 ถึง 2');
            }
        }
    }

    if ($transformType === 'direct_score' && $payload !== null) {
        if (isset($payload['allowed']) && (!is_array($payload['allowed']) || !$payload['allowed'])) {
            throw new InvalidArgumentException('direct_score.allowed ต้องเป็น array ที่ไม่ว่างถ้ามีการระบุ');
        }
    }

    return [
        'type' => $transformType,
        'payload' => $payload,
        'payload_json' => $payload === null ? null : json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ];
}

function applyMockMetricTransform(array $metric, array $mapping, ?PDO $pdo = null): array {
    $pdo ??= db();
    $transformType = (string) ($mapping['transform_type'] ?? 'percent_threshold');
    $payload = $mapping['transform_payload'] ?? null;
    if (is_string($payload) && trim($payload) !== '') {
        $decoded = json_decode($payload, true);
        $payload = is_array($decoded) ? $decoded : null;
    }

    if ($transformType === 'percent_threshold') {
        $percent = $metric['percent'] ?? $metric['value'] ?? null;
        if (!is_numeric($percent)) {
            throw new InvalidArgumentException('percent_threshold ต้องมีค่า percent หรือ value ที่เป็นตัวเลข');
        }
        $percentValue = (float) $percent;
        if ($percentValue < 0 || $percentValue > 100) {
            throw new InvalidArgumentException('percent ต้องอยู่ระหว่าง 0 ถึง 100');
        }
        return [
            'score' => readinessScoreFromPercent($percentValue, $pdo),
            'normalized_value' => $percentValue,
            'value_field' => 'percent',
        ];
    }

    if ($transformType === 'enum_map') {
        $status = strtolower(trim((string) ($metric['status'] ?? $metric['value'] ?? '')));
        if ($status === '') {
            throw new InvalidArgumentException('enum_map ต้องมีค่า status หรือ value');
        }
        $map = is_array($payload['map'] ?? null) ? $payload['map'] : [];
        if (!array_key_exists($status, $map)) {
            throw new InvalidArgumentException('status ไม่อยู่ใน enum mapping: ' . $status);
        }
        return [
            'score' => (int) $map[$status],
            'normalized_value' => $status,
            'value_field' => 'status',
        ];
    }

    if ($transformType === 'direct_score') {
        $score = $metric['score'] ?? $metric['value'] ?? null;
        if (!is_numeric($score)) {
            throw new InvalidArgumentException('direct_score ต้องมีค่า score หรือ value ที่เป็นตัวเลข');
        }
        $scoreValue = (int) $score;
        if ($scoreValue < 0 || $scoreValue > 2) {
            throw new InvalidArgumentException('direct score ต้องอยู่ระหว่าง 0 ถึง 2');
        }
        return [
            'score' => $scoreValue,
            'normalized_value' => $scoreValue,
            'value_field' => 'score',
        ];
    }

    throw new InvalidArgumentException('transform type ไม่รองรับ: ' . $transformType);
}

function mockPercentMetricKeyIsValid(string $metricKey): bool {
    return (bool) preg_match('/^[A-Za-z0-9._-]{3,190}$/', $metricKey);
}

function mockPercentTargetExists(string $rowId, string $colId, int $itemIndex, ?array $items = null): bool {
    $items ??= getItemsDefinition();
    return isset($items[$rowId][$colId]) && array_key_exists($itemIndex, $items[$rowId][$colId]);
}

function getMockPercentMetricMappings(bool $includeInactive = false, ?PDO $pdo = null): array {
    $pdo ??= db();
    if (!tableExists($pdo, 'mock_percent_metric_mappings')) {
        return [];
    }

    $sql = 'SELECT * FROM mock_percent_metric_mappings';
    if (!$includeInactive) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY metric_key';
    return $pdo->query($sql)->fetchAll();
}

function mockPercentTargetCollisionWarnings(?PDO $pdo = null): array {
    $groups = [];
    foreach (effectiveMockPercentMetricMap($pdo) as $mapping) {
        $targetKey = (string) ($mapping['row_id'] ?? '') . '|' . (string) ($mapping['col_id'] ?? '') . '|' . (int) ($mapping['item_index'] ?? -1);
        $groups[$targetKey][] = (string) ($mapping['metric_key'] ?? '');
    }

    $warnings = [];
    foreach ($groups as $targetKey => $metricKeys) {
        $metricKeys = array_values(array_filter(array_unique($metricKeys)));
        if (count($metricKeys) < 2) {
            continue;
        }
        [$rowId, $colId, $itemIndex] = array_pad(explode('|', $targetKey, 3), 3, '');
        $warnings[] = [
            'target_key' => $targetKey,
            'row_id' => $rowId,
            'col_id' => $colId,
            'item_index' => (int) $itemIndex,
            'metric_keys' => $metricKeys,
        ];
    }
    return $warnings;
}

function exportMockPercentMetricMappings(string $format = 'json', ?PDO $pdo = null): string {
    $rows = getMockPercentMetricMappings(true, $pdo);
    $format = strtolower(trim($format));
    if ($format === 'json') {
        return (string) json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    if ($format !== 'csv') {
        throw new InvalidArgumentException('รองรับเฉพาะ export format: json, csv');
    }

    $handle = fopen('php://temp', 'r+');
    if ($handle === false) {
        throw new RuntimeException('ไม่สามารถสร้าง export CSV ได้');
    }
    fputcsv($handle, ['metric_key', 'row_id', 'col_id', 'item_index', 'transform_type', 'transform_payload', 'is_active']);
    foreach ($rows as $row) {
        fputcsv($handle, [
            (string) ($row['metric_key'] ?? ''),
            (string) ($row['row_id'] ?? ''),
            (string) ($row['col_id'] ?? ''),
            (int) ($row['item_index'] ?? 0),
            (string) ($row['transform_type'] ?? 'percent_threshold'),
            (string) ($row['transform_payload'] ?? ''),
            (int) ($row['is_active'] ?? 0),
        ]);
    }
    rewind($handle);
    $csv = stream_get_contents($handle);
    fclose($handle);
    return (string) $csv;
}

function importMockPercentMetricMappings(string $payload, string $format = 'json', bool $replaceExisting = false): array {
    $format = strtolower(trim($format));
    if (!in_array($format, ['json', 'csv'], true)) {
        throw new InvalidArgumentException('รองรับเฉพาะ import format: json, csv');
    }

    if ($format === 'json') {
        $rows = json_decode($payload, true);
        if (!is_array($rows)) {
            throw new InvalidArgumentException('JSON import ไม่ถูกต้อง');
        }
    } else {
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            throw new RuntimeException('ไม่สามารถอ่าน CSV import ได้');
        }
        fwrite($handle, $payload);
        rewind($handle);
        $header = fgetcsv($handle);
        $rows = [];
        while (($line = fgetcsv($handle)) !== false) {
            if (!$header) {
                continue;
            }
            $rows[] = array_combine($header, $line);
        }
        fclose($handle);
    }

    $pdo = db();
    $summary = ['created' => 0, 'updated' => 0];

    if ($replaceExisting) {
        $pdo->exec('DELETE FROM mock_percent_metric_mappings');
    }

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $metricKey = trim((string) ($row['metric_key'] ?? ''));
        $rowId = trim((string) ($row['row_id'] ?? ''));
        $colId = trim((string) ($row['col_id'] ?? ''));
        $itemIndex = isset($row['item_index']) ? (int) $row['item_index'] : -1;
        $isActive = isset($row['is_active']) ? (int) $row['is_active'] === 1 : true;
        $transformType = trim((string) ($row['transform_type'] ?? 'percent_threshold'));
        $transformPayload = isset($row['transform_payload']) ? (string) $row['transform_payload'] : null;

        $normalized = normalizeMockPercentTransform($transformType, $transformPayload);

        $stmt = $pdo->prepare('SELECT id FROM mock_percent_metric_mappings WHERE metric_key = ? LIMIT 1');
        $stmt->execute([$metricKey]);
        $existingId = $stmt->fetchColumn();
        if ($existingId === false) {
            createMockPercentMetricMapping($metricKey, $rowId, $colId, $itemIndex, $isActive, $normalized['type'], $normalized['payload_json']);
            $summary['created']++;
        } else {
            updateMockPercentMetricMapping((int) $existingId, $metricKey, $rowId, $colId, $itemIndex, $isActive, $normalized['type'], $normalized['payload_json']);
            $summary['updated']++;
        }
    }

    return $summary;
}

function effectiveMockPercentMetricMap(?PDO $pdo = null): array {
    $pdo ??= db();
    $map = mockPercentMetricMapFromConfig();

    foreach (getMockPercentMetricMappings(true, $pdo) as $row) {
        $metricKey = (string) ($row['metric_key'] ?? '');
        if ($metricKey === '') {
            continue;
        }
        if ((int) ($row['is_active'] ?? 0) !== 1) {
            unset($map[$metricKey]);
            continue;
        }

        $map[$metricKey] = [
            'id' => (int) ($row['id'] ?? 0),
            'metric_key' => $metricKey,
            'row_id' => (string) ($row['row_id'] ?? ''),
            'col_id' => (string) ($row['col_id'] ?? ''),
            'item_index' => (int) ($row['item_index'] ?? 0),
            'transform_type' => (string) ($row['transform_type'] ?? 'percent_threshold'),
            'transform_payload' => (string) ($row['transform_payload'] ?? ''),
            'is_active' => 1,
            'source' => 'database',
        ];
    }

    ksort($map);
    return $map;
}

function createMockPercentMetricMapping(string $metricKey, string $rowId, string $colId, int $itemIndex, bool $isActive, string $transformType = 'percent_threshold', ?string $transformPayload = null): void {
    $metricKey = trim($metricKey);
    $rowId = trim($rowId);
    $colId = trim($colId);
    if (!mockPercentMetricKeyIsValid($metricKey)) {
        throw new InvalidArgumentException('metric key ต้องยาว 3-190 ตัวอักษร และใช้ได้เฉพาะ a-z, A-Z, 0-9, จุด, ขีดล่าง, ขีดกลาง');
    }
    if (!mockPercentTargetExists($rowId, $colId, $itemIndex)) {
        throw new InvalidArgumentException('target item ไม่ถูกต้อง');
    }

    $transform = normalizeMockPercentTransform($transformType, $transformPayload);

    $pdo = db();
    $stmt = $pdo->prepare('INSERT INTO mock_percent_metric_mappings (metric_key, row_id, col_id, item_index, transform_type, transform_payload, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $now = nowString();
    $stmt->execute([$metricKey, $rowId, $colId, $itemIndex, $transform['type'], $transform['payload_json'], $isActive ? 1 : 0, $now, $now]);

    $user = currentUser();
    writeAuditLog($pdo, $user['id'] ?? null, $user['name'] ?? null, 'CREATE_MOCK_PERCENT_MAPPING', 'mock_percent_metric_mapping', $metricKey, [
        'row_id' => $rowId,
        'col_id' => $colId,
        'item_index' => $itemIndex,
        'transform_type' => $transform['type'],
        'transform_payload' => $transform['payload'],
        'is_active' => $isActive,
    ]);
}

    function updateMockPercentMetricMapping(int $mappingId, string $metricKey, string $rowId, string $colId, int $itemIndex, bool $isActive, string $transformType = 'percent_threshold', ?string $transformPayload = null): void {
    $metricKey = trim($metricKey);
    $rowId = trim($rowId);
    $colId = trim($colId);
    if (!mockPercentMetricKeyIsValid($metricKey)) {
        throw new InvalidArgumentException('metric key ต้องยาว 3-190 ตัวอักษร และใช้ได้เฉพาะ a-z, A-Z, 0-9, จุด, ขีดล่าง, ขีดกลาง');
    }
    if (!mockPercentTargetExists($rowId, $colId, $itemIndex)) {
        throw new InvalidArgumentException('target item ไม่ถูกต้อง');
    }

    $transform = normalizeMockPercentTransform($transformType, $transformPayload);

    $pdo = db();
    $stmt = $pdo->prepare('UPDATE mock_percent_metric_mappings SET metric_key = ?, row_id = ?, col_id = ?, item_index = ?, transform_type = ?, transform_payload = ?, is_active = ?, updated_at = ? WHERE id = ?');
    $stmt->execute([$metricKey, $rowId, $colId, $itemIndex, $transform['type'], $transform['payload_json'], $isActive ? 1 : 0, nowString(), $mappingId]);

    $user = currentUser();
    writeAuditLog($pdo, $user['id'] ?? null, $user['name'] ?? null, 'UPDATE_MOCK_PERCENT_MAPPING', 'mock_percent_metric_mapping', (string) $mappingId, [
        'metric_key' => $metricKey,
        'row_id' => $rowId,
        'col_id' => $colId,
        'item_index' => $itemIndex,
        'transform_type' => $transform['type'],
        'transform_payload' => $transform['payload'],
        'is_active' => $isActive,
    ]);
}

function deleteMockPercentMetricMapping(int $mappingId): void {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT metric_key FROM mock_percent_metric_mappings WHERE id = ? LIMIT 1');
    $stmt->execute([$mappingId]);
    $mapping = $stmt->fetch();
    if (!$mapping) {
        throw new InvalidArgumentException('ไม่พบ mapping ที่ต้องการลบ');
    }

    $delete = $pdo->prepare('DELETE FROM mock_percent_metric_mappings WHERE id = ?');
    $delete->execute([$mappingId]);

    $user = currentUser();
    writeAuditLog($pdo, $user['id'] ?? null, $user['name'] ?? null, 'DELETE_MOCK_PERCENT_MAPPING', 'mock_percent_metric_mapping', (string) $mappingId, [
        'metric_key' => (string) ($mapping['metric_key'] ?? ''),
    ]);
}

function statusLabel(float $pct): array {
    $thresholds = readinessStatusThresholds();
    if ($pct >= $thresholds['ready']) {
        return ['text' => 'พร้อม', 'bg' => themeColor('success_bg', '#EAF3DE'), 'color' => themeColor('success_text', '#27500A')];
    }
    if ($pct >= $thresholds['warning']) {
        return ['text' => 'ปานกลาง', 'bg' => themeColor('warning_bg', '#FAEEDA'), 'color' => themeColor('warning_text', '#633806')];
    }
    return ['text' => 'ต้องปรับปรุง', 'bg' => themeColor('danger_bg', '#FCEBEB'), 'color' => themeColor('danger_text', '#791F1F')];
}

function barColor(float $pct): string {
    $thresholds = readinessStatusThresholds();
    if ($pct >= $thresholds['ready']) {
        return themeColor('ready_bar', '#639922');
    }
    if ($pct >= $thresholds['warning']) {
        return themeColor('warning_bar', '#BA7517');
    }
    return themeColor('danger_bar', '#E24B4A');
}

function normalizeItem(string|array $item): array {
    if (is_array($item)) {
        return [
            'label' => (string) ($item['label'] ?? ''),
            'url' => trim((string) ($item['url'] ?? '')),
        ];
    }

    return [
        'label' => $item,
        'url' => '',
    ];
}

function readinessItemKey(array $item): string {
    return (string) ($item['row_id'] ?? '') . '|' . (string) ($item['col_id'] ?? '') . '|' . (int) ($item['item_index'] ?? 0);
}

function readinessConfigItems(): array {
    $items = [];
    foreach (ITEMS as $rowId => $rowDef) {
        foreach (['personnel', 'material', 'tactic'] as $colId) {
            foreach (($rowDef[$colId] ?? []) as $itemIndex => $item) {
                $itemData = normalizeItem($item);
                $items[] = [
                    'row_id' => $rowId,
                    'col_id' => $colId,
                    'item_index' => (int) $itemIndex,
                    'label' => $itemData['label'],
                    'url' => $itemData['url'],
                ];
            }
        }
    }
    return $items;
}

function readinessItemsMap(array $items): array {
    $map = [];
    foreach ($items as $item) {
        $map[readinessItemKey($item)] = [
            'id' => isset($item['id']) ? (int) $item['id'] : null,
            'row_id' => (string) $item['row_id'],
            'col_id' => (string) $item['col_id'],
            'item_index' => (int) $item['item_index'],
            'label' => (string) ($item['label'] ?? ''),
            'url' => (string) ($item['url'] ?? ''),
            'is_active' => (int) ($item['is_active'] ?? 1),
        ];
    }
    return $map;
}

function readinessEntriesUsageByKey(?PDO $pdo = null): array {
    $pdo ??= db();
    $stmt = $pdo->query(
        'SELECT row_id, col_id, item_index, COUNT(*) AS entry_count, COUNT(DISTINCT unit_id) AS unit_count
         FROM readiness_entries
         GROUP BY row_id, col_id, item_index'
    );

    $usage = [];
    foreach ($stmt->fetchAll() as $row) {
        $usage[readinessItemKey($row)] = [
            'entry_count' => (int) ($row['entry_count'] ?? 0),
            'unit_count' => (int) ($row['unit_count'] ?? 0),
        ];
    }

    return $usage;
}

function buildReadinessItemsDiff(?PDO $pdo = null): array {
    $pdo ??= db();
    $dbItems = getReadinessItems(true);
    $configItems = readinessConfigItems();
    $dbMap = readinessItemsMap($dbItems);
    $configMap = readinessItemsMap($configItems);
    $usageMap = readinessEntriesUsageByKey($pdo);

    $toAdd = [];
    $toUpdate = [];
    $toDeactivate = [];

    foreach ($configMap as $key => $configItem) {
        if (!isset($dbMap[$key])) {
            $toAdd[] = [
                'key' => $key,
                'config' => $configItem,
                'usage' => $usageMap[$key] ?? ['entry_count' => 0, 'unit_count' => 0],
            ];
            continue;
        }

        $dbItem = $dbMap[$key];
        if ($dbItem['label'] !== $configItem['label'] || $dbItem['url'] !== $configItem['url'] || (int) $dbItem['is_active'] !== 1) {
            $toUpdate[] = [
                'key' => $key,
                'db' => $dbItem,
                'config' => $configItem,
                'usage' => $usageMap[$key] ?? ['entry_count' => 0, 'unit_count' => 0],
            ];
        }
    }

    foreach ($dbMap as $key => $dbItem) {
        if (!isset($configMap[$key]) && (int) $dbItem['is_active'] === 1) {
            $toDeactivate[] = [
                'key' => $key,
                'db' => $dbItem,
                'usage' => $usageMap[$key] ?? ['entry_count' => 0, 'unit_count' => 0],
            ];
        }
    }

    $impactedKeys = [];
    foreach (array_merge($toUpdate, $toDeactivate) as $change) {
        if ((int) (($change['usage']['entry_count'] ?? 0)) > 0) {
            $impactedKeys[] = (string) $change['key'];
        }
    }

    return [
        'db_items' => $dbItems,
        'config_items' => $configItems,
        'usage_by_key' => $usageMap,
        'diff' => [
            'to_add_count' => count($toAdd),
            'to_update_count' => count($toUpdate),
            'to_deactivate_count' => count($toDeactivate),
            'to_add' => $toAdd,
            'to_update' => $toUpdate,
            'to_deactivate' => $toDeactivate,
        ],
        'impact' => [
            'has_changes' => count($toAdd) > 0 || count($toUpdate) > 0 || count($toDeactivate) > 0,
            'has_usage_risk' => count($impactedKeys) > 0,
            'usage_risk_count' => count($impactedKeys),
            'usage_risk_keys' => array_values($impactedKeys),
        ],
        'summary' => [
            'db_item_count' => count($dbItems),
            'config_item_count' => count($configItems),
        ],
    ];
}

function ensureDirectory(string $path): void {
    if (is_dir($path)) {
        return;
    }
    if (!mkdir($path, 0775, true) && !is_dir($path)) {
        throw new RuntimeException('ไม่สามารถสร้างโฟลเดอร์ได้: ' . $path);
    }
}

function readinessSyncBackupDirectory(): string {
    return READINESS_SYNC_BACKUP_DIR;
}

function tableExists(PDO $pdo, string $table): bool {
    if (DB_DRIVER === 'mysql') {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?');
        $stmt->execute([DB_NAME, $table]);
        return (int) $stmt->fetchColumn() > 0;
    }

    $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type = 'table' AND name = ?");
    $stmt->execute([$table]);
    return (bool) $stmt->fetchColumn();
}

function createAppSettingsTable(PDO $pdo): void {
    if (DB_DRIVER === 'mysql') {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS app_settings (
                setting_key VARCHAR(100) PRIMARY KEY,
                setting_value LONGTEXT NULL,
                updated_by_user_id INT NULL,
                updated_at DATETIME NOT NULL,
                CONSTRAINT fk_app_settings_user FOREIGN KEY (updated_by_user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        return;
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS app_settings (
            setting_key TEXT PRIMARY KEY,
            setting_value TEXT NULL,
            updated_by_user_id INTEGER NULL,
            updated_at TEXT NOT NULL,
            FOREIGN KEY (updated_by_user_id) REFERENCES users(id) ON DELETE SET NULL
        )'
    );
}

function resetReadinessSettingsCache(): void {
    $GLOBALS['readiness_settings_cache'] = null;
    $GLOBALS['readiness_visible_row_ids_cache'] = null;
    $GLOBALS['readiness_calculated_row_ids_cache'] = null;
    $GLOBALS['readiness_effective_row_weights_cache'] = null;
}

function getAppSettings(?PDO $pdo = null): array {
    $pdo ??= db();
    if (array_key_exists('readiness_settings_cache', $GLOBALS) && is_array($GLOBALS['readiness_settings_cache'])) {
        return $GLOBALS['readiness_settings_cache'];
    }

    if (!tableExists($pdo, 'app_settings')) {
        $GLOBALS['readiness_settings_cache'] = [];
        return [];
    }

    $stmt = $pdo->query('SELECT setting_key, setting_value FROM app_settings');
    $settings = [];
    foreach ($stmt->fetchAll() as $row) {
        $settings[(string) $row['setting_key']] = $row['setting_value'] === null ? null : (string) $row['setting_value'];
    }

    $GLOBALS['readiness_settings_cache'] = $settings;
    return $settings;
}

function getAppSetting(string $key, ?string $default = null, ?PDO $pdo = null): ?string {
    $settings = getAppSettings($pdo);
    return array_key_exists($key, $settings) ? $settings[$key] : $default;
}

function setAppSetting(string $key, ?string $value, ?int $userId = null, ?PDO $pdo = null): void {
    $pdo ??= db();
    $now = nowString();

    if (DB_DRIVER === 'mysql') {
        $stmt = $pdo->prepare(
            'INSERT INTO app_settings (setting_key, setting_value, updated_by_user_id, updated_at)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by_user_id = VALUES(updated_by_user_id), updated_at = VALUES(updated_at)'
        );
        $stmt->execute([$key, $value, $userId, $now]);
    } else {
        $stmt = $pdo->prepare('INSERT OR REPLACE INTO app_settings (setting_key, setting_value, updated_by_user_id, updated_at) VALUES (?, ?, ?, ?)');
        $stmt->execute([$key, $value, $userId, $now]);
    }

    resetReadinessSettingsCache();
}

function deleteAppSetting(string $key, ?PDO $pdo = null): void {
    $pdo ??= db();
    if (!tableExists($pdo, 'app_settings')) {
        return;
    }
    $stmt = $pdo->prepare('DELETE FROM app_settings WHERE setting_key = ?');
    $stmt->execute([$key]);
    resetReadinessSettingsCache();
}

function readinessSettingsSourceLabel(string $key, ?PDO $pdo = null): string {
    $value = getAppSetting($key, null, $pdo);
    return $value === null ? 'env/default' : 'database';
}

function getReadinessDisplaySettings(?PDO $pdo = null): array {
    $pdo ??= db();
    $thresholds = readinessStatusThresholds($pdo);
    return [
        'visible_rows_raw' => getAppSetting('readiness_visible_rows', READINESS_VISIBLE_ROWS, $pdo) ?? '',
        'calculated_rows_raw' => getAppSetting('readiness_calculated_rows', READINESS_CALCULATED_ROWS, $pdo) ?? '',
        'visible_rows' => readinessVisibleRowIds(),
        'calculated_rows' => readinessCalculatedRowIds(),
        'visible_source' => readinessSettingsSourceLabel('readiness_visible_rows', $pdo),
        'calculated_source' => readinessSettingsSourceLabel('readiness_calculated_rows', $pdo),
        'ready_threshold' => $thresholds['ready'],
        'warning_threshold' => $thresholds['warning'],
        'ready_threshold_source' => $thresholds['ready_source'],
        'warning_threshold_source' => $thresholds['warning_source'],
    ];
}

function updateReadinessDisplaySettings(array $visibleRows, array $calculatedRows, float $readyThreshold, float $warningThreshold, ?array $actor = null, ?PDO $pdo = null): void {
    $pdo ??= db();
    $actor ??= currentUser();
    $allowedRowIds = readinessAllRowIds();
    $allowedLookup = array_fill_keys($allowedRowIds, true);

    $visible = [];
    foreach ($visibleRows as $rowId) {
        if (isset($allowedLookup[$rowId]) && !in_array($rowId, $visible, true)) {
            $visible[] = $rowId;
        }
    }
    if (!$visible) {
        throw new InvalidArgumentException('ต้องเลือกมิติที่ต้องการแสดงอย่างน้อย 1 มิติ');
    }

    $calculated = [];
    foreach ($calculatedRows as $rowId) {
        if (in_array($rowId, $visible, true) && !in_array($rowId, $calculated, true)) {
            $calculated[] = $rowId;
        }
    }

    if ($readyThreshold < 0 || $readyThreshold > 100) {
        throw new InvalidArgumentException('เกณฑ์สถานะพร้อมต้องอยู่ระหว่าง 0 ถึง 100');
    }
    if ($warningThreshold < 0 || $warningThreshold > 100) {
        throw new InvalidArgumentException('เกณฑ์สถานะปานกลางต้องอยู่ระหว่าง 0 ถึง 100');
    }
    if ($warningThreshold >= $readyThreshold) {
        throw new InvalidArgumentException('เกณฑ์สถานะปานกลางต้องน้อยกว่าเกณฑ์สถานะพร้อม');
    }

    $userId = isset($actor['id']) ? (int) $actor['id'] : null;
    setAppSetting('readiness_visible_rows', implode(',', $visible), $userId, $pdo);
    setAppSetting('readiness_calculated_rows', implode(',', $calculated), $userId, $pdo);
    setAppSetting('readiness_ready_threshold', (string) $readyThreshold, $userId, $pdo);
    setAppSetting('readiness_warning_threshold', (string) $warningThreshold, $userId, $pdo);

    writeAuditLog($pdo, $actor['id'] ?? null, $actor['name'] ?? ($actor['username'] ?? null), 'UPDATE_READINESS_DISPLAY_SETTINGS', 'app_settings', 'readiness_display', [
        'visible_rows' => $visible,
        'calculated_rows' => $calculated,
        'ready_threshold' => $readyThreshold,
        'warning_threshold' => $warningThreshold,
    ]);
}

function clearReadinessDisplaySettingsOverride(?array $actor = null, ?PDO $pdo = null): void {
    $pdo ??= db();
    $actor ??= currentUser();
    deleteAppSetting('readiness_visible_rows', $pdo);
    deleteAppSetting('readiness_calculated_rows', $pdo);
    deleteAppSetting('readiness_ready_threshold', $pdo);
    deleteAppSetting('readiness_warning_threshold', $pdo);
    $thresholds = readinessStatusThresholds($pdo);
    writeAuditLog($pdo, $actor['id'] ?? null, $actor['name'] ?? ($actor['username'] ?? null), 'RESET_READINESS_DISPLAY_SETTINGS', 'app_settings', 'readiness_display', [
        'visible_rows' => readinessVisibleRowIds(),
        'calculated_rows' => readinessCalculatedRowIds(),
        'ready_threshold' => $thresholds['ready'],
        'warning_threshold' => $thresholds['warning'],
    ]);
}

function readinessRelativePath(string $path): string {
    $root = rtrim(__DIR__, '/');
    if (str_starts_with($path, $root . '/')) {
        return substr($path, strlen($root) + 1);
    }
    return $path;
}

function isAbsolutePath(string $path): bool {
    return $path !== '' && ($path[0] === '/' || preg_match('/^[A-Za-z]:\\\\/', $path) === 1);
}

function resolveReadinessSyncBackupPath(string $path): string {
    $path = trim($path);
    if ($path === '') {
        throw new InvalidArgumentException('ต้องระบุไฟล์ backup');
    }
    if (isAbsolutePath($path)) {
        return $path;
    }
    return __DIR__ . '/' . ltrim($path, '/');
}

function exportReadinessSyncBackup(?PDO $pdo = null, ?array $diffData = null, ?array $actor = null, string $source = 'system'): array {
    $pdo ??= db();
    $diffData ??= buildReadinessItemsDiff($pdo);
    $actor ??= currentUser();

    $backupDir = readinessSyncBackupDirectory();
    ensureDirectory($backupDir);

    $createdAt = nowString();
    $token = bin2hex(random_bytes(4));
    $fileName = 'readiness-sync-' . date('Ymd-His') . '-' . $token . '.json';
    $filePath = rtrim($backupDir, '/') . '/' . $fileName;

    $payload = [
        'meta' => [
            'created_at' => $createdAt,
            'app_env' => APP_ENV,
            'db_driver' => DB_DRIVER,
            'source' => $source,
            'actor' => [
                'id' => $actor['id'] ?? null,
                'username' => $actor['username'] ?? null,
                'name' => $actor['name'] ?? null,
                'role' => $actor['role'] ?? null,
            ],
        ],
        'summary' => $diffData['summary'] ?? [],
        'diff' => $diffData['diff'] ?? [],
        'impact' => $diffData['impact'] ?? [],
        'readiness_items' => getReadinessItems(true),
        'readiness_entries' => $pdo->query('SELECT * FROM readiness_entries ORDER BY unit_id, row_id, col_id, item_index, id')->fetchAll(),
    ];

    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException('สร้างไฟล์ backup ไม่สำเร็จ');
    }
    if (file_put_contents($filePath, $json) === false) {
        throw new RuntimeException('บันทึกไฟล์ backup ไม่สำเร็จ');
    }

    return [
        'file_name' => $fileName,
        'file_path' => $filePath,
        'file_path_relative' => readinessRelativePath($filePath),
        'created_at' => $createdAt,
        'item_count' => count($payload['readiness_items']),
        'entry_count' => count($payload['readiness_entries']),
    ];
}

function assertReadinessSyncAllowed(bool $allowProduction = false): void {
    if (appIsProduction() && !$allowProduction) {
        throw new RuntimeException('Refusing to run readiness sync against APP_ENV=production. ยืนยันอีกครั้งก่อนดำเนินการจริง');
    }
}

function applyReadinessItemsSyncWithBackup(?PDO $pdo = null, ?array $actor = null, bool $allowProduction = false, string $source = 'system'): array {
    $pdo ??= db();
    $actor ??= currentUser();
    assertReadinessSyncAllowed($allowProduction);

    $diffData = buildReadinessItemsDiff($pdo);
    $backup = exportReadinessSyncBackup($pdo, $diffData, $actor, $source);

    $result = [
        'backup' => $backup,
        'diff' => $diffData['diff'],
        'impact' => $diffData['impact'],
        'summary' => $diffData['summary'],
        'applied' => false,
        'no_changes' => !($diffData['impact']['has_changes'] ?? false),
    ];

    if ($result['no_changes']) {
        writeAuditLog($pdo, $actor['id'] ?? null, $actor['name'] ?? ($actor['username'] ?? 'system'), 'READINESS_SYNC_SKIPPED', 'readiness_sync', 'config', [
            'source' => $source,
            'backup_file' => $backup['file_path_relative'],
            'diff' => $diffData['diff'],
            'impact' => $diffData['impact'],
        ]);
        return $result;
    }

    syncItemsDefinition($pdo, true);
    $result['applied'] = true;
    $result['summary']['db_item_count_after'] = count(getReadinessItems(true));

    writeAuditLog($pdo, $actor['id'] ?? null, $actor['name'] ?? ($actor['username'] ?? 'system'), 'READINESS_SYNC_APPLY', 'readiness_sync', 'config', [
        'source' => $source,
        'backup_file' => $backup['file_path_relative'],
        'diff' => $diffData['diff'],
        'impact' => $diffData['impact'],
    ]);

    return $result;
}

function restoreReadinessSyncBackup(string $backupPath, ?PDO $pdo = null, ?array $actor = null, bool $allowProduction = false, string $source = 'system'): array {
    $pdo ??= db();
    $actor ??= currentUser();
    assertReadinessSyncAllowed($allowProduction);

    $resolvedPath = resolveReadinessSyncBackupPath($backupPath);
    if (!is_file($resolvedPath) || !is_readable($resolvedPath)) {
        throw new InvalidArgumentException('ไม่พบไฟล์ backup ที่ระบุ');
    }

    $raw = file_get_contents($resolvedPath);
    $payload = json_decode((string) $raw, true);
    if (!is_array($payload)) {
        throw new RuntimeException('ไฟล์ backup ไม่ถูกต้อง');
    }

    $items = $payload['readiness_items'] ?? null;
    $entries = $payload['readiness_entries'] ?? null;
    if (!is_array($items) || !is_array($entries)) {
        throw new RuntimeException('ไฟล์ backup ไม่มีข้อมูลที่ใช้ restore');
    }

    $pdo->beginTransaction();
    try {
        $pdo->exec('DELETE FROM readiness_entries');
        $pdo->exec('DELETE FROM readiness_items');

        $insertItem = $pdo->prepare('INSERT INTO readiness_items (id, row_id, col_id, item_index, label, url, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $item) {
            $insertItem->execute([
                (int) ($item['id'] ?? 0),
                (string) ($item['row_id'] ?? ''),
                (string) ($item['col_id'] ?? ''),
                (int) ($item['item_index'] ?? 0),
                (string) ($item['label'] ?? ''),
                (string) ($item['url'] ?? ''),
                (int) ($item['is_active'] ?? 1),
                (string) ($item['created_at'] ?? nowString()),
                (string) ($item['updated_at'] ?? nowString()),
            ]);
        }

        $insertEntry = $pdo->prepare('INSERT INTO readiness_entries (id, unit_id, row_id, col_id, item_index, value, source, updated_by_user_id, updated_by_name, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($entries as $entry) {
            $insertEntry->execute([
                (int) ($entry['id'] ?? 0),
                (int) ($entry['unit_id'] ?? 0),
                (string) ($entry['row_id'] ?? ''),
                (string) ($entry['col_id'] ?? ''),
                (int) ($entry['item_index'] ?? 0),
                (int) ($entry['value'] ?? 0),
                (string) ($entry['source'] ?? 'manual'),
                isset($entry['updated_by_user_id']) ? (int) $entry['updated_by_user_id'] : null,
                (string) ($entry['updated_by_name'] ?? ''),
                (string) ($entry['created_at'] ?? nowString()),
                (string) ($entry['updated_at'] ?? nowString()),
            ]);
        }

        writeAuditLog($pdo, $actor['id'] ?? null, $actor['name'] ?? ($actor['username'] ?? 'system'), 'READINESS_SYNC_RESTORE', 'readiness_sync', readinessRelativePath($resolvedPath), [
            'source' => $source,
            'backup_file' => readinessRelativePath($resolvedPath),
            'item_count' => count($items),
            'entry_count' => count($entries),
        ]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    return [
        'restored' => true,
        'backup_file' => readinessRelativePath($resolvedPath),
        'item_count' => count($items),
        'entry_count' => count($entries),
    ];
}

function listReadinessSyncBackups(int $limit = 20): array {
    $backupDir = readinessSyncBackupDirectory();
    if (!is_dir($backupDir)) {
        return [];
    }

    $files = glob(rtrim($backupDir, '/') . '/*.json') ?: [];
    rsort($files, SORT_STRING);
    $files = array_slice($files, 0, max(1, $limit));

    $rows = [];
    foreach ($files as $filePath) {
        $rows[] = [
            'file_name' => basename($filePath),
            'file_path' => $filePath,
            'file_path_relative' => readinessRelativePath($filePath),
            'size_bytes' => (int) (filesize($filePath) ?: 0),
            'modified_at' => date('Y-m-d H:i:s', (int) filemtime($filePath)),
        ];
    }

    return $rows;
}

function renderItemName(string|array $item, string $className = ''): string {
    $itemData = normalizeItem($item);
    $label = htmlspecialchars($itemData['label'], ENT_QUOTES, 'UTF-8');
    $classAttr = $className !== '' ? ' class="' . htmlspecialchars($className, ENT_QUOTES, 'UTF-8') . '"' : '';

    if ($itemData['url'] === '') {
        return $className !== '' ? '<span' . $classAttr . '>' . $label . '</span>' : $label;
    }

    return '<a' . $classAttr . ' href="' . htmlspecialchars($itemData['url'], ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer">' . $label . '</a>';
}

function ensureSessionStarted(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        configurePhpSecurity();
        session_name(SESSION_COOKIE_NAME);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => isHttpsRequest(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
        if (empty($_SESSION['created_at'])) {
            $_SESSION['created_at'] = time();
        }
        $_SESSION['last_activity_at'] = $_SESSION['last_activity_at'] ?? time();
    }

    enforceSessionTimeouts();
}

function nowString(): string {
    return date('Y-m-d H:i:s');
}

function enforceSessionTimeouts(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    $now = time();
    $createdAt = (int) ($_SESSION['created_at'] ?? $now);
    $lastActivityAt = (int) ($_SESSION['last_activity_at'] ?? $now);

    if (($now - $createdAt) > SESSION_ABSOLUTE_TIMEOUT || ($now - $lastActivityAt) > SESSION_IDLE_TIMEOUT) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION = [];
        return;
    }

    $_SESSION['last_activity_at'] = $now;
}

function csrfToken(): string {
    ensureSessionStarted();
    $token = (string) ($_SESSION['csrf_token'] ?? '');
    $expiresAt = (int) ($_SESSION['csrf_token_expires_at'] ?? 0);
    if ($token === '' || $expiresAt < time()) {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_expires_at'] = time() + CSRF_TOKEN_TTL;
    }
    return $token;
}

function csrfInput(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

function verifyCsrfRequest(): void {
    ensureSessionStarted();
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }

    $token = (string) ($_POST['csrf_token'] ?? '');
    $storedToken = (string) ($_SESSION['csrf_token'] ?? '');
    $expiresAt = (int) ($_SESSION['csrf_token_expires_at'] ?? 0);
    if ($token === '' || $storedToken === '' || $expiresAt < time() || !hash_equals($storedToken, $token)) {
        http_response_code(419);
        exit('CSRF token ไม่ถูกต้องหรือหมดอายุ');
    }
}

function sqlLimitPlaceholder(int $limit): int {
    return max(1, $limit);
}

function db(): PDO {
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        if (DB_DRIVER === 'mysql') {
            $dsn = 'mysql:charset=utf8mb4;dbname=' . DB_NAME;
            if (DB_SOCKET !== '') {
                $dsn .= ';unix_socket=' . DB_SOCKET;
            } else {
                $dsn .= ';host=' . DB_HOST . ';port=' . DB_PORT;
            }
            $options[PDO::ATTR_TIMEOUT] = DB_CONNECT_TIMEOUT;
            if (DB_SSL_CA !== '' && defined('PDO::MYSQL_ATTR_SSL_CA')) {
                $options[PDO::MYSQL_ATTR_SSL_CA] = DB_SSL_CA;
            }
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } else {
            $pdo = new PDO('sqlite:' . DB_PATH, null, null, $options);
            $pdo->exec('PRAGMA foreign_keys = ON');
        }
    } catch (Throwable $e) {
        http_response_code(500);
        exit('เชื่อมต่อฐานข้อมูลไม่สำเร็จ: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
    }

    bootstrapDatabase($pdo);
    return $pdo;
}

function bootstrapDatabase(PDO $pdo): void {
    static $bootstrapped = false;

    if ($bootstrapped) {
        return;
    }

    foreach (databaseSchemaStatements() as $statement) {
        $pdo->exec($statement);
    }

    runDatabaseMigrations($pdo);

    bootstrapUnits($pdo);
    syncItemsDefinition($pdo, SYNC_ITEMS_FROM_CONFIG);
    bootstrapUsers($pdo);
    bootstrapApiClients($pdo);
    bootstrapMockPercentMetricMappings($pdo);
    migrateLegacyData($pdo);
    migrateLegacyManualEntriesToOverrides($pdo);

    $bootstrapped = true;
}

function databaseSchemaStatements(): array {
    if (DB_DRIVER === 'mysql') {
        return [
            "CREATE TABLE IF NOT EXISTS units (
                id INT AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(100) NOT NULL UNIQUE,
                name VARCHAR(255) NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(100) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                display_name VARCHAR(255) NOT NULL,
                role VARCHAR(50) NOT NULL,
                unit_id INT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                CONSTRAINT fk_users_unit FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS readiness_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                row_id VARCHAR(50) NOT NULL,
                col_id VARCHAR(50) NOT NULL,
                item_index INT NOT NULL,
                label VARCHAR(255) NOT NULL,
                url TEXT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE KEY uniq_readiness_item (row_id, col_id, item_index)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS readiness_entries (
                id INT AUTO_INCREMENT PRIMARY KEY,
                unit_id INT NOT NULL,
                row_id VARCHAR(50) NOT NULL,
                col_id VARCHAR(50) NOT NULL,
                item_index INT NOT NULL,
                value TINYINT NOT NULL,
                source VARCHAR(50) NOT NULL,
                updated_by_user_id INT NULL,
                source_name VARCHAR(255) NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE KEY uniq_readiness_entry (unit_id, row_id, col_id, item_index, source),
                CONSTRAINT fk_entries_unit FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE,
                CONSTRAINT fk_entries_user FOREIGN KEY (updated_by_user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS readiness_overrides (
                id INT AUTO_INCREMENT PRIMARY KEY,
                unit_id INT NOT NULL,
                row_id VARCHAR(50) NOT NULL,
                col_id VARCHAR(50) NOT NULL,
                item_index INT NOT NULL,
                value TINYINT NOT NULL,
                updated_by_user_id INT NULL,
                source_name VARCHAR(255) NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE KEY uniq_readiness_override (unit_id, row_id, col_id, item_index),
                CONSTRAINT fk_overrides_unit FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE,
                CONSTRAINT fk_overrides_user FOREIGN KEY (updated_by_user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS api_clients (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                client_key VARCHAR(100) NOT NULL UNIQUE,
                token_hash VARCHAR(255) NOT NULL,
                unit_id INT NULL,
                allowed_ips TEXT NULL,
                rate_limit_per_minute INT NOT NULL DEFAULT 60,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                last_used_at DATETIME NULL,
                last_used_ip VARCHAR(64) NULL,
                CONSTRAINT fk_api_clients_unit FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS audit_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                actor_name VARCHAR(255) NULL,
                action VARCHAR(100) NOT NULL,
                entity_type VARCHAR(100) NOT NULL,
                entity_key VARCHAR(255) NOT NULL,
                details LONGTEXT NULL,
                ip_address VARCHAR(64) NULL,
                user_agent TEXT NULL,
                request_id VARCHAR(64) NULL,
                created_at DATETIME NOT NULL,
                CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS api_request_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                client_id INT NULL,
                client_key VARCHAR(100) NULL,
                request_ip VARCHAR(64) NULL,
                endpoint VARCHAR(255) NOT NULL,
                status_code INT NOT NULL,
                request_id VARCHAR(64) NOT NULL,
                created_at DATETIME NOT NULL,
                CONSTRAINT fk_api_request_client FOREIGN KEY (client_id) REFERENCES api_clients(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS mock_percent_metric_mappings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                metric_key VARCHAR(190) NOT NULL UNIQUE,
                row_id VARCHAR(50) NOT NULL,
                col_id VARCHAR(50) NOT NULL,
                item_index INT NOT NULL,
                transform_type VARCHAR(50) NOT NULL DEFAULT 'percent_threshold',
                transform_payload LONGTEXT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ];
    }

    return [
        'CREATE TABLE IF NOT EXISTS units (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            code TEXT NOT NULL UNIQUE,
            name TEXT NOT NULL,
            is_active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )',
        'CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            display_name TEXT NOT NULL,
            role TEXT NOT NULL,
            unit_id INTEGER NULL,
            is_active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL
        )',
        'CREATE TABLE IF NOT EXISTS readiness_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            row_id TEXT NOT NULL,
            col_id TEXT NOT NULL,
            item_index INTEGER NOT NULL,
            label TEXT NOT NULL,
            url TEXT NULL,
            is_active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            UNIQUE (row_id, col_id, item_index)
        )',
        'CREATE TABLE IF NOT EXISTS readiness_entries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            unit_id INTEGER NOT NULL,
            row_id TEXT NOT NULL,
            col_id TEXT NOT NULL,
            item_index INTEGER NOT NULL,
            value INTEGER NOT NULL,
            source TEXT NOT NULL,
            updated_by_user_id INTEGER NULL,
            source_name TEXT NULL,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            UNIQUE (unit_id, row_id, col_id, item_index, source),
            FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE,
            FOREIGN KEY (updated_by_user_id) REFERENCES users(id) ON DELETE SET NULL
        )',
        'CREATE TABLE IF NOT EXISTS readiness_overrides (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            unit_id INTEGER NOT NULL,
            row_id TEXT NOT NULL,
            col_id TEXT NOT NULL,
            item_index INTEGER NOT NULL,
            value INTEGER NOT NULL,
            updated_by_user_id INTEGER NULL,
            source_name TEXT NULL,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            UNIQUE (unit_id, row_id, col_id, item_index),
            FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE,
            FOREIGN KEY (updated_by_user_id) REFERENCES users(id) ON DELETE SET NULL
        )',
        'CREATE TABLE IF NOT EXISTS api_clients (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            client_key TEXT NULL,
            token_hash TEXT NOT NULL,
            unit_id INTEGER NULL,
            allowed_ips TEXT NULL,
            rate_limit_per_minute INTEGER NOT NULL DEFAULT 60,
            is_active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            last_used_at TEXT NULL,
            last_used_ip TEXT NULL,
            FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL
        )',
        'CREATE TABLE IF NOT EXISTS audit_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NULL,
            actor_name TEXT NULL,
            action TEXT NOT NULL,
            entity_type TEXT NOT NULL,
            entity_key TEXT NOT NULL,
            details TEXT NULL,
            ip_address TEXT NULL,
            user_agent TEXT NULL,
            request_id TEXT NULL,
            created_at TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )',
        'CREATE TABLE IF NOT EXISTS api_request_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id INTEGER NULL,
            client_key TEXT NULL,
            request_ip TEXT NULL,
            endpoint TEXT NOT NULL,
            status_code INTEGER NOT NULL,
            request_id TEXT NOT NULL,
            created_at TEXT NOT NULL,
            FOREIGN KEY (client_id) REFERENCES api_clients(id) ON DELETE SET NULL
        )',
        'CREATE TABLE IF NOT EXISTS mock_percent_metric_mappings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            metric_key TEXT NOT NULL UNIQUE,
            row_id TEXT NOT NULL,
            col_id TEXT NOT NULL,
            item_index INTEGER NOT NULL,
            transform_type TEXT NOT NULL DEFAULT \'percent_threshold\',
            transform_payload TEXT NULL,
            is_active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )',
    ];
}

function runDatabaseMigrations(PDO $pdo): void {
    createAppSettingsTable($pdo);
    ensureColumnExists($pdo, 'api_clients', 'client_key', 'VARCHAR(100) NULL', 'TEXT NULL');
    ensureColumnExists($pdo, 'api_clients', 'allowed_ips', 'TEXT NULL', 'TEXT NULL');
    ensureColumnExists($pdo, 'api_clients', 'rate_limit_per_minute', 'INT NOT NULL DEFAULT 60', 'INTEGER NOT NULL DEFAULT 60');
    ensureColumnExists($pdo, 'api_clients', 'last_used_ip', 'VARCHAR(64) NULL', 'TEXT NULL');
    ensureColumnExists($pdo, 'audit_log', 'ip_address', 'VARCHAR(64) NULL', 'TEXT NULL');
    ensureColumnExists($pdo, 'audit_log', 'user_agent', 'TEXT NULL', 'TEXT NULL');
    ensureColumnExists($pdo, 'audit_log', 'request_id', 'VARCHAR(64) NULL', 'TEXT NULL');
    ensureColumnExists($pdo, 'users', 'failed_login_attempts', 'INT NOT NULL DEFAULT 0', 'INTEGER NOT NULL DEFAULT 0');
    ensureColumnExists($pdo, 'users', 'last_failed_login_at', 'DATETIME NULL', 'TEXT NULL');
    ensureColumnExists($pdo, 'users', 'locked_until', 'DATETIME NULL', 'TEXT NULL');
    ensureColumnExists($pdo, 'users', 'password_changed_at', 'DATETIME NULL', 'TEXT NULL');
    ensureColumnExists($pdo, 'users', 'password_reset_token_hash', 'VARCHAR(255) NULL', 'TEXT NULL');
    ensureColumnExists($pdo, 'users', 'password_reset_token_expires_at', 'DATETIME NULL', 'TEXT NULL');
    ensureColumnExists($pdo, 'mock_percent_metric_mappings', 'transform_type', "VARCHAR(50) NOT NULL DEFAULT 'percent_threshold'", "TEXT NOT NULL DEFAULT 'percent_threshold'");
    ensureColumnExists($pdo, 'mock_percent_metric_mappings', 'transform_payload', 'LONGTEXT NULL', 'TEXT NULL');
    backfillApiClientKeys($pdo);
    backfillUserSecurityColumns($pdo);
}

function tableHasColumn(PDO $pdo, string $table, string $column): bool {
    if (DB_DRIVER === 'mysql') {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $stmt->execute([DB_NAME, $table, $column]);
        return (int) $stmt->fetchColumn() > 0;
    }

    $stmt = $pdo->query('PRAGMA table_info(' . $table . ')');
    foreach ($stmt->fetchAll() as $info) {
        if (($info['name'] ?? '') === $column) {
            return true;
        }
    }
    return false;
}

function ensureColumnExists(PDO $pdo, string $table, string $column, string $mysqlDefinition, string $sqliteDefinition): void {
    if (tableHasColumn($pdo, $table, $column)) {
        return;
    }

    $definition = DB_DRIVER === 'mysql' ? $mysqlDefinition : $sqliteDefinition;
    $pdo->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $column . ' ' . $definition);
}

function backfillApiClientKeys(PDO $pdo): void {
    if (!tableHasColumn($pdo, 'api_clients', 'client_key')) {
        return;
    }

    $stmt = $pdo->query('SELECT id, name, client_key, rate_limit_per_minute FROM api_clients');
    $rows = $stmt->fetchAll();
    $update = $pdo->prepare('UPDATE api_clients SET client_key = ?, rate_limit_per_minute = ?, updated_at = ? WHERE id = ?');
    foreach ($rows as $row) {
        $clientKey = trim((string) ($row['client_key'] ?? ''));
        $rateLimit = (int) ($row['rate_limit_per_minute'] ?? 0);
        if ($clientKey === '') {
            $clientKey = slugifyIdentifier((string) $row['name']) . '-' . (int) $row['id'];
        }
        if ($rateLimit <= 0) {
            $rateLimit = API_DEFAULT_RATE_LIMIT_PER_MINUTE;
        }
        $update->execute([$clientKey, $rateLimit, nowString(), $row['id']]);
    }
}

function backfillUserSecurityColumns(PDO $pdo): void {
    if (!tableHasColumn($pdo, 'users', 'password_changed_at')) {
        return;
    }

    $stmt = $pdo->query('SELECT id, password_changed_at, failed_login_attempts FROM users');
    $rows = $stmt->fetchAll();
    $update = $pdo->prepare('UPDATE users SET password_changed_at = COALESCE(password_changed_at, ?), failed_login_attempts = COALESCE(failed_login_attempts, 0), updated_at = ? WHERE id = ?');
    $now = nowString();
    foreach ($rows as $row) {
        $update->execute([$now, $now, $row['id']]);
    }
}

function bootstrapUnits(PDO $pdo): void {
    $stmt = $pdo->prepare('SELECT id FROM units WHERE code = ? LIMIT 1');
    $stmt->execute([DEFAULT_UNIT_CODE]);
    if ($stmt->fetch()) {
        return;
    }

    $now = nowString();
    $insert = $pdo->prepare('INSERT INTO units (code, name, is_active, created_at, updated_at) VALUES (?, ?, 1, ?, ?)');
    $insert->execute([DEFAULT_UNIT_CODE, DEFAULT_UNIT_NAME, $now, $now]);
}

function syncItemsDefinition(PDO $pdo, bool $force = false): void {
    $count = (int) $pdo->query('SELECT COUNT(*) FROM readiness_items')->fetchColumn();
    if (!$force && $count > 0) {
        return;
    }

    $pdo->beginTransaction();
    try {
        $pdo->exec('UPDATE readiness_items SET is_active = 0');
        $select = $pdo->prepare('SELECT id FROM readiness_items WHERE row_id = ? AND col_id = ? AND item_index = ? LIMIT 1');
        $insert = $pdo->prepare('INSERT INTO readiness_items (row_id, col_id, item_index, label, url, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 1, ?, ?)');
        $update = $pdo->prepare('UPDATE readiness_items SET label = ?, url = ?, is_active = 1, updated_at = ? WHERE id = ?');
        $now = nowString();

        foreach (ITEMS as $rowId => $rowDef) {
            foreach (['personnel', 'material', 'tactic'] as $colId) {
                foreach ($rowDef[$colId] as $itemIndex => $item) {
                    $itemData = normalizeItem($item);
                    $select->execute([$rowId, $colId, $itemIndex]);
                    $existing = $select->fetch();
                    if ($existing) {
                        $update->execute([$itemData['label'], $itemData['url'], $now, $existing['id']]);
                    } else {
                        $insert->execute([$rowId, $colId, $itemIndex, $itemData['label'], $itemData['url'], $now, $now]);
                    }
                }
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function bootstrapUsers(PDO $pdo): void {
    $count = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $defaultUnitId = getUnitIdByCode(DEFAULT_UNIT_CODE, $pdo);
    $now = nowString();
    $insert = $pdo->prepare('INSERT INTO users (username, password_hash, display_name, role, unit_id, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 1, ?, ?)');
    foreach (LEGACY_USERS as $username => $user) {
        $unitId = getUnitIdByCode((string) ($user['unit_code'] ?? DEFAULT_UNIT_CODE), $pdo) ?? $defaultUnitId;
        $insert->execute([
            $username,
            $user['hash'],
            $user['name'],
            $user['role'],
            $unitId,
            $now,
            $now,
        ]);
    }
}

function bootstrapApiClients(PDO $pdo): void {
    $count = (int) $pdo->query('SELECT COUNT(*) FROM api_clients')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $now = nowString();
    $insert = $pdo->prepare('INSERT INTO api_clients (name, client_key, token_hash, unit_id, allowed_ips, rate_limit_per_minute, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)');
    foreach (DEFAULT_API_CLIENTS as $client) {
        $unitId = isset($client['unit_code']) ? getUnitIdByCode((string) $client['unit_code'], $pdo) : null;
        $clientKey = trim((string) ($client['client_key'] ?? ''));
        if ($clientKey === '') {
            $clientKey = slugifyIdentifier((string) $client['name']);
        }
        $insert->execute([
            $client['name'],
            $clientKey,
            password_hash((string) ($client['secret'] ?? $client['token'] ?? ''), PASSWORD_DEFAULT),
            $unitId,
            (string) ($client['allowed_ips'] ?? ''),
            (int) ($client['rate_limit'] ?? API_DEFAULT_RATE_LIMIT_PER_MINUTE),
            $now,
            $now,
        ]);
    }
}

function bootstrapMockPercentMetricMappings(PDO $pdo): void {
    if (!tableExists($pdo, 'mock_percent_metric_mappings')) {
        return;
    }

    $existing = [];
    $stmt = $pdo->query('SELECT metric_key FROM mock_percent_metric_mappings');
    foreach ($stmt->fetchAll() as $row) {
        $existing[(string) ($row['metric_key'] ?? '')] = true;
    }

    if (!$existing && !MOCK_PERCENT_METRIC_MAP) {
        return;
    }

    $insert = $pdo->prepare('INSERT INTO mock_percent_metric_mappings (metric_key, row_id, col_id, item_index, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, 1, ?, ?)');
    $now = nowString();

    foreach (mockPercentMetricMapFromConfig() as $metricKey => $target) {
        if (isset($existing[$metricKey])) {
            continue;
        }
        $insert->execute([
            $metricKey,
            $target['row_id'],
            $target['col_id'],
            $target['item_index'],
            $now,
            $now,
        ]);
    }
}

function migrateLegacyData(PDO $pdo): void {
    $count = (int) $pdo->query('SELECT COUNT(*) FROM readiness_entries')->fetchColumn();
    if ($count > 0 || !file_exists(LEGACY_DATA_FILE)) {
        return;
    }

    $json = file_get_contents(LEGACY_DATA_FILE);
    $legacy = json_decode($json, true);
    if (!is_array($legacy) || !isset($legacy['rows']) || !is_array($legacy['rows'])) {
        return;
    }

    $unitId = getUnitIdByCode(DEFAULT_UNIT_CODE, $pdo);
    $userId = null;
    if (!empty($legacy['updated_by'])) {
        $user = findUserByDisplayName((string) $legacy['updated_by']);
        $userId = $user['id'] ?? null;
    }

    persistRows(
        $pdo,
        $unitId,
        sanitizeRows($legacy['rows']),
        'manual',
        $userId,
        (string) ($legacy['updated_by'] ?? 'legacy-import'),
        (string) ($legacy['updated_at'] ?? nowString())
    );
}

function migrateLegacyManualEntriesToOverrides(PDO $pdo): void {
    $count = (int) $pdo->query("SELECT COUNT(*) FROM readiness_entries WHERE source = 'manual'")->fetchColumn();
    if ($count === 0) {
        return;
    }

    $rows = $pdo->query(
        "SELECT unit_id, row_id, col_id, item_index, value, updated_by_user_id, source_name, created_at, updated_at
         FROM readiness_entries
         WHERE source = 'manual'
         ORDER BY updated_at ASC, id ASC"
    )->fetchAll();
    if (!$rows) {
        return;
    }

    $upsertSql = DB_DRIVER === 'mysql'
        ? 'INSERT INTO readiness_overrides (unit_id, row_id, col_id, item_index, value, updated_by_user_id, source_name, created_at, updated_at)
           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
           ON DUPLICATE KEY UPDATE value = VALUES(value), updated_by_user_id = VALUES(updated_by_user_id), source_name = VALUES(source_name), updated_at = VALUES(updated_at)'
        : 'INSERT INTO readiness_overrides (unit_id, row_id, col_id, item_index, value, updated_by_user_id, source_name, created_at, updated_at)
           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
           ON CONFLICT(unit_id, row_id, col_id, item_index) DO UPDATE SET
             value = excluded.value,
             updated_by_user_id = excluded.updated_by_user_id,
             source_name = excluded.source_name,
             updated_at = excluded.updated_at';

    $insert = $pdo->prepare($upsertSql);

    $pdo->beginTransaction();
    try {
        foreach ($rows as $row) {
            $insert->execute([
                $row['unit_id'],
                $row['row_id'],
                $row['col_id'],
                $row['item_index'],
                $row['value'],
                $row['updated_by_user_id'],
                $row['source_name'],
                $row['created_at'],
                $row['updated_at'],
            ]);
        }

        $pdo->exec("DELETE FROM readiness_entries WHERE source = 'manual'");
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function getItemsDefinition(): array {
    $stmt = db()->query('SELECT row_id, col_id, item_index, label, url FROM readiness_items WHERE is_active = 1 ORDER BY row_id, col_id, item_index');
    $rows = $stmt->fetchAll();
    if (!$rows) {
        return ITEMS;
    }

    $definitions = [];
    foreach (ITEMS as $rowId => $rowDef) {
        $definitions[$rowId] = [
            'label' => $rowDef['label'],
            'personnel' => [],
            'material' => [],
            'tactic' => [],
        ];
    }

    foreach ($rows as $row) {
        if (!isset($definitions[$row['row_id']])) {
            $definitions[$row['row_id']] = [
                'label' => $row['row_id'],
                'personnel' => [],
                'material' => [],
                'tactic' => [],
            ];
        }

        $definitions[$row['row_id']][$row['col_id']][(int) $row['item_index']] = [
            'label' => $row['label'],
            'url' => (string) ($row['url'] ?? ''),
        ];
    }

    foreach ($definitions as $rowId => $definition) {
        foreach (['personnel', 'material', 'tactic'] as $colId) {
            ksort($definitions[$rowId][$colId]);
            $definitions[$rowId][$colId] = array_values($definitions[$rowId][$colId]);
        }
    }

    return $definitions;
}

function readinessRowOptions(): array {
    $options = [];
    foreach (ITEMS as $rowId => $rowDef) {
        $options[$rowId] = (string) ($rowDef['label'] ?? $rowId);
    }
    return $options;
}

function readinessColumnOptions(): array {
    return [
        'personnel' => 'องค์บุคคล',
        'material' => 'องค์วัตถุ',
        'tactic' => 'องค์ยุทธวิธี',
    ];
}

function getReadinessItems(bool $includeInactive = false): array {
    $sql = 'SELECT * FROM readiness_items';
    if (!$includeInactive) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY row_id, col_id, item_index';
    return db()->query($sql)->fetchAll();
}

function getReadinessItemById(int $itemId): ?array {
    $stmt = db()->prepare('SELECT * FROM readiness_items WHERE id = ? LIMIT 1');
    $stmt->execute([$itemId]);
    return $stmt->fetch() ?: null;
}

function nextReadinessItemIndex(PDO $pdo, string $rowId, string $colId): int {
    $stmt = $pdo->prepare('SELECT COALESCE(MAX(item_index), -1) FROM readiness_items WHERE row_id = ? AND col_id = ?');
    $stmt->execute([$rowId, $colId]);
    return ((int) $stmt->fetchColumn()) + 1;
}

function assertReadinessRowAndColumn(string $rowId, string $colId): void {
    if (!isset(readinessRowOptions()[$rowId])) {
        throw new InvalidArgumentException('row_id ไม่ถูกต้อง');
    }
    if (!isset(readinessColumnOptions()[$colId])) {
        throw new InvalidArgumentException('col_id ไม่ถูกต้อง');
    }
}

function createReadinessItem(string $rowId, string $colId, string $label, string $url = ''): void {
    $rowId = trim($rowId);
    $colId = trim($colId);
    $label = trim($label);
    $url = trim($url);
    assertReadinessRowAndColumn($rowId, $colId);
    if ($label === '') {
        throw new InvalidArgumentException('กรอกชื่อรายการให้ครบถ้วน');
    }

    $pdo = db();
    $itemIndex = nextReadinessItemIndex($pdo, $rowId, $colId);
    $stmt = $pdo->prepare('INSERT INTO readiness_items (row_id, col_id, item_index, label, url, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 1, ?, ?)');
    $now = nowString();
    $stmt->execute([$rowId, $colId, $itemIndex, $label, $url, $now, $now]);

    $user = currentUser();
    writeAuditLog($pdo, $user['id'] ?? null, $user['name'] ?? null, 'CREATE_READINESS_ITEM', 'readiness_item', $rowId . '.' . $colId . '.' . $itemIndex, [
        'label' => $label,
        'url' => $url,
    ]);
}

function updateReadinessItem(int $itemId, string $label, string $url = ''): void {
    $label = trim($label);
    $url = trim($url);
    if ($label === '') {
        throw new InvalidArgumentException('กรอกชื่อรายการให้ครบถ้วน');
    }

    $item = getReadinessItemById($itemId);
    if (!$item) {
        throw new InvalidArgumentException('ไม่พบรายการ');
    }

    $stmt = db()->prepare('UPDATE readiness_items SET label = ?, url = ?, updated_at = ? WHERE id = ?');
    $stmt->execute([$label, $url, nowString(), $itemId]);

    $user = currentUser();
    writeAuditLog(db(), $user['id'] ?? null, $user['name'] ?? null, 'UPDATE_READINESS_ITEM', 'readiness_item', $item['row_id'] . '.' . $item['col_id'] . '.' . $item['item_index'], [
        'label' => $label,
        'url' => $url,
    ]);
}

function deleteReadinessItem(int $itemId): void {
    $pdo = db();
    $item = getReadinessItemById($itemId);
    if (!$item) {
        throw new InvalidArgumentException('ไม่พบรายการ');
    }

    $pdo->beginTransaction();
    try {
        $deleteEntries = $pdo->prepare('DELETE FROM readiness_entries WHERE row_id = ? AND col_id = ? AND item_index = ?');
        $deleteItem = $pdo->prepare('DELETE FROM readiness_items WHERE id = ?');
        $shiftItems = $pdo->prepare('UPDATE readiness_items SET item_index = item_index - 1, updated_at = ? WHERE row_id = ? AND col_id = ? AND item_index > ?');
        $shiftEntries = $pdo->prepare('UPDATE readiness_entries SET item_index = item_index - 1, updated_at = ? WHERE row_id = ? AND col_id = ? AND item_index > ?');

        $deleteEntries->execute([$item['row_id'], $item['col_id'], $item['item_index']]);
        $deleteItem->execute([$itemId]);
        $now = nowString();
        $shiftItems->execute([$now, $item['row_id'], $item['col_id'], $item['item_index']]);
        $shiftEntries->execute([$now, $item['row_id'], $item['col_id'], $item['item_index']]);

        $user = currentUser();
        writeAuditLog($pdo, $user['id'] ?? null, $user['name'] ?? null, 'DELETE_READINESS_ITEM', 'readiness_item', $item['row_id'] . '.' . $item['col_id'] . '.' . $item['item_index'], [
            'label' => $item['label'],
        ]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function moveReadinessItem(int $itemId, string $direction): void {
    $item = getReadinessItemById($itemId);
    if (!$item) {
        throw new InvalidArgumentException('ไม่พบรายการ');
    }

    if (!in_array($direction, ['up', 'down'], true)) {
        throw new InvalidArgumentException('direction ไม่ถูกต้อง');
    }

    $targetIndex = (int) $item['item_index'] + ($direction === 'up' ? -1 : 1);
    if ($targetIndex < 0) {
        return;
    }

    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM readiness_items WHERE row_id = ? AND col_id = ? AND item_index = ? LIMIT 1');
    $stmt->execute([$item['row_id'], $item['col_id'], $targetIndex]);
    $swapItem = $stmt->fetch();
    if (!$swapItem) {
        return;
    }

    $pdo->beginTransaction();
    try {
        $now = nowString();
        $shiftItems = $pdo->prepare('UPDATE readiness_items SET item_index = ? , updated_at = ? WHERE id = ?');
        $shiftEntries = $pdo->prepare('UPDATE readiness_entries SET item_index = ?, updated_at = ? WHERE row_id = ? AND col_id = ? AND item_index = ?');

        $tempIndex = -1;
        $shiftItems->execute([$tempIndex, $now, $item['id']]);
        $shiftEntries->execute([$tempIndex, $now, $item['row_id'], $item['col_id'], $item['item_index']]);

        $shiftItems->execute([$item['item_index'], $now, $swapItem['id']]);
        $shiftEntries->execute([$item['item_index'], $now, $swapItem['row_id'], $swapItem['col_id'], $swapItem['item_index']]);

        $shiftItems->execute([$targetIndex, $now, $item['id']]);
        $shiftEntries->execute([$targetIndex, $now, $item['row_id'], $item['col_id'], $tempIndex]);

        $user = currentUser();
        writeAuditLog($pdo, $user['id'] ?? null, $user['name'] ?? null, 'MOVE_READINESS_ITEM', 'readiness_item', $item['row_id'] . '.' . $item['col_id'] . '.' . $item['item_index'], [
            'direction' => $direction,
            'from_index' => (int) $item['item_index'],
            'to_index' => $targetIndex,
            'label' => $item['label'],
        ]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function emptyRowsStructure(?array $items = null): array {
    $items ??= getItemsDefinition();
    $rows = [];

    foreach ($items as $rowId => $rowDef) {
        foreach (['personnel', 'material', 'tactic'] as $colId) {
            $rows[$rowId][$colId] = array_fill(0, count($rowDef[$colId]), 0);
        }
    }

    return $rows;
}

function sanitizeRows(array $rows, ?array $items = null): array {
    $items ??= getItemsDefinition();
    $sanitized = emptyRowsStructure($items);
    foreach ($items as $rowId => $rowDef) {
        foreach (['personnel', 'material', 'tactic'] as $colId) {
            foreach ($rowDef[$colId] as $itemIndex => $_item) {
                $value = (int) ($rows[$rowId][$colId][$itemIndex] ?? 0);
                $sanitized[$rowId][$colId][$itemIndex] = min(2, max(0, $value));
            }
        }
    }

    return $sanitized;
}

function validateRowsPayload(array $rows, ?array $items = null): array {
    $items ??= getItemsDefinition();
    $errors = [];
    foreach ($items as $rowId => $rowDef) {
        if (!isset($rows[$rowId]) || !is_array($rows[$rowId])) {
            $errors[] = 'Missing row: ' . $rowId;
            continue;
        }
        foreach (['personnel', 'material', 'tactic'] as $colId) {
            if (!isset($rows[$rowId][$colId]) || !is_array($rows[$rowId][$colId])) {
                $errors[] = 'Missing column: ' . $rowId . '.' . $colId;
                continue;
            }
            if (count($rows[$rowId][$colId]) !== count($rowDef[$colId])) {
                $errors[] = 'Invalid item count: ' . $rowId . '.' . $colId;
                continue;
            }
            foreach ($rows[$rowId][$colId] as $itemIndex => $value) {
                if (!is_numeric($value)) {
                    $errors[] = 'Invalid value type: ' . $rowId . '.' . $colId . '.' . $itemIndex;
                    continue;
                }
                $numericValue = (int) $value;
                if ($numericValue < 0 || $numericValue > 2) {
                    $errors[] = 'Value out of range: ' . $rowId . '.' . $colId . '.' . $itemIndex;
                }
            }
        }
    }
    return $errors;
}

function sourcePriorityIndex(string $source): int {
    $index = array_search($source, DEFAULT_SOURCE_PRIORITY, true);
    return $index === false ? PHP_INT_MAX : $index;
}

function loadData(?int $unitId = null): array {
    $items = getItemsDefinition();
    $rows = emptyRowsStructure($items);
    $integrationRows = emptyRowsStructure($items);
    $overrideRows = emptyRowsStructure($items);
    $resolvedSources = emptyRowsStructure($items);
    $integrationPresent = emptyRowsStructure($items);
    $overridePresent = emptyRowsStructure($items);
    foreach ($resolvedSources as $rowId => $rowDef) {
        foreach (['personnel', 'material', 'tactic'] as $colId) {
            foreach ($rowDef[$colId] as $itemIndex => $_value) {
                $resolvedSources[$rowId][$colId][$itemIndex] = 'none';
                $integrationPresent[$rowId][$colId][$itemIndex] = false;
                $overridePresent[$rowId][$colId][$itemIndex] = false;
            }
        }
    }
    $unitId ??= getFirstActiveUnitId();
    if ($unitId === null) {
        return [
            'unit_id' => null,
            'updated_at' => null,
            'updated_by' => null,
            'rows' => $rows,
            'integration_rows' => $integrationRows,
            'override_rows' => $overrideRows,
            'integration_present' => $integrationPresent,
            'override_present' => $overridePresent,
            'resolved_sources' => $resolvedSources,
        ];
    }

    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT re.*, COALESCE(u.display_name, re.source_name, re.source) AS actor_name
         FROM readiness_entries re
         LEFT JOIN users u ON u.id = re.updated_by_user_id
         WHERE re.unit_id = ?
           AND re.source <> ?
         ORDER BY re.updated_at DESC, re.id DESC'
    );
    $stmt->execute([$unitId, 'manual']);
    $entries = $stmt->fetchAll();

    $overrideStmt = $pdo->prepare(
        'SELECT ro.*, COALESCE(u.display_name, ro.source_name, ?) AS actor_name
         FROM readiness_overrides ro
         LEFT JOIN users u ON u.id = ro.updated_by_user_id
         WHERE ro.unit_id = ?
         ORDER BY ro.updated_at DESC, ro.id DESC'
    );
    $overrideStmt->execute(['manual override', $unitId]);
    $overrides = $overrideStmt->fetchAll();

    $selected = [];
    $latest = null;
    foreach ($entries as $entry) {
        $key = $entry['row_id'] . '|' . $entry['col_id'] . '|' . $entry['item_index'];
        if (!isset($selected[$key])) {
            $selected[$key] = $entry;
            continue;
        }

        $current = $selected[$key];
        $currentPriority = sourcePriorityIndex((string) $current['source']);
        $entryPriority = sourcePriorityIndex((string) $entry['source']);

        if ($entryPriority < $currentPriority) {
            $selected[$key] = $entry;
        }
    }

    foreach ($selected as $entry) {
        $rowId = (string) ($entry['row_id'] ?? '');
        $colId = (string) ($entry['col_id'] ?? '');
        $itemIndex = (int) ($entry['item_index'] ?? -1);
        if (!isset($rows[$rowId][$colId]) || !array_key_exists($itemIndex, $rows[$rowId][$colId])) {
            continue;
        }

        $value = min(2, max(0, (int) ($entry['value'] ?? 0)));
        $rows[$rowId][$colId][$itemIndex] = $value;
        $integrationRows[$rowId][$colId][$itemIndex] = $value;
        $integrationPresent[$rowId][$colId][$itemIndex] = true;
        $resolvedSources[$rowId][$colId][$itemIndex] = (string) ($entry['source'] ?? 'unknown');

        if ($latest === null || strcmp((string) ($entry['updated_at'] ?? ''), (string) ($latest['updated_at'] ?? '')) > 0) {
            $latest = $entry;
        }
    }

    foreach ($overrides as $entry) {
        $rowId = (string) ($entry['row_id'] ?? '');
        $colId = (string) ($entry['col_id'] ?? '');
        $itemIndex = (int) ($entry['item_index'] ?? -1);
        if (!isset($rows[$rowId][$colId]) || !array_key_exists($itemIndex, $rows[$rowId][$colId])) {
            continue;
        }

        $value = min(2, max(0, (int) ($entry['value'] ?? 0)));
        $rows[$rowId][$colId][$itemIndex] = $value;
        $overrideRows[$rowId][$colId][$itemIndex] = $value;
        $overridePresent[$rowId][$colId][$itemIndex] = true;
        $resolvedSources[$rowId][$colId][$itemIndex] = 'override';

        if ($latest === null || strcmp((string) ($entry['updated_at'] ?? ''), (string) ($latest['updated_at'] ?? '')) > 0) {
            $latest = $entry;
        }
    }

    return [
        'unit_id' => $unitId,
        'updated_at' => $latest['updated_at'] ?? null,
        'updated_by' => $latest['actor_name'] ?? null,
        'rows' => $rows,
        'integration_rows' => $integrationRows,
        'override_rows' => $overrideRows,
        'integration_present' => $integrationPresent,
        'override_present' => $overridePresent,
        'resolved_sources' => $resolvedSources,
    ];
}

function persistReadinessOverrides(PDO $pdo, int $unitId, array $rows, ?int $userId, string $actorName, ?string $timestamp = null): bool {
    $items = getItemsDefinition();
    $timestamp = $timestamp ?: nowString();

    $select = $pdo->prepare('SELECT id FROM readiness_overrides WHERE unit_id = ? AND row_id = ? AND col_id = ? AND item_index = ? LIMIT 1');
    $insert = $pdo->prepare('INSERT INTO readiness_overrides (unit_id, row_id, col_id, item_index, value, updated_by_user_id, source_name, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $update = $pdo->prepare('UPDATE readiness_overrides SET value = ?, updated_by_user_id = ?, source_name = ?, updated_at = ? WHERE id = ?');
    $delete = $pdo->prepare('DELETE FROM readiness_overrides WHERE unit_id = ? AND row_id = ? AND col_id = ? AND item_index = ?');

    $pdo->beginTransaction();
    try {
        foreach ($items as $rowId => $rowDef) {
            foreach (['personnel', 'material', 'tactic'] as $colId) {
                foreach ($rowDef[$colId] as $itemIndex => $_item) {
                    if (!isset($rows[$rowId]) || !isset($rows[$rowId][$colId]) || !array_key_exists($itemIndex, $rows[$rowId][$colId])) {
                        continue;
                    }

                    $value = $rows[$rowId][$colId][$itemIndex];
                    $select->execute([$unitId, $rowId, $colId, $itemIndex]);
                    $existing = $select->fetch();

                    if ($value === null || $value === '') {
                        if ($existing) {
                            $delete->execute([$unitId, $rowId, $colId, $itemIndex]);
                        }
                        continue;
                    }

                    $value = min(2, max(0, (int) $value));
                    if ($existing) {
                        $update->execute([$value, $userId, $actorName, $timestamp, $existing['id']]);
                    } else {
                        $insert->execute([$unitId, $rowId, $colId, $itemIndex, $value, $userId, $actorName, $timestamp, $timestamp]);
                    }
                }
            }
        }

        writeAuditLog($pdo, $userId, $actorName, 'MANUAL_OVERRIDE_SYNC', 'unit', (string) $unitId, [
            'unit_id' => $unitId,
            'updated_at' => $timestamp,
        ]);
        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function loadRowsBySource(PDO $pdo, int $unitId, string $source, ?array $items = null): array {
    $items ??= getItemsDefinition();
    $rows = emptyRowsStructure($items);

    $stmt = $pdo->prepare(
        'SELECT row_id, col_id, item_index, value
         FROM readiness_entries
         WHERE unit_id = ? AND source = ?'
    );
    $stmt->execute([$unitId, $source]);
    $entries = $stmt->fetchAll();

    foreach ($entries as $entry) {
        $rowId = (string) ($entry['row_id'] ?? '');
        $colId = (string) ($entry['col_id'] ?? '');
        $itemIndex = (int) ($entry['item_index'] ?? -1);

        if (!isset($rows[$rowId][$colId]) || !array_key_exists($itemIndex, $rows[$rowId][$colId])) {
            continue;
        }
        $rows[$rowId][$colId][$itemIndex] = min(2, max(0, (int) ($entry['value'] ?? 0)));
    }

    return $rows;
}

function applyMockPercentMetricsToRows(array $rows, array $metrics, ?PDO $pdo = null): array {
    $pdo ??= db();
    $map = effectiveMockPercentMetricMap($pdo);
    $applied = [];
    $errors = [];

    foreach ($metrics as $index => $metric) {
        if (!is_array($metric)) {
            $errors[] = 'metrics[' . $index . '] must be an object';
            continue;
        }

        $key = trim((string) ($metric['key'] ?? ''));
        if ($key === '') {
            $errors[] = 'metrics[' . $index . '].key is required';
            continue;
        }
        if (!isset($map[$key])) {
            $errors[] = 'Unknown metric key: ' . $key;
            continue;
        }

        $target = $map[$key];
        $rowId = (string) $target['row_id'];
        $colId = (string) $target['col_id'];
        $itemIndex = (int) $target['item_index'];

        if (!isset($rows[$rowId][$colId]) || !array_key_exists($itemIndex, $rows[$rowId][$colId])) {
            $errors[] = 'Metric key maps to missing readiness item: ' . $key;
            continue;
        }

        try {
            $transformed = applyMockMetricTransform($metric, $target, $pdo);
        } catch (Throwable $e) {
            $errors[] = 'metrics[' . $index . ']: ' . $e->getMessage();
            continue;
        }

        $score = (int) $transformed['score'];
        $rows[$rowId][$colId][$itemIndex] = $score;
        $applied[] = [
            'key' => $key,
            'transform_type' => (string) ($target['transform_type'] ?? 'percent_threshold'),
            'input_field' => (string) ($transformed['value_field'] ?? 'value'),
            'input_value' => $transformed['normalized_value'] ?? null,
            'score' => $score,
            'row_id' => $rowId,
            'col_id' => $colId,
            'item_index' => $itemIndex,
        ];
    }

    return [
        'rows' => $rows,
        'applied' => $applied,
        'errors' => $errors,
    ];
}

function persistRows(PDO $pdo, int $unitId, array $rows, string $source, ?int $userId, string $actorName, ?string $timestamp = null): bool {
    $items = getItemsDefinition();
    $rows = sanitizeRows($rows, $items);
    $timestamp = $timestamp ?: nowString();

    $select = $pdo->prepare('SELECT id FROM readiness_entries WHERE unit_id = ? AND row_id = ? AND col_id = ? AND item_index = ? AND source = ? LIMIT 1');
    $insert = $pdo->prepare('INSERT INTO readiness_entries (unit_id, row_id, col_id, item_index, value, source, updated_by_user_id, source_name, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $update = $pdo->prepare('UPDATE readiness_entries SET value = ?, updated_by_user_id = ?, source_name = ?, updated_at = ? WHERE id = ?');

    $pdo->beginTransaction();
    try {
        foreach ($items as $rowId => $rowDef) {
            foreach (['personnel', 'material', 'tactic'] as $colId) {
                foreach ($rowDef[$colId] as $itemIndex => $_item) {
                    $value = (int) $rows[$rowId][$colId][$itemIndex];
                    $select->execute([$unitId, $rowId, $colId, $itemIndex, $source]);
                    $existing = $select->fetch();

                    if ($existing) {
                        $update->execute([$value, $userId, $actorName, $timestamp, $existing['id']]);
                    } else {
                        $insert->execute([$unitId, $rowId, $colId, $itemIndex, $value, $source, $userId, $actorName, $timestamp, $timestamp]);
                    }
                }
            }
        }

        writeAuditLog($pdo, $userId, $actorName, strtoupper($source) . '_UPSERT', 'unit', (string) $unitId, [
            'unit_id' => $unitId,
            'source' => $source,
            'updated_at' => $timestamp,
        ]);
        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function saveData(array $data, int $unitId, ?int $userId, string $actorName, string $source = 'manual'): bool {
    return persistRows(db(), $unitId, (array) ($data['rows'] ?? []), $source, $userId, $actorName);
}

function writeAuditLog(PDO $pdo, ?int $userId, ?string $actorName, string $action, string $entityType, string $entityKey, array $details = []): void {
    $stmt = $pdo->prepare('INSERT INTO audit_log (user_id, actor_name, action, entity_type, entity_key, details, ip_address, user_agent, request_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $userId,
        $actorName,
        $action,
        $entityType,
        $entityKey,
        json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        currentRequestIp(),
        currentRequestUserAgent(),
        requestIdentifier(),
        nowString(),
    ]);
}

function getUnits(bool $activeOnly = true): array {
    $sql = 'SELECT * FROM units';
    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY name';
    return db()->query($sql)->fetchAll();
}

function getUnitById(?int $unitId): ?array {
    if ($unitId === null) {
        return null;
    }
    $stmt = db()->prepare('SELECT * FROM units WHERE id = ? LIMIT 1');
    $stmt->execute([$unitId]);
    return $stmt->fetch() ?: null;
}

function getUnitIdByCode(string $code, ?PDO $pdo = null): ?int {
    $pdo ??= db();
    $stmt = $pdo->prepare('SELECT id FROM units WHERE code = ? LIMIT 1');
    $stmt->execute([$code]);
    $id = $stmt->fetchColumn();
    return $id === false ? null : (int) $id;
}

function getFirstActiveUnitId(): ?int {
    $stmt = db()->query('SELECT id FROM units WHERE is_active = 1 ORDER BY name LIMIT 1');
    $id = $stmt->fetchColumn();
    return $id === false ? null : (int) $id;
}

function rolePermissions(string $role): array {
    return ROLE_PERMISSIONS[$role] ?? [];
}

function roleOptions(): array {
    return [
        'admin' => 'Admin',
        'officer' => 'Officer',
        'viewer' => 'Viewer',
        'integration_service' => 'Integration Service',
    ];
}

function findUserByUsername(string $username): ?array {
    $stmt = db()->prepare(
        'SELECT u.*, units.name AS unit_name, units.code AS unit_code
         FROM users u
         LEFT JOIN units ON units.id = u.unit_id
         WHERE u.username = ?
         LIMIT 1'
    );
    $stmt->execute([$username]);
    return $stmt->fetch() ?: null;
}

function findUserByDisplayName(string $displayName): ?array {
    $stmt = db()->prepare('SELECT * FROM users WHERE display_name = ? LIMIT 1');
    $stmt->execute([$displayName]);
    return $stmt->fetch() ?: null;
}

function getUsers(): array {
    $stmt = db()->query(
        'SELECT u.*, units.name AS unit_name, units.code AS unit_code
         FROM users u
         LEFT JOIN units ON units.id = u.unit_id
         ORDER BY u.username'
    );
    return $stmt->fetchAll();
}

function getUserById(int $userId): ?array {
    $stmt = db()->prepare(
        'SELECT u.*, units.name AS unit_name, units.code AS unit_code
         FROM users u
         LEFT JOIN units ON units.id = u.unit_id
         WHERE u.id = ?
         LIMIT 1'
    );
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: null;
}

function getAuditLogs(array $filters = [], int $limit = 200): array {
    $clauses = [];
    $params = [];
    if (!empty($filters['action'])) {
        $clauses[] = 'action = ?';
        $params[] = $filters['action'];
    }
    if (!empty($filters['entity_type'])) {
        $clauses[] = 'entity_type = ?';
        $params[] = $filters['entity_type'];
    }
    if (!empty($filters['search'])) {
        $clauses[] = '(actor_name LIKE ? OR entity_key LIKE ? OR details LIKE ?)';
        $searchTerm = '%' . $filters['search'] . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $sql = 'SELECT * FROM audit_log';
    if ($clauses) {
        $sql .= ' WHERE ' . implode(' AND ', $clauses);
    }
    $sql .= ' ORDER BY created_at DESC, id DESC LIMIT ' . sqlLimitPlaceholder($limit);

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function auditActionOptions(): array {
    $stmt = db()->query('SELECT DISTINCT action FROM audit_log ORDER BY action');
    return array_values(array_filter(array_map(static fn(array $row): string => (string) $row['action'], $stmt->fetchAll())));
}

function auditEntityTypeOptions(): array {
    $stmt = db()->query('SELECT DISTINCT entity_type FROM audit_log ORDER BY entity_type');
    return array_values(array_filter(array_map(static fn(array $row): string => (string) $row['entity_type'], $stmt->fetchAll())));
}

function passwordPolicyMessages(): array {
    $messages = ['อย่างน้อย ' . PASSWORD_MIN_LENGTH . ' ตัวอักษร'];
    if (PASSWORD_REQUIRE_UPPERCASE) {
        $messages[] = 'มีอักษรพิมพ์ใหญ่';
    }
    if (PASSWORD_REQUIRE_LOWERCASE) {
        $messages[] = 'มีอักษรพิมพ์เล็ก';
    }
    if (PASSWORD_REQUIRE_NUMBER) {
        $messages[] = 'มีตัวเลข';
    }
    if (PASSWORD_REQUIRE_SPECIAL) {
        $messages[] = 'มีอักขระพิเศษ';
    }
    return $messages;
}

function passwordPolicyText(): string {
    return implode(', ', passwordPolicyMessages());
}

function validatePasswordPolicy(string $password): array {
    $errors = [];
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = 'รหัสผ่านต้องยาวอย่างน้อย ' . PASSWORD_MIN_LENGTH . ' ตัวอักษร';
    }
    if (PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
        $errors[] = 'รหัสผ่านต้องมีอักษรพิมพ์ใหญ่อย่างน้อย 1 ตัว';
    }
    if (PASSWORD_REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
        $errors[] = 'รหัสผ่านต้องมีอักษรพิมพ์เล็กอย่างน้อย 1 ตัว';
    }
    if (PASSWORD_REQUIRE_NUMBER && !preg_match('/\d/', $password)) {
        $errors[] = 'รหัสผ่านต้องมีตัวเลขอย่างน้อย 1 ตัว';
    }
    if (PASSWORD_REQUIRE_SPECIAL && !preg_match('/[^a-zA-Z0-9]/', $password)) {
        $errors[] = 'รหัสผ่านต้องมีอักขระพิเศษอย่างน้อย 1 ตัว';
    }
    return $errors;
}

function assertPasswordPolicy(string $password): void {
    $errors = validatePasswordPolicy($password);
    if ($errors) {
        throw new InvalidArgumentException(implode(' | ', $errors));
    }
}

function dateTimeToTimestamp(?string $value): int {
    if ($value === null || trim($value) === '') {
        return 0;
    }
    $timestamp = strtotime($value);
    return $timestamp === false ? 0 : $timestamp;
}

function isUserLocked(array $user): bool {
    return dateTimeToTimestamp($user['locked_until'] ?? null) > time();
}

function lockoutRemainingSeconds(array $user): int {
    $remaining = dateTimeToTimestamp($user['locked_until'] ?? null) - time();
    return max(0, $remaining);
}

function resetFailedLoginState(int $userId): void {
    $stmt = db()->prepare('UPDATE users SET failed_login_attempts = 0, last_failed_login_at = NULL, locked_until = NULL, updated_at = ? WHERE id = ?');
    $stmt->execute([nowString(), $userId]);
}

function recordFailedLoginAttempt(array $user): array {
    $attempts = (int) ($user['failed_login_attempts'] ?? 0) + 1;
    $lockedUntil = null;
    if ($attempts >= LOGIN_MAX_FAILURES) {
        $lockedUntil = date('Y-m-d H:i:s', time() + LOGIN_LOCKOUT_SECONDS);
    }

    $stmt = db()->prepare('UPDATE users SET failed_login_attempts = ?, last_failed_login_at = ?, locked_until = ?, updated_at = ? WHERE id = ?');
    $now = nowString();
    $stmt->execute([$attempts, $now, $lockedUntil, $now, $user['id']]);

    return getUserById((int) $user['id']) ?? $user;
}

function attemptLogin(string $username, string $password): array {
    $user = findUserByUsername($username);
    if (!$user || (int) ($user['is_active'] ?? 0) !== 1) {
        writeAuditLog(db(), null, $username, 'FAILED_LOGIN', 'user', $username, ['reason' => 'unknown_or_inactive']);
        return ['ok' => false, 'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'];
    }

    if (($user['role'] ?? '') === 'integration_service') {
        writeAuditLog(db(), (int) $user['id'], $user['display_name'], 'WEB_LOGIN_BLOCKED', 'user', $user['username'], [
            'reason' => 'integration_service_web_login_forbidden',
        ]);
        return ['ok' => false, 'message' => 'บัญชีนี้ใช้สำหรับ API เท่านั้น ไม่สามารถเข้าสู่ระบบผ่านหน้าเว็บได้'];
    }

    if (isUserLocked($user)) {
        writeAuditLog(db(), (int) $user['id'], $user['display_name'], 'LOCKED_LOGIN_ATTEMPT', 'user', $user['username'], [
            'locked_until' => $user['locked_until'],
        ]);
        $minutes = (int) ceil(lockoutRemainingSeconds($user) / 60);
        return ['ok' => false, 'message' => 'บัญชีถูกล็อกชั่วคราว กรุณาลองใหม่อีกครั้งในประมาณ ' . max(1, $minutes) . ' นาที'];
    }

    if (!password_verify($password, (string) $user['password_hash'])) {
        $updatedUser = recordFailedLoginAttempt($user);
        writeAuditLog(db(), (int) $user['id'], $user['display_name'], 'FAILED_LOGIN', 'user', $user['username'], [
            'failed_login_attempts' => $updatedUser['failed_login_attempts'] ?? null,
            'locked_until' => $updatedUser['locked_until'] ?? null,
        ]);

        if (isUserLocked($updatedUser)) {
            return ['ok' => false, 'message' => 'บัญชีถูกล็อกชั่วคราวจากการใส่รหัสผ่านผิดหลายครั้ง'];
        }

        return ['ok' => false, 'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'];
    }

    resetFailedLoginState((int) $user['id']);
    $freshUser = findUserByUsername($username) ?? $user;
    return ['ok' => true, 'user' => $freshUser];
}

function authenticateUser(string $username, string $password): ?array {
    $result = attemptLogin($username, $password);
    return $result['ok'] ? ($result['user'] ?? null) : null;
}

function startUserSession(array $user): void {
    ensureSessionStarted();
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user'] = $user['username'];
    $_SESSION['user_name'] = $user['display_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['permissions'] = rolePermissions($user['role']);
    $_SESSION['unit_id'] = isset($user['unit_id']) ? (int) $user['unit_id'] : null;
    $_SESSION['unit_name'] = $user['unit_name'] ?? null;
}

function currentUser(): ?array {
    ensureSessionStarted();
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    return [
        'id' => (int) $_SESSION['user_id'],
        'username' => $_SESSION['user'] ?? '',
        'name' => $_SESSION['user_name'] ?? '',
        'role' => $_SESSION['role'] ?? '',
        'permissions' => $_SESSION['permissions'] ?? [],
        'unit_id' => $_SESSION['unit_id'] ?? null,
        'unit_name' => $_SESSION['unit_name'] ?? null,
    ];
}

function currentUserCan(string $permission): bool {
    $user = currentUser();
    if (!$user) {
        return false;
    }
    return in_array($permission, $user['permissions'], true);
}

function currentUserCanAny(array $permissions): bool {
    foreach ($permissions as $permission) {
        if (currentUserCan($permission)) {
            return true;
        }
    }
    return false;
}

function userCanEditReadiness(?array $user = null): bool {
    $permissions = $user['permissions'] ?? currentUser()['permissions'] ?? [];
    return in_array('edit:own_unit', $permissions, true) || in_array('edit:all_units', $permissions, true);
}

function userCanViewDashboard(?array $user = null): bool {
    $permissions = $user['permissions'] ?? currentUser()['permissions'] ?? [];
    return in_array('view:dashboard', $permissions, true)
        || in_array('view:own_unit', $permissions, true)
        || in_array('view:all_units', $permissions, true)
        || userCanEditReadiness($user);
}

function defaultLandingPath(?array $user = null): string {
    $user ??= currentUser();
    if (!$user) {
        return 'login.php';
    }
    if (userCanEditReadiness($user)) {
        return 'input.php';
    }
    if (userCanViewDashboard($user)) {
        return 'index.php';
    }
    return 'index.php';
}

function requireLogin(): void {
    ensureSessionStarted();
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function requirePermission(string $permission): void {
    requireLogin();
    if (!currentUserCan($permission)) {
        http_response_code(403);
        exit('ไม่มีสิทธิ์เข้าถึงส่วนนี้');
    }
}

function requireAnyPermission(array $permissions): void {
    requireLogin();
    if (!currentUserCanAny($permissions)) {
        http_response_code(403);
        exit('ไม่มีสิทธิ์เข้าถึงส่วนนี้');
    }
}

function canAccessUnit(?int $unitId): bool {
    if ($unitId === null) {
        return false;
    }
    if (currentUserCan('edit:all_units') || currentUserCan('view:all_units')) {
        return true;
    }
    $user = currentUser();
    if (!$user) {
        return false;
    }
    return isset($user['unit_id']) && (int) $user['unit_id'] === (int) $unitId;
}

function selectedUnitIdFromRequest(?int $fallback = null): ?int {
    $requested = $_POST['unit_id'] ?? $_GET['unit_id'] ?? $fallback;
    if ($requested === null || $requested === '') {
        return $fallback;
    }
    return (int) $requested;
}

function createUnit(string $code, string $name): void {
    $code = trim($code);
    $name = trim($name);
    if ($code === '' || $name === '') {
        throw new InvalidArgumentException('กรอกข้อมูลหน่วยให้ครบถ้วน');
    }
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $code)) {
        throw new InvalidArgumentException('code หน่วยใช้ได้เฉพาะ a-z, A-Z, 0-9, - และ _');
    }

    $stmt = db()->prepare('INSERT INTO units (code, name, is_active, created_at, updated_at) VALUES (?, ?, 1, ?, ?)');
    $now = nowString();
    $stmt->execute([$code, $name, $now, $now]);

    $user = currentUser();
    writeAuditLog(db(), $user['id'] ?? null, $user['name'] ?? null, 'CREATE_UNIT', 'unit', $code, ['name' => $name]);
}

function updateUnit(int $unitId, string $code, string $name, bool $isActive): void {
    $stmt = db()->prepare('UPDATE units SET code = ?, name = ?, is_active = ?, updated_at = ? WHERE id = ?');
    $stmt->execute([$code, $name, $isActive ? 1 : 0, nowString(), $unitId]);

    $user = currentUser();
    writeAuditLog(db(), $user['id'] ?? null, $user['name'] ?? null, 'UPDATE_UNIT', 'unit', (string) $unitId, [
        'code' => $code,
        'name' => $name,
        'is_active' => $isActive,
    ]);
}

function createUser(string $username, string $displayName, string $password, string $role, ?int $unitId, bool $isActive): void {
    $username = trim($username);
    $displayName = trim($displayName);
    if ($username === '' || $displayName === '' || $password === '') {
        throw new InvalidArgumentException('กรอกข้อมูลผู้ใช้ให้ครบถ้วน');
    }
    if (!preg_match('/^[a-zA-Z0-9_.-]{3,64}$/', $username)) {
        throw new InvalidArgumentException('username ต้องยาว 3-64 ตัวอักษร และใช้ได้เฉพาะ a-z, A-Z, 0-9, _, -, .');
    }
    assertPasswordPolicy($password);
    if (!isset(roleOptions()[$role])) {
        throw new InvalidArgumentException('role ไม่ถูกต้อง');
    }

    $stmt = db()->prepare('INSERT INTO users (username, password_hash, display_name, role, unit_id, is_active, failed_login_attempts, last_failed_login_at, locked_until, password_changed_at, password_reset_token_hash, password_reset_token_expires_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 0, NULL, NULL, ?, NULL, NULL, ?, ?)');
    $now = nowString();
    $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $displayName, $role, $unitId, $isActive ? 1 : 0, $now, $now, $now]);

    $user = currentUser();
    writeAuditLog(db(), $user['id'] ?? null, $user['name'] ?? null, 'CREATE_USER', 'user', $username, [
        'role' => $role,
        'unit_id' => $unitId,
        'is_active' => $isActive,
    ]);
}

function updateUser(int $userId, string $displayName, string $role, ?int $unitId, bool $isActive, string $password = ''): void {
    if (!isset(roleOptions()[$role])) {
        throw new InvalidArgumentException('role ไม่ถูกต้อง');
    }
    if ($password !== '') {
        assertPasswordPolicy($password);
    }

    $pdo = db();
    if ($password !== '') {
        $stmt = $pdo->prepare('UPDATE users SET display_name = ?, role = ?, unit_id = ?, is_active = ?, password_hash = ?, password_changed_at = ?, password_reset_token_hash = NULL, password_reset_token_expires_at = NULL, failed_login_attempts = 0, last_failed_login_at = NULL, locked_until = NULL, updated_at = ? WHERE id = ?');
        $now = nowString();
        $stmt->execute([$displayName, $role, $unitId, $isActive ? 1 : 0, password_hash($password, PASSWORD_DEFAULT), $now, $now, $userId]);
    } else {
        $stmt = $pdo->prepare('UPDATE users SET display_name = ?, role = ?, unit_id = ?, is_active = ?, updated_at = ? WHERE id = ?');
        $stmt->execute([$displayName, $role, $unitId, $isActive ? 1 : 0, nowString(), $userId]);
    }

    $user = currentUser();
    writeAuditLog($pdo, $user['id'] ?? null, $user['name'] ?? null, 'UPDATE_USER', 'user', (string) $userId, [
        'role' => $role,
        'unit_id' => $unitId,
        'is_active' => $isActive,
        'password_reset' => $password !== '',
    ]);
}

function slugifyIdentifier(string $value): string {
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9_-]+/', '-', $value) ?? '';
    $value = trim($value, '-');
    return $value !== '' ? $value : 'client';
}

function generateClientKey(string $name): string {
    return slugifyIdentifier($name) . '-' . bin2hex(random_bytes(4));
}

function generateClientSecret(): string {
    return bin2hex(random_bytes(24));
}

function getApiClients(): array {
    $stmt = db()->query(
        'SELECT api_clients.*, units.name AS unit_name, units.code AS unit_code
         FROM api_clients
         LEFT JOIN units ON units.id = api_clients.unit_id
         ORDER BY api_clients.name'
    );
    return $stmt->fetchAll();
}

function findApiClientByClientKey(string $clientKey): ?array {
    $stmt = db()->prepare(
        'SELECT api_clients.*, units.name AS unit_name, units.code AS unit_code
         FROM api_clients
         LEFT JOIN units ON units.id = api_clients.unit_id
         WHERE api_clients.client_key = ? AND api_clients.is_active = 1
         LIMIT 1'
    );
    $stmt->execute([$clientKey]);
    return $stmt->fetch() ?: null;
}

function findApiClientByToken(string $token): ?array {
    $clients = getApiClients();
    foreach ($clients as $client) {
        if ((int) $client['is_active'] !== 1) {
            continue;
        }
        if (password_verify($token, (string) $client['token_hash'])) {
            return $client;
        }
    }
    return null;
}

function findApiClientByCredentials(?string $clientKey, string $secret): ?array {
    if ($secret === '') {
        return null;
    }
    if ($clientKey !== null && $clientKey !== '') {
        $client = findApiClientByClientKey($clientKey);
        if ($client && password_verify($secret, (string) $client['token_hash'])) {
            return $client;
        }
        return null;
    }

    if (API_REQUIRE_CLIENT_KEY) {
        return null;
    }

    return findApiClientByToken($secret);
}
function normalizeAllowedIps(string $allowedIps): string {
    $parts = array_filter(array_map('trim', explode(',', $allowedIps)), static fn(string $part): bool => $part !== '');
    return implode(',', array_values(array_unique($parts)));
}

function createApiClient(string $name, ?string $secret, ?int $unitId, bool $isActive, string $allowedIps = '', ?int $rateLimit = null): array {
    $name = trim($name);
    $secret = trim((string) $secret);
    if ($name === '') {
        throw new InvalidArgumentException('กรอกชื่อ client ให้ครบถ้วน');
    }
    if ($secret === '') {
        $secret = generateClientSecret();
    }
    $clientKey = generateClientKey($name);
    $allowedIps = normalizeAllowedIps($allowedIps);
    $rateLimit = $rateLimit !== null && $rateLimit > 0 ? $rateLimit : API_DEFAULT_RATE_LIMIT_PER_MINUTE;

    $stmt = db()->prepare('INSERT INTO api_clients (name, client_key, token_hash, unit_id, allowed_ips, rate_limit_per_minute, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $now = nowString();
    $stmt->execute([$name, $clientKey, password_hash($secret, PASSWORD_DEFAULT), $unitId, $allowedIps, $rateLimit, $isActive ? 1 : 0, $now, $now]);

    $user = currentUser();
    writeAuditLog(db(), $user['id'] ?? null, $user['name'] ?? null, 'CREATE_API_CLIENT', 'api_client', $name, [
        'client_key' => $clientKey,
        'unit_id' => $unitId,
        'is_active' => $isActive,
        'allowed_ips' => $allowedIps,
        'rate_limit_per_minute' => $rateLimit,
    ]);

    return [
        'client_key' => $clientKey,
        'secret' => $secret,
    ];
}

function updateApiClient(int $clientId, string $name, ?int $unitId, bool $isActive, string $secret = '', string $allowedIps = '', ?int $rateLimit = null): array {
    $pdo = db();
    $allowedIps = normalizeAllowedIps($allowedIps);
    $rateLimit = $rateLimit !== null && $rateLimit > 0 ? $rateLimit : API_DEFAULT_RATE_LIMIT_PER_MINUTE;
    $issuedSecret = '';
    if ($secret !== '') {
        $issuedSecret = $secret;
        $stmt = $pdo->prepare('UPDATE api_clients SET name = ?, token_hash = ?, unit_id = ?, allowed_ips = ?, rate_limit_per_minute = ?, is_active = ?, updated_at = ? WHERE id = ?');
        $stmt->execute([$name, password_hash($secret, PASSWORD_DEFAULT), $unitId, $allowedIps, $rateLimit, $isActive ? 1 : 0, nowString(), $clientId]);
    } else {
        $stmt = $pdo->prepare('UPDATE api_clients SET name = ?, unit_id = ?, allowed_ips = ?, rate_limit_per_minute = ?, is_active = ?, updated_at = ? WHERE id = ?');
        $stmt->execute([$name, $unitId, $allowedIps, $rateLimit, $isActive ? 1 : 0, nowString(), $clientId]);
    }

    $user = currentUser();
    writeAuditLog($pdo, $user['id'] ?? null, $user['name'] ?? null, 'UPDATE_API_CLIENT', 'api_client', (string) $clientId, [
        'unit_id' => $unitId,
        'is_active' => $isActive,
        'token_reset' => $secret !== '',
        'allowed_ips' => $allowedIps,
        'rate_limit_per_minute' => $rateLimit,
    ]);

    return [
        'secret' => $issuedSecret,
    ];
}

function generatePasswordResetToken(): string {
    return bin2hex(random_bytes(24));
}

function issuePasswordResetForUser(int $userId): array {
    $user = getUserById($userId);
    if (!$user) {
        throw new InvalidArgumentException('ไม่พบผู้ใช้');
    }

    $token = generatePasswordResetToken();
    $tokenHash = hash('sha256', $token);
    $expiresAt = date('Y-m-d H:i:s', time() + PASSWORD_RESET_TTL);
    $stmt = db()->prepare('UPDATE users SET password_reset_token_hash = ?, password_reset_token_expires_at = ?, updated_at = ? WHERE id = ?');
    $now = nowString();
    $stmt->execute([$tokenHash, $expiresAt, $now, $userId]);

    $actor = currentUser();
    writeAuditLog(db(), $actor['id'] ?? null, $actor['name'] ?? null, 'ISSUE_PASSWORD_RESET', 'user', $user['username'], [
        'expires_at' => $expiresAt,
    ]);

    return [
        'token' => $token,
        'expires_at' => $expiresAt,
        'url' => rtrim(APP_URL, '/') . '/reset-password.php?token=' . urlencode($token),
        'user' => $user,
    ];
}

function findUserByPasswordResetToken(string $token): ?array {
    $tokenHash = hash('sha256', $token);
    $stmt = db()->prepare('SELECT u.*, units.name AS unit_name, units.code AS unit_code FROM users u LEFT JOIN units ON units.id = u.unit_id WHERE u.password_reset_token_hash = ? AND u.password_reset_token_expires_at >= ? LIMIT 1');
    $stmt->execute([$tokenHash, nowString()]);
    return $stmt->fetch() ?: null;
}

function completePasswordReset(string $token, string $password, string $passwordConfirm): array {
    if ($password !== $passwordConfirm) {
        throw new InvalidArgumentException('รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน');
    }
    assertPasswordPolicy($password);

    $user = findUserByPasswordResetToken($token);
    if (!$user) {
        throw new InvalidArgumentException('ลิงก์ reset ไม่ถูกต้องหรือหมดอายุ');
    }

    $stmt = db()->prepare('UPDATE users SET password_hash = ?, password_changed_at = ?, password_reset_token_hash = NULL, password_reset_token_expires_at = NULL, failed_login_attempts = 0, last_failed_login_at = NULL, locked_until = NULL, updated_at = ? WHERE id = ?');
    $now = nowString();
    $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $now, $now, $user['id']]);

    writeAuditLog(db(), (int) $user['id'], $user['display_name'], 'PASSWORD_RESET_COMPLETE', 'user', $user['username'], []);
    return getUserById((int) $user['id']) ?? $user;
}

function ipAllowedForClient(string $requestIp, string $allowedIps): bool {
    $allowedIps = trim($allowedIps);
    if ($allowedIps === '') {
        return true;
    }

    foreach (explode(',', $allowedIps) as $candidate) {
        $candidate = trim($candidate);
        if ($candidate === '') {
            continue;
        }
        if ($candidate === $requestIp) {
            return true;
        }
        if (str_contains($candidate, '/')) {
            if (ipInCidr($requestIp, $candidate)) {
                return true;
            }
        }
    }
    return false;
}

function ipInCidr(string $ip, string $cidr): bool {
    [$subnet, $mask] = array_pad(explode('/', $cidr, 2), 2, null);
    if ($subnet === null || $mask === null) {
        return false;
    }
    $ipLong = ip2long($ip);
    $subnetLong = ip2long($subnet);
    if ($ipLong === false || $subnetLong === false) {
        return false;
    }
    $mask = (int) $mask;
    $netmask = -1 << (32 - $mask);
    $subnetLong &= $netmask;
    return ($ipLong & $netmask) === $subnetLong;
}

function requestBody(): string {
    static $body = null;
    if ($body !== null) {
        return $body;
    }
    $body = (string) file_get_contents('php://input');
    return $body;
}

function requestJsonBody(): array {
    $body = requestBody();
    $decoded = json_decode($body, true);
    return is_array($decoded) ? $decoded : [];
}

function requestContentType(): string {
    return strtolower(trim((string) ($_SERVER['CONTENT_TYPE'] ?? '')));
}

function assertJsonRequest(): void {
    $contentType = requestContentType();
    if ($contentType !== '' && !str_starts_with($contentType, 'application/json')) {
        jsonResponse(['ok' => false, 'error' => 'Content-Type must be application/json'], 415);
    }
}

function assertApiBodySize(): void {
    $body = requestBody();
    if (strlen($body) > API_MAX_BODY_BYTES) {
        jsonResponse(['ok' => false, 'error' => 'Payload too large'], 413);
    }
}

function requestClientKey(): string {
    return trim((string) ($_SERVER['HTTP_X_CLIENT_KEY'] ?? ''));
}

function requestClientSecret(): string {
    return trim((string) ($_SERVER['HTTP_X_CLIENT_SECRET'] ?? ''));
}

function requestBearerToken(): string {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
        return trim($matches[1]);
    }
    return trim((string) ($_SERVER['HTTP_X_API_KEY'] ?? ''));
}

function assertApiClientAllowed(array $client): void {
    $requestIp = currentRequestIp();
    $globalAllowedIps = normalizeAllowedIps(API_ALLOWED_IPS);
    if ($globalAllowedIps !== '' && !ipAllowedForClient($requestIp, $globalAllowedIps)) {
        jsonResponse(['ok' => false, 'error' => 'Request IP is not allowed'], 403);
    }
    if (!ipAllowedForClient($requestIp, (string) ($client['allowed_ips'] ?? ''))) {
        jsonResponse(['ok' => false, 'error' => 'Request IP is not allowed for this client'], 403);
    }
}

function apiRequestsInWindow(int $clientId, int $windowSeconds = 60): int {
    $threshold = date('Y-m-d H:i:s', time() - $windowSeconds);
    $stmt = db()->prepare('SELECT COUNT(*) FROM api_request_log WHERE client_id = ? AND created_at >= ?');
    $stmt->execute([$clientId, $threshold]);
    return (int) $stmt->fetchColumn();
}

function assertApiRateLimit(array $client): void {
    $clientId = (int) ($client['id'] ?? 0);
    $limit = (int) ($client['rate_limit_per_minute'] ?? API_DEFAULT_RATE_LIMIT_PER_MINUTE);
    $limit = $limit > 0 ? $limit : API_DEFAULT_RATE_LIMIT_PER_MINUTE;
    if ($clientId <= 0) {
        return;
    }
    if (apiRequestsInWindow($clientId) >= $limit) {
        jsonResponse(['ok' => false, 'error' => 'Rate limit exceeded'], 429);
    }
}

function recordApiRequest(?array $client, int $statusCode, string $endpoint): void {
    $stmt = db()->prepare('INSERT INTO api_request_log (client_id, client_key, request_ip, endpoint, status_code, request_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $client['id'] ?? null,
        $client['client_key'] ?? null,
        currentRequestIp(),
        $endpoint,
        $statusCode,
        requestIdentifier(),
        nowString(),
    ]);
}

function markApiClientUsage(array $client): void {
    $update = db()->prepare('UPDATE api_clients SET last_used_at = ?, last_used_ip = ?, updated_at = ? WHERE id = ?');
    $now = nowString();
    $update->execute([$now, currentRequestIp(), $now, $client['id']]);
}

function authenticateApiRequest(): array {
    assertJsonRequest();
    assertApiBodySize();

    $clientKey = requestClientKey();
    $secret = requestClientSecret();
    if ($secret === '') {
        $secret = requestBearerToken();
    }

    $client = findApiClientByCredentials($clientKey !== '' ? $clientKey : null, $secret);
    if (!$client) {
        recordApiRequest(null, 401, (string) ($_SERVER['REQUEST_URI'] ?? 'api'));
        jsonResponse(['ok' => false, 'error' => 'Invalid API credentials'], 401);
    }

    assertApiClientAllowed($client);
    assertApiRateLimit($client);
    markApiClientUsage($client);
    return $client;
}

function jsonResponse(array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function getUnitDashboardSummary(?array $units = null): array {
    $units ??= getUnits();
    $summary = [];
    foreach ($units as $unit) {
        $unitId = (int) $unit['id'];
        $data = loadData($unitId);
        $overall = calcOverall($data['rows'] ?? []);
        $summary[] = [
            'unit' => $unit,
            'overall' => $overall,
            'status' => statusLabel($overall),
            'updated_at' => $data['updated_at'] ?? null,
            'updated_by' => $data['updated_by'] ?? null,
        ];
    }

    usort($summary, static function (array $left, array $right): int {
        return strcmp((string) $left['unit']['name'], (string) $right['unit']['name']);
    });

    return $summary;
}
