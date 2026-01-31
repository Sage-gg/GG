<?php
/**
 * API Logger
 * Logs all API requests and responses for debugging and audit purposes
 */

/**
 * Log API request or response
 * 
 * @param string $direction 'outgoing', 'incoming', or 'error'
 * @param string $action Action identifier
 * @param array|null $request_data Request payload
 * @param array|null $response_data Response data
 */
function logAPIRequest($direction, $action, $request_data = null, $response_data = null) {
    if (!API_LOG_ENABLED) {
        return;
    }
    
    // Ensure log directory exists
    $log_dir = dirname(API_LOG_FILE);
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    // Rotate log if too large
    if (file_exists(API_LOG_FILE) && filesize(API_LOG_FILE) > API_LOG_MAX_SIZE) {
        rotateLogFile();
    }
    
    // Build log entry
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'direction' => $direction,
        'action' => $action,
        'request_data' => $request_data,
        'response_data' => $response_data,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown'
    ];
    
    // Read existing log
    $log_data = [];
    if (file_exists(API_LOG_FILE)) {
        $existing_content = file_get_contents(API_LOG_FILE);
        if (!empty($existing_content)) {
            $log_data = json_decode($existing_content, true) ?? [];
        }
    }
    
    // Add new entry
    $log_data[] = $log_entry;
    
    // Write log
    file_put_contents(API_LOG_FILE, json_encode($log_data, JSON_PRETTY_PRINT));
    
    // Also write to PHP error log if debug mode
    if (API_DEBUG_MODE) {
        error_log("API Log [{$direction}] {$action}: " . json_encode($log_entry));
    }
}

/**
 * Rotate log file when it gets too large
 */
