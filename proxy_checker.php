<?php
/**
 * Background Proxy Checker
 * Run this via cron job or manually to auto-check proxies
 */

// Include the main API functions
require_once __DIR__ . '/admin_api.php';

// Override to run in CLI mode
if (php_sapi_name() === 'cli') {
    echo "Starting proxy health check...\n";
    $result = auto_check_all_proxies();
    echo "Checked: {$result['checked']} proxies\n";
    echo "Valid: {$result['valid']}\n";
    echo "Invalid: {$result['invalid']}\n";
    echo "Removed: {$result['removed']}\n";
    echo "Total proxies remaining: {$result['total_proxies']}\n";
    
    // Log to file
    $log_file = __DIR__ . '/admin_data/proxy_check_log.txt';
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - " . json_encode($result) . "\n", FILE_APPEND);
} else {
    // Web access - require admin auth
    session_start();
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        die(json_encode(['success' => false, 'error' => 'Unauthorized']));
    }
    
    echo json_encode(auto_check_all_proxies());
}
?>