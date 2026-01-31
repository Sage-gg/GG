<?php
/**
 * Core 2 API Configuration
 */

// Enable/disable automatic sync to Core 2
define('AUTO_SYNC_ENABLED', false);

// Sync only when budget is approved
define('SYNC_ON_APPROVAL', true);

// API endpoint configuration
define('CORE2_API_URL', 'https://your-core2-system.com/api/budgets');
define('CORE2_API_KEY', 'your-api-key-here');
define('CORE2_API_SECRET', 'your-api-secret-here');

// Sync retry settings
define('SYNC_RETRY_ATTEMPTS', 3);
define('SYNC_RETRY_DELAY', 2);

// API timeout
define('API_TIMEOUT', 30);

// Debug mode
define('SYNC_DEBUG_MODE', false);