function rotateLogFile() {
    $backup_file = API_LOG_FILE . '.' . date('Y-m-d_H-i-s') . '.backup';
    rename(API_LOG_FILE, $backup_file);
    
    // Keep only last 5 backup files
    $log_dir = dirname(API_LOG_FILE);
    $backup_files = glob($log_dir . '/*.backup');
    
    if (count($backup_files) > 5) {
        // Sort by modification time
        usort($backup_files, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        
        // Delete oldest files
        $files_to_delete = array_slice($backup_files, 0, count($backup_files) - 5);
        foreach ($files_to_delete as $file) {
            unlink($file);
        }
    }
}

/**
 * Get recent API logs
 * 
 * @param int $limit Number of recent entries to retrieve
 * @param string|null $direction Filter by direction
 * @return array
 */
function getRecentAPILogs($limit = 50, $direction = null) {
    if (!file_exists(API_LOG_FILE)) {
        return [];
    }
    
    $log_content = file_get_contents(API_LOG_FILE);
    $log_data = json_decode($log_content, true) ?? [];
    
    // Filter by direction if specified
    if ($direction) {
        $log_data = array_filter($log_data, function($entry) use ($direction) {
            return $entry['direction'] === $direction;
        });
    }
    
    // Get most recent entries
    $log_data = array_slice(array_reverse($log_data), 0, $limit);
    
    return $log_data;
}

/**
 * Get API logs by date range
 * 
 * @param string $start_date Start date (Y-m-d format)
 * @param string $end_date End date (Y-m-d format)
 * @return array
 */
function getAPILogsByDateRange($start_date, $end_date) {
    if (!file_exists(API_LOG_FILE)) {
        return [];
    }
    
    $log_content = file_get_contents(API_LOG_FILE);
    $log_data = json_decode($log_content, true) ?? [];
    
    $start_timestamp = strtotime($start_date . ' 00:00:00');
    $end_timestamp = strtotime($end_date . ' 23:59:59');
    
    return array_filter($log_data, function($entry) use ($start_timestamp, $end_timestamp) {
        $entry_timestamp = strtotime($entry['timestamp']);
        return $entry_timestamp >= $start_timestamp && $entry_timestamp <= $end_timestamp;
    });
}

/**
 * Get API statistics
 * 
 * @return array
 */
function getAPIStatistics() {
    if (!file_exists(API_LOG_FILE)) {
        return [
            'total_requests' => 0,
            'successful' => 0,
            'failed' => 0,
            'error_rate' => 0
        ];
    }
    
    $log_content = file_get_contents(API_LOG_FILE);
    $log_data = json_decode($log_content, true) ?? [];
    
    $stats = [
        'total_requests' => 0,
        'outgoing' => 0,
        'incoming' => 0,
        'errors' => 0,
        'successful' => 0,
        'failed' => 0,
        'by_action' => [],
        'by_date' => []
    ];
    
    foreach ($log_data as $entry) {
        $stats['total_requests']++;
        
        // Count by direction
        if (isset($entry['direction'])) {
            $direction = $entry['direction'];
            if ($direction === 'outgoing') {
                $stats['outgoing']++;
            } elseif ($direction === 'incoming') {
                $stats['incoming']++;
            } elseif ($direction === 'error') {
                $stats['errors']++;
            }
        }
        
        // Count by success/failure
        if (isset($entry['response_data']['success'])) {
            if ($entry['response_data']['success']) {
                $stats['successful']++;
            } else {
                $stats['failed']++;
            }
        }
        
        // Count by action
        if (isset($entry['action'])) {
            $action = $entry['action'];
            if (!isset($stats['by_action'][$action])) {
                $stats['by_action'][$action] = 0;
            }
            $stats['by_action'][$action]++;
        }
        
        // Count by date
        if (isset($entry['timestamp'])) {
            $date = date('Y-m-d', strtotime($entry['timestamp']));
            if (!isset($stats['by_date'][$date])) {
                $stats['by_date'][$date] = 0;
            }
            $stats['by_date'][$date]++;
        }
    }
    
    // Calculate error rate
    if ($stats['total_requests'] > 0) {
        $stats['error_rate'] = round(($stats['failed'] + $stats['errors']) / $stats['total_requests'] * 100, 2);
    } else {
        $stats['error_rate'] = 0;
    }
    
    return $stats;
}

/**
 * Clear API logs
 * 
 * @param bool $create_backup Create backup before clearing
 */
function clearAPILogs($create_backup = true) {
    if (!file_exists(API_LOG_FILE)) {
        return;
    }
    
    if ($create_backup) {
        $backup_file = API_LOG_FILE . '.' . date('Y-m-d_H-i-s') . '.cleared';
        copy(API_LOG_FILE, $backup_file);
    }
    
    file_put_contents(API_LOG_FILE, '[]');
}

/**
 * Export logs to CSV
 * 
 * @param string|null $output_file Output file path
 * @return string Path to exported file
 */
function exportLogsToCSV($output_file = null) {
    if (!file_exists(API_LOG_FILE)) {
        return null;
    }
    
    if ($output_file === null) {
        $output_file = dirname(API_LOG_FILE) . '/api_logs_' . date('Y-m-d_H-i-s') . '.csv';
    }
    
    $log_content = file_get_contents(API_LOG_FILE);
    $log_data = json_decode($log_content, true) ?? [];
    
    $fp = fopen($output_file, 'w');
    
    // Write headers
    fputcsv($fp, ['Timestamp', 'Direction', 'Action', 'Success', 'Message', 'IP Address']);
    
    // Write data
    foreach ($log_data as $entry) {
        $success = '';
        $message = '';
        
        if (isset($entry['response_data']['success'])) {
            $success = $entry['response_data']['success'] ? 'Yes' : 'No';
        }
        
        if (isset($entry['response_data']['message'])) {
            $message = $entry['response_data']['message'];
        }
        
        fputcsv($fp, [
            $entry['timestamp'] ?? '',
            $entry['direction'] ?? '',
            $entry['action'] ?? '',
            $success,
            $message,
            $entry['ip_address'] ?? ''
        ]);
    }
    
    fclose($fp);
    
    return $output_file;
}
