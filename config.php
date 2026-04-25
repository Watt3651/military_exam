<?php
if (!function_exists('readiness_load_env_file')) {
    function readiness_load_env_file(string $filePath): void {
        if (!is_file($filePath) || !is_readable($filePath)) {
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            [$name, $value] = array_pad(explode('=', $trimmed, 2), 2, null);
            $name = trim((string) $name);
            if ($name === '' || getenv($name) !== false) {
                continue;
            }

            $value = trim((string) $value);
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }

            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

readiness_load_env_file(__DIR__ . '/.env');

define('APP_ENV', getenv('READINESS_APP_ENV') ?: 'development');
define('APP_DEBUG', filter_var(getenv('READINESS_APP_DEBUG') ?: '0', FILTER_VALIDATE_BOOLEAN));
define('APP_URL', getenv('READINESS_APP_URL') ?: 'http://localhost');
define('TRUST_PROXY_HEADERS', filter_var(getenv('READINESS_TRUST_PROXY_HEADERS') ?: '0', FILTER_VALIDATE_BOOLEAN));

// password เดิมใช้สำหรับ seed ผู้ใช้ครั้งแรกเท่านั้น
define('LEGACY_USERS', [
    'admin' => [
        'hash'      => '$2y$12$jwftosN0ULktPjxLlcCmtenWpSkGA3awMYt5I968kBu8nKb.irBha',
        'name'      => 'ผู้ดูแลระบบ',
        'role'      => 'admin',
        'unit_code' => 'fleet',
    ],
    'officer1' => [
        'hash'      => '$2y$12$jwftosN0ULktPjxLlcCmtenWpSkGA3awMYt5I968kBu8nKb.irBha',
        'name'      => 'เจ้าหน้าที่ 1',
        'role'      => 'officer',
        'unit_code' => 'fleet',
    ],
]);

define('ROLE_PERMISSIONS', [
    'admin' => [
        'view:dashboard',
        'view:all_units',
        'edit:all_units',
        'manage:users',
        'manage:units',
        'manage:items',
        'manage:api',
        'view:audit',
    ],
    'officer' => [
        'view:dashboard',
        'view:own_unit',
        'edit:own_unit',
    ],
    'viewer' => [
        'view:dashboard',
        'view:own_unit',
    ],
    'integration_service' => [
        'ingest:readiness',
    ],
]);

// ===== น้ำหนักคะแนน =====
define('ROW_WEIGHTS', [
    'develop'    => 0.25,
    'efficiency' => 0.40,
    'service'    => 0.15,
    'result'     => 0.20,
]);

define('COL_WEIGHTS', [
    'personnel' => 0.20,
    'material'  => 0.50,
    'tactic'    => 0.30,
]);

// ===== รายการแต่ละช่อง =====
// รองรับได้ทั้งรูปแบบข้อความธรรมดา และ ['label' => '...', 'url' => 'https://...']
define('ITEMS', [
    'develop' => [
        'label' => 'มิติที่ 1 พัฒนาองค์กร',
        'personnel' => ['อาชีวอนามัย','Better Home Better Health',['label' => 'PQS 100-200', 'url' => 'http://localhost:8003'],'HRMISS','PMS-IT (007)'],
        'material'  => ['PMS-IT','Safety Check List','PQS 200-300','ซ่อมจำกัด/ตามระยะเวลา',['label' => 'e-SUPPOL', 'url' => 'http://localhost:8001'],'ระบบ SOAP','ระบบย่อย สสท.พอน.กร.'],
        'tactic'    => ['ชม.ฝึกตามสาขาปฏิบัติการ','ชม.การฝึกเป็นทีม','การฝึก ศยก.เรือจอด','Safe To Sail','e-SUPPOL (กิจกรรมออกเรือ)'],
    ],
    'efficiency' => [
        'label' => 'มิติที่ 2 ประสิทธิภาพ',
        'personnel' => ['ชม.ฝึกตามมาตรฐาน','PQS 300'],
        'material'  => ['ศปก.กร.','สสท.พอน.กร.','ซ่อมคืนสภาพ','IAS','Glin System','Elec System','OPMC SYSTEM'],
        'tactic'    => ['แผนเผชิญเหตุ','องค์บุคคล/ยุทธวิธีกองเรือ','Basic Tactic','หลักนิยมตามสาขา/กองเรือ'],
    ],
    'service' => [
        'label' => 'มิติที่ 3 ผู้รับบริการ',
        'personnel' => ['Shakedown','ราชการสนาม','ผู้เชี่ยวชาญสาขาปฏิบัติการทางเรือ','PQS'],
        'material'  => ['Midlife Upgrade','C4ISR','ศูนย์ปฏิบัติการเรือดำน้ำ','ศูนย์ปฏิบัติการทุ่นระเบิด','ระบบตรวจการณ์จากหน่วยเรือ','ดาวเทียมสื่อสาร','VMS'],
        'tactic'    => ['Shakedown','แลกเปลี่ยนปรับมาตรฐาน','การฝึก กร. นย. สอ.ผฝ.','ปฏิบัติการร่วม ศรชล.','ยิงแฝง/ยิงฝั้ง'],
    ],
    'result' => [
        'label' => 'มิติที่ 4 ประสิทธิผล',
        'personnel' => ['Combat Staff','PMQA (KM)'],
        'material'  => ['ระบบตรวจการณ์จากหน่วยเรือ','TDL','PMQA 4.0','RTN BMIS','Centrix','G BAD','ACCS/ASD','ILS-IT','ForceAsset-IT','New GFMIS'],
        'tactic'    => ['การฝึก ทร.','การฝึกร่วม ทท.','การฝึกผสม','Ready to Combat','HADR'],
    ],
]);

define('DEFAULT_UNIT_CODE', 'fleet');
define('DEFAULT_UNIT_NAME', 'กองเรือยุทธการ');

define('DB_DRIVER', getenv('READINESS_DB_DRIVER') ?: 'sqlite');
define('DB_PATH', getenv('READINESS_DB_PATH') ?: (__DIR__ . '/data/readiness.sqlite'));
define('DB_HOST', getenv('READINESS_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('READINESS_DB_PORT') ?: '3306');
define('DB_NAME', getenv('READINESS_DB_NAME') ?: 'readiness');
define('DB_USER', getenv('READINESS_DB_USER') ?: '');
define('DB_PASS', getenv('READINESS_DB_PASS') ?: '');
define('DB_SOCKET', getenv('READINESS_DB_SOCKET') ?: '');
define('DB_CONNECT_TIMEOUT', (int) (getenv('READINESS_DB_CONNECT_TIMEOUT') ?: 5));
define('DB_SSL_CA', getenv('READINESS_DB_SSL_CA') ?: '');

define('LEGACY_DATA_FILE', __DIR__ . '/data/readiness.json');
define('READINESS_SYNC_BACKUP_DIR', getenv('READINESS_SYNC_BACKUP_DIR') ?: (__DIR__ . '/data/backups/readiness-sync'));
define('READINESS_VISIBLE_ROWS', trim((string) (getenv('READINESS_VISIBLE_ROWS') ?: '')));
define('READINESS_CALCULATED_ROWS', trim((string) (getenv('READINESS_CALCULATED_ROWS') ?: '')));
define('READINESS_READY_THRESHOLD', is_numeric(getenv('READINESS_READY_THRESHOLD')) ? (float) getenv('READINESS_READY_THRESHOLD') : 75.0);
define('READINESS_WARNING_THRESHOLD', is_numeric(getenv('READINESS_WARNING_THRESHOLD')) ? (float) getenv('READINESS_WARNING_THRESHOLD') : 40.0);
define('SYNC_ITEMS_FROM_CONFIG', filter_var(getenv('READINESS_SYNC_ITEMS_FROM_CONFIG') ?: '0', FILTER_VALIDATE_BOOLEAN));
define('MOCK_PERCENT_SOURCE', getenv('READINESS_MOCK_PERCENT_SOURCE') ?: 'integration_mock');
define('DEFAULT_SOURCE_PRIORITY', ['manual', MOCK_PERCENT_SOURCE, 'api']);
define('MOCK_PERCENT_METRIC_MAP', [
    'support.efficiency.material.ssot_pon_kr' => ['row_id' => 'efficiency', 'col_id' => 'material', 'item_index' => 1],
]);
define('DEFAULT_API_CLIENTS', [
    [
        'name'        => 'default-ingestion-client',
        'secret'      => 'change-me-in-production',
        'client_key'  => 'default-ingestion-client',
        'unit_code'   => DEFAULT_UNIT_CODE,
        'allowed_ips' => '',
        'rate_limit'  => 60,
    ],
]);

define('SESSION_COOKIE_NAME', getenv('READINESS_SESSION_NAME') ?: 'readiness_session');
define('SESSION_IDLE_TIMEOUT', (int) (getenv('READINESS_SESSION_IDLE_TIMEOUT') ?: 3600));
define('SESSION_ABSOLUTE_TIMEOUT', (int) (getenv('READINESS_SESSION_ABSOLUTE_TIMEOUT') ?: 43200));
define('CSRF_TOKEN_TTL', (int) (getenv('READINESS_CSRF_TOKEN_TTL') ?: 7200));

define('PASSWORD_MIN_LENGTH', (int) (getenv('READINESS_PASSWORD_MIN_LENGTH') ?: 12));
define('PASSWORD_REQUIRE_UPPERCASE', filter_var(getenv('READINESS_PASSWORD_REQUIRE_UPPERCASE') ?: '1', FILTER_VALIDATE_BOOLEAN));
define('PASSWORD_REQUIRE_LOWERCASE', filter_var(getenv('READINESS_PASSWORD_REQUIRE_LOWERCASE') ?: '1', FILTER_VALIDATE_BOOLEAN));
define('PASSWORD_REQUIRE_NUMBER', filter_var(getenv('READINESS_PASSWORD_REQUIRE_NUMBER') ?: '1', FILTER_VALIDATE_BOOLEAN));
define('PASSWORD_REQUIRE_SPECIAL', filter_var(getenv('READINESS_PASSWORD_REQUIRE_SPECIAL') ?: '1', FILTER_VALIDATE_BOOLEAN));
define('PASSWORD_RESET_TTL', (int) (getenv('READINESS_PASSWORD_RESET_TTL') ?: 3600));
define('LOGIN_MAX_FAILURES', (int) (getenv('READINESS_LOGIN_MAX_FAILURES') ?: 5));
define('LOGIN_LOCKOUT_SECONDS', (int) (getenv('READINESS_LOGIN_LOCKOUT_SECONDS') ?: 900));

define('API_MAX_BODY_BYTES', (int) (getenv('READINESS_API_MAX_BODY_BYTES') ?: 65536));
define('API_DEFAULT_RATE_LIMIT_PER_MINUTE', (int) (getenv('READINESS_API_RATE_LIMIT_PER_MINUTE') ?: 60));
define('API_REQUIRE_CLIENT_KEY', filter_var(getenv('READINESS_API_REQUIRE_CLIENT_KEY') ?: '1', FILTER_VALIDATE_BOOLEAN));
define('API_ALLOWED_CLOCK_SKEW_SECONDS', (int) (getenv('READINESS_API_ALLOWED_CLOCK_SKEW_SECONDS') ?: 300));
define('API_ALLOWED_IPS', trim((string) (getenv('READINESS_API_ALLOWED_IPS') ?: '')));

if (!function_exists('readinessThemePresets')) {
    function readinessThemePresets(): array {
        $navy = [
            'page_bg' => '#f1f4f7',
            'page_bg_tv_start' => '#163a62',
            'page_bg_tv_mid' => '#0d2238',
            'page_bg_tv_end' => '#081523',
            'surface' => '#ffffff',
            'surface_alt' => '#f7fafc',
            'surface_muted' => '#eef4fa',
            'surface_muted_end' => '#dae5f0',
            'surface_soft' => '#fcfbf8',
            'surface_code' => '#f4f2ec',
            'surface_tv_row_start' => '#f1f5f9',
            'surface_tv_row_end' => '#dce5ee',
            'border' => '#d6e0ea',
            'border_soft' => '#e2e0d8',
            'border_table' => '#ece8de',
            'border_row' => '#f0ede6',
            'border_row_soft' => '#f5f5f3',
            'border_strong' => '#c5d4e3',
            'text' => '#14202b',
            'text_muted' => '#5c6773',
            'text_soft' => '#7c8794',
            'topbar_bg' => '#0f2942',
            'topbar_link' => '#c5d9ed',
            'topbar_link_hover' => '#ffffff',
            'primary' => '#1c4f80',
            'primary_hover' => '#163e65',
            'primary_text' => '#ffffff',
            'link' => '#1c4f80',
            'link_hover' => '#163e65',
            'neutral_button' => '#637381',
            'danger_button' => '#A53232',
            'navy_950' => '#081523',
            'navy_900' => '#0b2337',
            'navy_850' => '#0c2238',
            'navy_800' => '#102c49',
            'navy_700' => '#132f4e',
            'navy_650' => '#163a62',
            'navy_600' => '#224a73',
            'accent_teal' => '#5eead4',
            'accent_teal_strong' => '#2dd4bf',
            'accent_gold' => '#f7cf71',
            'accent_gold_strong' => '#d8a93a',
            'accent_slate' => '#cbd5e1',
            'accent_slate_strong' => '#94a3b8',
            'accent_sky' => '#93c5fd',
            'accent_sky_strong' => '#60a5fa',
            'success_bg' => '#EAF3DE',
            'success_text' => '#27500A',
            'success_border' => '#C0DD97',
            'warning_bg' => '#FAEEDA',
            'warning_text' => '#633806',
            'danger_bg' => '#FCEBEB',
            'danger_text' => '#791F1F',
            'info_bg' => '#E6F1FB',
            'info_border' => '#85B7EB',
            'ready_bar' => '#639922',
            'check_bar' => '#378ADD',
            'warning_bar' => '#BA7517',
            'danger_bar' => '#102c49',
            'neutral_dot' => '#d3d1c7',
            'text_inverse_soft' => '#f8fbff',
            'score_box_bg_start' => '#eef4fa',
            'score_box_bg_end' => '#dae5f0',
            'score_box_border' => '#c5d4e3',
            'score_box_highlight_start' => '#163e65',
            'score_box_highlight_end' => '#1c4f80',
        ];

        $classic = array_replace($navy, [
            'page_bg' => '#f5f5f3',
            'surface_alt' => '#f5f5f3',
            'topbar_bg' => '#0C447C',
            'topbar_link' => '#B5D4F4',
            'primary' => '#185FA5',
            'primary_hover' => '#0C447C',
            'link' => '#185FA5',
            'link_hover' => '#0C447C',
            'score_box_highlight_start' => '#0C447C',
            'score_box_highlight_end' => '#185FA5',
        ]);

        $highContrast = array_replace($navy, [
            'page_bg' => '#edf2f7',
            'surface' => '#ffffff',
            'surface_alt' => '#ffffff',
            'surface_soft' => '#f4f7fb',
            'border_soft' => '#b7c7d9',
            'border_table' => '#c7d3de',
            'border_strong' => '#91a8bf',
            'text' => '#081523',
            'text_muted' => '#1f3348',
            'text_soft' => '#445a70',
            'topbar_bg' => '#06111c',
            'topbar_link' => '#ffffff',
            'primary' => '#0b3d91',
            'primary_hover' => '#072b67',
            'link' => '#0b3d91',
            'link_hover' => '#072b67',
            'accent_teal' => '#6fffe9',
            'accent_teal_strong' => '#00d1b2',
            'accent_gold' => '#ffe082',
            'accent_gold_strong' => '#ffb300',
            'accent_slate' => '#e2e8f0',
            'accent_slate_strong' => '#94a3b8',
            'success_bg' => '#dff3da',
            'success_text' => '#143d00',
            'warning_bg' => '#fff1c4',
            'warning_text' => '#5d3b00',
            'danger_bg' => '#fde2e1',
            'danger_text' => '#6c1111',
            'info_bg' => '#dcebff',
            'info_border' => '#69a7ff',
        ]);

        return [
            'navy' => $navy,
            'classic' => $classic,
            'high-contrast' => $highContrast,
            'high_contrast' => $highContrast,
        ];
    }
}

if (!function_exists('readinessThemePresetConfig')) {
    function readinessThemePresetConfig(string $preset): array {
        $normalizedPreset = strtolower(trim($preset));
        $presets = readinessThemePresets();
        return $presets[$normalizedPreset] ?? $presets['navy'];
    }
}

define('APP_THEME_PRESET', getenv('READINESS_THEME_PRESET') ?: 'navy');
define('APP_THEME', readinessThemePresetConfig(APP_THEME_PRESET));

define('SITE_NAME', 'ระบบรายงานสถานะความพร้อม กองเรือยุทธการ');