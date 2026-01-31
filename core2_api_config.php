<?php
/**
 * API Configuration (NO SECURITY)
 * All security features removed for easy integration
 */

// Core 2 API Configuration
define('CORE2_API_URL', 'https://core2.cranecali-ms.com/api/insert_asset.php');
define('API_TIMEOUT', 30);

// API Sync Settings
define('AUTO_SYNC_ENABLED', true);
define('SYNC_ON_APPROVAL', true);
define('SYNC_RETRY_ATTEMPTS', 3);
define('SYNC_RETRY_DELAY', 5);

// Logging Settings
define('API_LOG_ENABLED', true);
define('API_LOG_FILE', __DIR__ . '/logs/api_log.json');
define('API_LOG_MAX_SIZE', 5242880);

// NO SECURITY - Webhook without authentication
define('WEBHOOK_ENABLED', true);

// NO SECURITY - Debug mode
define('API_DEBUG_MODE', false);

// Department Mapping
$DEPARTMENT_LOCATION_MAP = [
    'HR' => 'HR Department Building',
    'HR2' => 'HR Division 2',
    'HR4' => 'HR Division 4',
    'Core' => 'Core Operations Center',
    'Core 2' => 'Core Division 2',
    'Core 4' => 'Core Division 4'
];

// Cost Center Mapping
$COST_CENTER_ASSET_MAP = [
    'Training Budget' => [
        'asset_type' => 'Training Asset',
        'location' => 'HR Training Facility',
        'category' => 'Human Resources'
    ],
    'Reimbursement Budget' => [
        'asset_type' => 'Administrative Asset',
        'location' => 'HR Administration',
        'category' => 'Human Resources'
    ],
    'Benefits Budget' => [
        'asset_type' => 'Benefits Asset',
        'location' => 'HR Benefits Office',
        'category' => 'Human Resources'
    ],
    'Payroll Budget' => [
        'asset_type' => 'Payroll Asset',
        'location' => 'HR Payroll Division',
        'category' => 'Human Resources'
    ],
    'Log Maintenance Costs' => [
        'asset_type' => 'Maintenance Crane',
        'location' => 'Maintenance Yard',
        'category' => 'Fleet Management'
    ],
    'Depreciation Charges' => [
        'asset_type' => 'Depreciation Asset',
        'location' => 'Asset Registry',
        'category' => 'Asset Management'
    ],
    'Insurance Fees' => [
        'asset_type' => 'Insured Crane',
        'location' => 'Insurance Division',
        'category' => 'Risk Management'
    ],
    'Vehicle Operational Budget' => [
        'asset_type' => 'Operational Crane',
        'location' => 'Operations Center',
        'category' => 'Fleet Operations'
    ]
];

// Status Mapping
$STATUS_MAP = [
    'Pending' => 'Under Maintenance',
    'Approved' => 'Active',
    'Rejected' => 'Inactive'
];

// Period Mapping
$PERIOD_FREQUENCY_MAP = [
    'Daily' => 'Daily',
    'Bi-weekly' => 'Bi-weekly',
    'Monthly' => 'Monthly',
    'Annually' => 'Annually'
];

// Helper Functions
function getDepartmentLocation($department) {
    global $DEPARTMENT_LOCATION_MAP;
    return $DEPARTMENT_LOCATION_MAP[$department] ?? $department . ' Department';
}

function getAssetMapping($cost_center) {
    global $COST_CENTER_ASSET_MAP;
    if (isset($COST_CENTER_ASSET_MAP[$cost_center])) {
        return $COST_CENTER_ASSET_MAP[$cost_center];
    }
    return ['asset_type' => 'Crane', 'location' => 'Default Location', 'category' => 'General'];
}

function getAssetStatus($approval_status) {
    global $STATUS_MAP;
    return $STATUS_MAP[$approval_status] ?? 'Active';
}

function getBudgetFrequency($period) {
    global $PERIOD_FREQUENCY_MAP;
    return $PERIOD_FREQUENCY_MAP[$period] ?? 'Monthly';
}

function validateAPIConfig() {
    $errors = [];
    if (empty(CORE2_API_URL)) $errors[] = 'CORE2_API_URL is not configured';
    if (!filter_var(CORE2_API_URL, FILTER_VALIDATE_URL)) $errors[] = 'CORE2_API_URL is not a valid URL';
    if (API_LOG_ENABLED && !is_writable(dirname(API_LOG_FILE))) $errors[] = 'API log directory is not writable';
    return $errors;
}

function getAPIConfigSummary() {
    return [
        'core2_url' => CORE2_API_URL,
        'auto_sync' => AUTO_SYNC_ENABLED,
        'sync_on_approval' => SYNC_ON_APPROVAL,
        'retry_attempts' => SYNC_RETRY_ATTEMPTS,
        'logging_enabled' => API_LOG_ENABLED,
        'webhook_enabled' => WEBHOOK_ENABLED,
        'debug_mode' => API_DEBUG_MODE,
        'security' => 'DISABLED - No authentication required'
    ];
}
