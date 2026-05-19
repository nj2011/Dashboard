<?php

// ===== RAILWAY PERSISTENT STORAGE FIX =====
// Try multiple possible storage locations
$possible_paths = [];

// 1. Railway volume mount (if configured)
$railway_volume = getenv('RAILWAY_VOLUME_MOUNT_PATH');
if ($railway_volume) {
    $possible_paths[] = $railway_volume . '/admin_data';
}

// 2. Railway default volume path
$possible_paths[] = '/app/data/admin_data';

// 3. Local directory (fallback)
$possible_paths[] = __DIR__ . '/admin_data';

// Find first writable path
$data_dir = null;
foreach ($possible_paths as $path) {
    if (file_exists($path) || @mkdir($path, 0755, true)) {
        $data_dir = $path;
        break;
    }
}

// Final fallback - use sys_get_temp_dir() if all else fails
if (!$data_dir) {
    $data_dir = sys_get_temp_dir() . '/hyperion_admin_data';
    @mkdir($data_dir, 0755, true);
}

// Log the data directory being used (for debugging)
error_log("Using data directory: " . $data_dir);

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('precision', 16);
date_default_timezone_set('UTC');

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) return false;
    $json = json_encode(['success' => false, 'error' => 'Internal server error']);
    if (!headers_sent()) {
        header('Content-Type: application/json');
        echo $json;
    }
    exit(1);
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $json = json_encode(['success' => false, 'error' => 'Internal server error']);
        if (!headers_sent()) {
            header('Content-Type: application/json');
            echo $json;
        }
    }
});

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Cache-Control: no-store, no-cache, must-revalidate');

ignore_user_abort(true);
set_time_limit(60);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (file_exists(__DIR__ . '/telegram_bot.php')) {
    require_once __DIR__ . '/telegram_bot.php';
} else {
    function send_telegram_message($msg) { return true; }
    function get_full_geolocation($ip) { return ['country' => 'Unknown', 'city' => 'Unknown']; }
    function send_anti_leak_alert($a,$b,$c,$d,$e,$f,$g,$h=[]) {}
    function send_new_user_notification($a,$b,$c,$d,$e,$f,$g=[]) {}
    function send_admin_login_alert($a,$b) {}
    function send_key_blacklisted_alert($a,$b) {}
    function send_tamper_alert($a,$b,$c,$d,$e,$f,$g) {}
    function send_device_limit_alert($a,$b,$c,$d,$e,$f,$g,$h='') {}
    function send_quota_limit_alert($a,$b,$c,$d,$e) {}
}

define('ENCRYPTION_KEY', '50ffe1072d95c29a7b62738de60bbde769d4631c37dcc8c93fc9e34fbe010e0a');
define('RATE_LIMIT_MAX_ATTEMPTS', 30);
define('RATE_LIMIT_TIMEFRAME', 60);
define('LOADER_VERSION', '7.1.0-SECURE');
define('ORIGINAL_LOADER_HASH', '267a2751ce1ea09fcbff5a85bbead7bac25f2f44410c42e3f8d2b491278cef73');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900);
define('SESSION_LIFETIME', 3600);
define('MAX_DEVICES_PER_KEY', 8);
define('BACKUP_MAX_FILES', 50);
define('SHARED_SECRET', '4_1_131_231_516_616_744_120_103_956_275_158_533_1441_256_119_-204_1097_0_339_0_1008');

// Use the DATA_DIR constant from above
$data_dir = DATA_DIR;
if (!file_exists($data_dir)) mkdir($data_dir, 0755, true);

$session_path = $data_dir . '/sessions';
if (!file_exists($session_path)) {
    mkdir($session_path, 0755, true);
}

session_save_path($session_path);

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', 3600);

session_start();

if (!isset($_SESSION['regenerated'])) {
    session_regenerate_id(true);
    $_SESSION['regenerated'] = true;
}

$keys_file = $data_dir . '/api_keys.json';
$devices_file = $data_dir . '/devices.json';
$logs_file = $data_dir . '/logs.json';
$settings_file = $data_dir . '/settings.json';
$proxies_file = $data_dir . '/proxies.json';
$leaks_file = $data_dir . '/leaks.json';
$first_time_users_file = $data_dir . '/first_time_users.json';
$allowed_devices_file = $data_dir . '/allowed_devices.json';
$hwid_blacklist_file = $data_dir . '/hwid_blacklist.json';
$version_blacklist_file = $data_dir . '/version_blacklist.json';
$ip_blacklist_file = $data_dir . '/ip_blacklist.json';
$admin_config_file = $data_dir . '/admin_config.json';
$rate_limit_file = $data_dir . '/rate_limits.json';
$login_attempts_file = $data_dir . '/login_attempts.json';
$suspicious_ips_file = $data_dir . '/suspicious_ips.json';
$heartbeats_file = $data_dir . '/heartbeats.json';
$proxy_stats_file = $data_dir . '/proxy_stats.json';
$nonces_file = $data_dir . '/used_nonces.json';
$proxy_config_file = $data_dir . '/proxy_config.json';
$active_users_file = $data_dir . '/active_users.json';
$active_users_history_file = $data_dir . '/active_users_history.json';
$hwid_sticky_file = $data_dir . '/hwid_sticky_binding.json';
$session_tokens_file = $data_dir . '/session_tokens.json';
$public_key_file = $data_dir . '/public_key.pem';
$private_key_file = $data_dir . '/private_key.pem';
$hwid_salt_file = $data_dir . '/hwid_salt_rotation.json';
$registry_licenses_file = $data_dir . '/registry_licenses.json';
$hardware_tokens_file = $data_dir . '/hardware_tokens.json';
$challenge_file = $data_dir . '/challenges.json';

function atomic_read_write($file, $callback) {
    $fp = fopen($file, 'c+');
    if (!$fp) {
        return ['success' => false, 'error' => 'Could not open file'];
    }
    
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return ['success' => false, 'error' => 'Could not acquire lock'];
    }
    
    $content = '';
    while (!feof($fp)) {
        $content .= fread($fp, 8192);
    }
    $data = json_decode($content, true);
    if (!$data) {
        $data = [];
    }
    
    $result = $callback($data);
    
    if ($result['modified'] ?? false) {
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($result['data'], JSON_PRETTY_PRINT));
        fflush($fp);
    }
    
    flock($fp, LOCK_UN);
    fclose($fp);
    
    return $result;
}

function initialize_default_files() {
    global $keys_file, $devices_file, $logs_file, $settings_file, $proxies_file;
    global $leaks_file, $first_time_users_file, $allowed_devices_file, $hwid_blacklist_file;
    global $version_blacklist_file, $ip_blacklist_file, $admin_config_file;
    global $active_users_file, $active_users_history_file, $proxy_config_file, $heartbeats_file;
    global $hwid_sticky_file, $session_tokens_file, $hwid_salt_file, $registry_licenses_file, $hardware_tokens_file, $challenge_file;
    
    $default_files = [
        $keys_file => ['keys' => [], 'next_id' => 1],
        $devices_file => ['devices' => []],
        $logs_file => ['logs' => []],
        $settings_file => [
            'kill_switch' => false, 'kill_switch_reason' => '',
            'force_maintenance' => false, 'force_maintenance_reason' => '',
            'maintenance_mode' => false, 'maintenance_message' => 'Under maintenance',
            'current_loader_version' => '7.1.0-SECURE', 'current_loader_hash' => ORIGINAL_LOADER_HASH,
            'max_devices_per_key' => 8, 'anti_leak_enabled' => true,
            'integrity_check_enabled' => true, 'min_allowed_version' => '1.0',
            'whitelist_bypass_kill_switch' => true, 'whitelist_bypass_all' => false,
            'require_hardware_token' => true, 'hwid_salt_rotation' => true
        ],
        $proxies_file => ['proxies' => [], 'next_id' => 1],
        $leaks_file => ['leaks' => []],
        $first_time_users_file => ['users' => []],
        $allowed_devices_file => ['devices' => [], 'next_id' => 1],
        $hwid_blacklist_file => ['blacklisted_hwids' => []],
        $version_blacklist_file => ['blacklisted_versions' => []],
        $ip_blacklist_file => [],
        $active_users_file => [],
        $active_users_history_file => [],
        $heartbeats_file => [],
        $proxy_config_file => ['enabled' => false, 'type' => 'HTTP', 'host' => '', 'port' => '', 'user' => '', 'pass' => ''],
        $hwid_sticky_file => [],
        $session_tokens_file => [],
        $hwid_salt_file => ['salts' => []],
        $registry_licenses_file => [],
        $hardware_tokens_file => ['tokens' => []],
        $challenge_file => []
    ];
    
    foreach ($default_files as $file => $default_data) {
        if (!file_exists($file)) {
            file_put_contents($file, json_encode($default_data, JSON_PRETTY_PRINT));
        }
    }
    
    if (!file_exists($admin_config_file)) {
        file_put_contents($admin_config_file, json_encode([
            'username' => 'admin',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'created_at' => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT));
    }
}
initialize_default_files();

function get_client_ip() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    if (isset($_SERVER['HTTP_X_REAL_IP'])) $ip = $_SERVER['HTTP_X_REAL_IP'];
    return trim($ip);
}

function log_action($type, $message) {
    global $logs_file;
    $data = file_exists($logs_file) ? json_decode(file_get_contents($logs_file), true) : ['logs' => []];
    $data['logs'][] = ['time' => date('Y-m-d H:i:s'), 'type' => $type, 'message' => $message, 'ip' => get_client_ip()];
    if (count($data['logs']) > 1000) $data['logs'] = array_slice($data['logs'], -1000);
    file_put_contents($logs_file, json_encode($data));
}

function get_settings() {
    global $settings_file;
    $default = [
        'kill_switch' => false, 'kill_switch_reason' => '',
        'force_maintenance' => false, 'force_maintenance_reason' => '',
        'maintenance_mode' => false, 'maintenance_message' => 'Under maintenance',
        'current_loader_version' => '7.1.0-SECURE', 'current_loader_hash' => ORIGINAL_LOADER_HASH,
        'max_devices_per_key' => 8, 'anti_leak_enabled' => true,
        'integrity_check_enabled' => true, 'min_allowed_version' => '1.0',
        'whitelist_bypass_kill_switch' => true, 'whitelist_bypass_all' => false,
        'require_hardware_token' => true, 'hwid_salt_rotation' => true
    ];
    if (file_exists($settings_file)) {
        $data = json_decode(file_get_contents($settings_file), true);
        if ($data) return array_merge($default, $data);
    }
    return $default;
}

function format_file_size($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}

function validate_hwid_format($hwid) {
    if (strlen($hwid) == 128 && preg_match('/^[a-f0-9]{128}$/', $hwid)) return true;
    if (strlen($hwid) == 64 && preg_match('/^[a-f0-9]{64}$/', $hwid)) return true;
    return false;
}

function verify_hwid_salt($hwid, $license_key) {
    global $hwid_salt_file;
    $settings = get_settings();
    if (!$settings['hwid_salt_rotation']) return true;
    
    $current_week = date('W');
    $current_month = date('n');
    
    $data = file_exists($hwid_salt_file) ? json_decode(file_get_contents($hwid_salt_file), true) : [];
    $key_hash = md5($license_key);
    
    if (!isset($data[$key_hash])) {
        $data[$key_hash] = [
            'salt_week' => $current_week,
            'salt_month' => $current_month,
            'created_at' => date('Y-m-d H:i:s')
        ];
        file_put_contents($hwid_salt_file, json_encode($data));
        return true;
    }
    
    if ($data[$key_hash]['salt_week'] != $current_week && $data[$key_hash]['salt_month'] != $current_month) {
        log_action('SALT_MISMATCH', "Salt rotation mismatch for license: $license_key");
        return false;
    }
    
    return true;
}

function validate_registry_license($license_key, $hwid) {
    global $registry_licenses_file;
    $data = file_exists($registry_licenses_file) ? json_decode(file_get_contents($registry_licenses_file), true) : [];
    $key_hash = md5($license_key);
    
    if (!isset($data[$key_hash])) return true;
    
    if ($data[$key_hash]['hwid'] !== $hwid) {
        log_action('REGISTRY_MISMATCH', "Registry license HWID mismatch for: $license_key");
        return false;
    }
    
    $data[$key_hash]['last_validated'] = date('Y-m-d H:i:s');
    file_put_contents($registry_licenses_file, json_encode($data));
    return true;
}

function register_registry_license($license_key, $hwid, $device_name) {
    global $registry_licenses_file;
    $data = file_exists($registry_licenses_file) ? json_decode(file_get_contents($registry_licenses_file), true) : [];
    $key_hash = md5($license_key);
    
    if (!isset($data[$key_hash])) {
        $data[$key_hash] = [
            'hwid' => $hwid,
            'device_name' => $device_name,
            'registered_at' => date('Y-m-d H:i:s'),
            'ip' => get_client_ip(),
            'validation_count' => 1
        ];
        file_put_contents($registry_licenses_file, json_encode($data));
        log_action('REGISTRY_REGISTERED', "License registered in registry: $license_key");
    }
    return true;
}

function validate_hardware_token($token_hwid, $submitted_hwid = '') {
    global $hardware_tokens_file;
    $settings = get_settings();
    
    // Debug logging
    error_log("=== HARDWARE TOKEN DEBUG ===");
    error_log("Token HWID received: " . $token_hwid);
    error_log("Submitted HWID: " . $submitted_hwid);
    error_log("Require hardware token: " . ($settings['require_hardware_token'] ? 'TRUE' : 'FALSE'));
    
    if (!$settings['require_hardware_token']) {
        error_log("Hardware token not required - returning true");
        return true;
    }
    
    if (!empty($submitted_hwid)) {
        $expected = hash('sha256', $submitted_hwid . SHARED_SECRET);
        error_log("Expected token: " . $expected);
        error_log("Token matches expected: " . ($token_hwid === $expected ? 'YES' : 'NO'));
        
        if ($token_hwid === $expected) {
            $data = file_exists($hardware_tokens_file) ? json_decode(file_get_contents($hardware_tokens_file), true) : ['tokens' => []];
            $already_registered = false;
            foreach ($data['tokens'] as $token) {
                if ($token['token_hwid'] === $token_hwid) {
                    $already_registered = true;
                    break;
                }
            }
            if (!$already_registered) {
                $data['tokens'][] = [
                    'token_hwid' => $token_hwid,
                    'label' => 'Auto-registered Device',
                    'active' => true,
                    'registered_at' => date('Y-m-d H:i:s'),
                    'registered_by' => 'auto',
                    'last_used' => date('Y-m-d H:i:s')
                ];
                file_put_contents($hardware_tokens_file, json_encode($data));
                log_action('TOKEN_AUTO_REGISTERED', "Hardware token auto-registered: $token_hwid");
                error_log("Token auto-registered successfully");
            }
            return true;
        }
    }
    
    $data = file_exists($hardware_tokens_file) ? json_decode(file_get_contents($hardware_tokens_file), true) : ['tokens' => []];
    
    foreach ($data['tokens'] as &$token) {
        if ($token['token_hwid'] === $token_hwid && $token['active']) {
            $token['last_used'] = date('Y-m-d H:i:s');
            file_put_contents($hardware_tokens_file, json_encode($data));
            error_log("Token found in database - valid");
            return true;
        }
    }
    
    error_log("HARDWARE TOKEN FAIL - Invalid token: $token_hwid");
    log_action('HARDWARE_TOKEN_FAIL', "Invalid hardware token: $token_hwid");
    return false;
}

function register_hardware_token($token_hwid, $label) {
    global $hardware_tokens_file;
    $data = file_exists($hardware_tokens_file) ? json_decode(file_get_contents($hardware_tokens_file), true) : ['tokens' => []];
    
    foreach ($data['tokens'] as $token) {
        if ($token['token_hwid'] === $token_hwid) {
            return ['success' => false, 'error' => 'Token already registered'];
        }
    }
    
    $data['tokens'][] = [
        'token_hwid' => $token_hwid,
        'label' => $label,
        'active' => true,
        'registered_at' => date('Y-m-d H:i:s'),
        'registered_by' => $_SESSION['admin_username'] ?? 'admin',
        'last_used' => null
    ];
    
    file_put_contents($hardware_tokens_file, json_encode($data));
    log_action('TOKEN_REGISTERED', "Hardware token registered: $token_hwid");
    return ['success' => true];
}

function generate_hwid_signing_keys() {
    global $public_key_file, $private_key_file;
    
    if (file_exists($public_key_file) && file_exists($private_key_file)) {
        return ['success' => true, 'message' => 'Keys already exist', 'public_key' => file_get_contents($public_key_file)];
    }
    
    $config = array(
        "digest_alg" => "sha256",
        "private_key_bits" => 2048,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    );
    
    $private_key = openssl_pkey_new($config);
    openssl_pkey_export($private_key, $private_key_pem);
    $public_key_details = openssl_pkey_get_details($private_key);
    $public_key_pem = $public_key_details['key'];
    
    file_put_contents($private_key_file, $private_key_pem);
    file_put_contents($public_key_file, $public_key_pem);
    log_action('KEYS_GENERATED', "HWID signing keys created");
    return ['success' => true, 'public_key' => $public_key_pem];
}

function verify_hwid_signature($hwid, $signature) {
    global $public_key_file;
    if (!file_exists($public_key_file)) generate_hwid_signing_keys();
    
    $public_key = openssl_pkey_get_public(file_get_contents($public_key_file));
    if (!$public_key) return false;
    
    $decoded_signature = base64_decode($signature);
    $verified = openssl_verify($hwid, $decoded_signature, $public_key, OPENSSL_ALGO_SHA256);
    return $verified === 1;
}

function check_sticky_binding($key, $hwid) {
    global $hwid_sticky_file;
    $sticky_data = file_exists($hwid_sticky_file) ? json_decode(file_get_contents($hwid_sticky_file), true) : [];
    $key_hash = md5($key['api_key']);
    $hwid_short = strlen($hwid) == 128 ? substr($hwid, 0, 64) : $hwid;
    
    if (!isset($sticky_data[$key_hash])) {
        $sticky_data[$key_hash] = [
            'bound_hwid' => $hwid_short,
            'bound_hwid_full' => $hwid,
            'bound_at' => date('Y-m-d H:i:s'),
            'bound_ip' => get_client_ip(),
            'validation_count' => 1,
            'license_label' => $key['label']
        ];
        file_put_contents($hwid_sticky_file, json_encode($sticky_data, JSON_PRETTY_PRINT));
        return ['allowed' => true, 'is_first_bind' => true];
    }
    
    $stored_hwid = $sticky_data[$key_hash]['bound_hwid'];
    if ($stored_hwid !== $hwid_short && $stored_hwid !== $hwid) {
        log_action('STICKY_BINDING_VIOLATION', "License {$key['api_key']} attempted to bind to new HWID");
        if (function_exists('send_telegram_message')) {
            send_telegram_message("STICKY BINDING VIOLATION\nKey: {$key['api_key']}\nLabel: {$key['label']}\nIP: " . get_client_ip());
        }
        return ['allowed' => false, 'error' => 'License is permanently bound to a different device. HWID mismatch.', 'expected_hwid' => $stored_hwid];
    }
    
    $sticky_data[$key_hash]['validation_count']++;
    $sticky_data[$key_hash]['last_validated'] = date('Y-m-d H:i:s');
    file_put_contents($hwid_sticky_file, json_encode($sticky_data, JSON_PRETTY_PRINT));
    return ['allowed' => true, 'is_first_bind' => false];
}

function generate_session_token($license_key, $hwid, $timestamp, $nonce) {
    $data = $license_key . $hwid . $timestamp . $nonce;
    return hash('sha256', $data);
}

function validate_session_token($license_key, $hwid, $timestamp, $nonce, $provided_token) {
    global $session_tokens_file;
    $expected = generate_session_token($license_key, $hwid, $timestamp, $nonce);
    
    if (!hash_equals($expected, $provided_token)) {
        log_action('SESSION_TOKEN_FAIL', "Invalid session token");
        return false;
    }
    
    $used_tokens = file_exists($session_tokens_file) ? json_decode(file_get_contents($session_tokens_file), true) : [];
    $token_hash = md5($provided_token);
    
    if (isset($used_tokens[$token_hash]) && $used_tokens[$token_hash] > time() - 300) {
        log_action('SESSION_TOKEN_REPLAY', "Session token replay detected");
        return false;
    }
    
    $used_tokens[$token_hash] = time();
    foreach ($used_tokens as $key => $ts) {
        if ($ts < time() - 300) unset($used_tokens[$key]);
    }
    file_put_contents($session_tokens_file, json_encode($used_tokens));
    return true;
}

function check_enhanced_rate_limit($hwid, $license_key) {
    global $rate_limit_file;
    $data = file_exists($rate_limit_file) ? json_decode(file_get_contents($rate_limit_file), true) : [];
    $now = time();
    $hwid_key = "hwid_" . md5($hwid);
    $license_key_hash = "license_" . md5($license_key);
    
    if (!isset($data[$hwid_key])) $data[$hwid_key] = ['attempts' => [], 'blocked_until' => 0];
    if (!isset($data[$license_key_hash])) $data[$license_key_hash] = ['attempts' => [], 'blocked_until' => 0];
    
    if ($data[$hwid_key]['blocked_until'] > $now) {
        return ['allowed' => false, 'wait_seconds' => $data[$hwid_key]['blocked_until'] - $now, 'reason' => 'HWID rate limited'];
    }
    if ($data[$license_key_hash]['blocked_until'] > $now) {
        return ['allowed' => false, 'wait_seconds' => $data[$license_key_hash]['blocked_until'] - $now, 'reason' => 'License rate limited'];
    }
    
    $data[$hwid_key]['attempts'] = array_filter($data[$hwid_key]['attempts'], fn($t) => $t > $now - 60);
    $data[$license_key_hash]['attempts'] = array_filter($data[$license_key_hash]['attempts'], fn($t) => $t > $now - 60);
    
    if (count($data[$hwid_key]['attempts']) >= 10) {
        $data[$hwid_key]['blocked_until'] = $now + 300;
        file_put_contents($rate_limit_file, json_encode($data));
        return ['allowed' => false, 'wait_seconds' => 300, 'reason' => 'Too many attempts from this HWID'];
    }
    
    if (count($data[$license_key_hash]['attempts']) >= 30) {
        $data[$license_key_hash]['blocked_until'] = $now + 300;
        file_put_contents($rate_limit_file, json_encode($data));
        return ['allowed' => false, 'wait_seconds' => 300, 'reason' => 'Too many attempts for this license'];
    }
    
    $data[$hwid_key]['attempts'][] = $now;
    $data[$license_key_hash]['attempts'][] = $now;
    file_put_contents($rate_limit_file, json_encode($data));
    return ['allowed' => true];
}

function check_second_rate_limit($hwid) {
    global $rate_limit_file;
    $data = file_exists($rate_limit_file) ? json_decode(file_get_contents($rate_limit_file), true) : [];
    $now = time();
    $second_key = "second_" . md5($hwid) . "_" . $now;
    
    $count = $data[$second_key] ?? 0;
    if ($count >= 2) {
        return ['allowed' => false, 'message' => 'Too many requests this second'];
    }
    
    $data[$second_key] = $count + 1;
    foreach ($data as $key => $ts) {
        if (strpos($key, 'second_') === 0 && is_numeric($ts)) {
            $key_time = (int)substr($key, strrpos($key, '_') + 1);
            if ($key_time < $now - 1) unset($data[$key]);
        }
    }
    file_put_contents($rate_limit_file, json_encode($data));
    return ['allowed' => true];
}

function verify_anti_replay($api_key, $hwid, $username, $timestamp, $nonce, $signature) {
    global $nonces_file;
    $current_time = time();
    if (abs($current_time - $timestamp) > 300) {
        return ['success' => false, 'error' => 'Request expired'];
    }
    
    $nonce_data = file_exists($nonces_file) ? json_decode(file_get_contents($nonces_file), true) : [];
    $nonce_key = md5($nonce);
    if (isset($nonce_data[$nonce_key]) && $nonce_data[$nonce_key] > $current_time - 300) {
        return ['success' => false, 'error' => 'Replay attack detected'];
    }
    
    $message = $api_key . $hwid . $username . $timestamp . $nonce;
    $expected = hash_hmac('sha256', $message, SHARED_SECRET);
    
    if (!hash_equals($expected, $signature)) {
        return ['success' => false, 'error' => 'Invalid signature'];
    }
    
    $nonce_data[$nonce_key] = $current_time;
    if (count($nonce_data) > 10000) $nonce_data = array_slice($nonce_data, -5000);
    file_put_contents($nonces_file, json_encode($nonce_data));
    return ['success' => true];
}

function get_kill_switch_status() {
    $settings = get_settings();
    return ['success' => true, 'kill_switch' => $settings['kill_switch'] ?? false, 'reason' => $settings['kill_switch_reason'] ?? ''];
}

function activate_kill_switch($reason) {
    global $settings_file;
    $settings = get_settings();
    $settings['kill_switch'] = true;
    $settings['kill_switch_reason'] = $reason;
    file_put_contents($settings_file, json_encode($settings));
    log_action('KILL_SWITCH', "ACTIVATED: $reason");
    if (function_exists('send_telegram_message')) {
        send_telegram_message("KILL SWITCH ACTIVATED\nReason: $reason\nIP: " . get_client_ip());
    }
    return ['success' => true];
}

function deactivate_kill_switch() {
    global $settings_file;
    $settings = get_settings();
    $settings['kill_switch'] = false;
    $settings['kill_switch_reason'] = '';
    file_put_contents($settings_file, json_encode($settings));
    log_action('KILL_SWITCH', "DEACTIVATED");
    if (function_exists('send_telegram_message')) {
        send_telegram_message("KILL SWITCH DEACTIVATED\nIP: " . get_client_ip());
    }
    return ['success' => true];
}

function update_active_user($license_key, $hwid, $device_name, $platform, $username = '') {
    global $active_users_file, $active_users_history_file;
    $now = time();
    $data = file_exists($active_users_file) ? json_decode(file_get_contents($active_users_file), true) : [];
    if (!is_array($data)) $data = [];
    
    foreach ($data as $key => $user) {
        if ($now - ($user['last_seen_timestamp'] ?? 0) > 300) unset($data[$key]);
    }
    
    $user_key = md5($license_key . $hwid);
    $is_new = !isset($data[$user_key]);
    
    $data[$user_key] = [
        'license_key' => $license_key,
        'hwid' => substr($hwid, 0, 32) . '...',
        'device_name' => $device_name,
        'platform' => $platform,
        'username' => $username,
        'first_seen' => isset($data[$user_key]) ? $data[$user_key]['first_seen'] : date('Y-m-d H:i:s'),
        'last_seen' => date('Y-m-d H:i:s'),
        'last_seen_timestamp' => $now,
        'ip' => get_client_ip(),
        'heartbeat_count' => ($data[$user_key]['heartbeat_count'] ?? 0) + 1
    ];
    
    file_put_contents($active_users_file, json_encode($data, JSON_PRETTY_PRINT));
    
    if ($is_new) {
        $history = file_exists($active_users_history_file) ? json_decode(file_get_contents($active_users_history_file), true) : [];
        $history[] = [
            'license_key' => $license_key,
            'hwid' => substr($hwid, 0, 32) . '...',
            'device_name' => $device_name,
            'platform' => $platform,
            'connected_at' => date('Y-m-d H:i:s'),
            'ip' => get_client_ip()
        ];
        if (count($history) > 5000) $history = array_slice($history, -5000);
        file_put_contents($active_users_history_file, json_encode($history, JSON_PRETTY_PRINT));
    }
    
    return ['success' => true];
}

function get_active_users() {
    global $active_users_file;
    $now = time();
    if (!file_exists($active_users_file)) return ['success' => true, 'active_users' => [], 'count' => 0];
    $data = json_decode(file_get_contents($active_users_file), true);
    if (!is_array($data)) $data = [];
    $active = [];
    foreach ($data as $user) {
        if ($now - ($user['last_seen_timestamp'] ?? 0) <= 300) $active[] = $user;
    }
    return ['success' => true, 'active_users' => $active, 'count' => count($active), 'unique_licenses' => count(array_unique(array_column($active, 'license_key')))];
}

function get_active_users_stats() {
    $active = get_active_users();
    $platforms = [];
    foreach ($active['active_users'] as $user) {
        $p = $user['platform'];
        $platforms[$p] = ($platforms[$p] ?? 0) + 1;
    }
    
    global $active_users_history_file;
    $history = file_exists($active_users_history_file) ? json_decode(file_get_contents($active_users_history_file), true) : [];
    $last_24h = 0;
    $day_ago = time() - 86400;
    foreach ($history as $entry) {
        if (strtotime($entry['connected_at']) > $day_ago) $last_24h++;
    }
    
    return [
        'success' => true,
        'currently_active' => $active['count'],
        'unique_licenses' => $active['unique_licenses'],
        'platforms' => $platforms,
        'users' => $active['active_users'],
        'last_24h_activity' => $last_24h
    ];
}

function remove_active_user($license_key, $hwid) {
    global $active_users_file;
    if (!file_exists($active_users_file)) return ['success' => true];
    $data = json_decode(file_get_contents($active_users_file), true);
    $user_key = md5($license_key . $hwid);
    if (isset($data[$user_key])) unset($data[$user_key]);
    file_put_contents($active_users_file, json_encode($data));
    return ['success' => true];
}

function login($username, $password) {
    global $admin_config_file;
    $client_ip = get_client_ip();
    $rate_check = check_login_attempts($client_ip);
    if (!$rate_check['allowed']) {
        return ['success' => false, 'error' => "Too many attempts. Try again in {$rate_check['wait_seconds']} seconds"];
    }
    if (!file_exists($admin_config_file)) {
        $config = ['username' => 'admin', 'password' => password_hash('admin123', PASSWORD_DEFAULT), 'created_at' => date('Y-m-d H:i:s')];
        file_put_contents($admin_config_file, json_encode($config));
    }
    $config = json_decode(file_get_contents($admin_config_file), true);
    if ($username === $config['username'] && password_verify($password, $config['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['ip_address'] = $client_ip;
        $_SESSION['login_time'] = time();
        record_login_attempt($client_ip, true);
        log_action('LOGIN', "Admin logged in: $username");
        if (function_exists('send_admin_login_alert')) {
            send_admin_login_alert($username, $client_ip);
        }
        return ['success' => true, 'logged_in' => true, 'username' => $username];
    }
    record_login_attempt($client_ip, false);
    log_action('LOGIN_FAILED', "Failed login for: $username");
    return ['success' => false, 'error' => 'Invalid username or password'];
}

function check_login_attempts($ip) {
    global $login_attempts_file;
    $data = file_exists($login_attempts_file) ? json_decode(file_get_contents($login_attempts_file), true) : [];
    $now = time();
    if (!isset($data[$ip])) $data[$ip] = ['attempts' => 0, 'first_attempt' => $now, 'blocked_until' => 0];
    if ($data[$ip]['blocked_until'] > $now) {
        return ['allowed' => false, 'wait_seconds' => $data[$ip]['blocked_until'] - $now];
    }
    if ($now - $data[$ip]['first_attempt'] > LOGIN_LOCKOUT_TIME) {
        $data[$ip] = ['attempts' => 0, 'first_attempt' => $now, 'blocked_until' => 0];
    }
    return ['allowed' => true, 'attempts' => $data[$ip]['attempts']];
}

function record_login_attempt($ip, $success) {
    global $login_attempts_file;
    $data = file_exists($login_attempts_file) ? json_decode(file_get_contents($login_attempts_file), true) : [];
    $now = time();
    if (!isset($data[$ip])) $data[$ip] = ['attempts' => 0, 'first_attempt' => $now, 'blocked_until' => 0];
    if ($success) {
        $data[$ip] = ['attempts' => 0, 'first_attempt' => $now, 'blocked_until' => 0];
    } else {
        $data[$ip]['attempts']++;
        $data[$ip]['first_attempt'] = $now;
        if ($data[$ip]['attempts'] >= MAX_LOGIN_ATTEMPTS) {
            $data[$ip]['blocked_until'] = $now + LOGIN_LOCKOUT_TIME;
            log_action('SECURITY', "IP $ip blocked");
            if (function_exists('send_telegram_message')) {
                send_telegram_message("SECURITY ALERT\nIP $ip blocked - {$data[$ip]['attempts']} failed attempts");
            }
        }
    }
    file_put_contents($login_attempts_file, json_encode($data));
}

function check_auth() {
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        $ip_match = ($_SESSION['ip_address'] ?? '') === get_client_ip();
        $time_valid = (time() - ($_SESSION['login_time'] ?? 0)) < SESSION_LIFETIME;
        if ($ip_match && $time_valid) {
            return ['success' => true, 'logged_in' => true, 'username' => $_SESSION['admin_username'] ?? 'Admin'];
        }
        session_destroy();
    }
    return ['success' => true, 'logged_in' => false];
}

function logout() {
    session_destroy();
    return ['success' => true];
}

function get_dashboard_stats() {
    global $keys_file, $devices_file, $leaks_file, $proxies_file, $settings_file, $allowed_devices_file;
    $keys_data = json_decode(file_get_contents($keys_file), true);
    $devices_data = json_decode(file_get_contents($devices_file), true);
    $leaks_data = json_decode(file_get_contents($leaks_file), true);
    $proxies_data = json_decode(file_get_contents($proxies_file), true);
    $settings = get_settings();
    $allowed_data = json_decode(file_get_contents($allowed_devices_file), true);
    
    $blacklisted_count = 0;
    $total_used = 0;
    $total_limit = 0;
    $bypass_count = 0;
    foreach ($keys_data['keys'] as $key) {
        if (isset($key['blacklisted']) && $key['blacklisted'] === true) $blacklisted_count++;
        if (isset($key['bypass_all']) && $key['bypass_all'] === true) $bypass_count++;
        $total_used += $key['used'];
        $total_limit += $key['limit'];
    }
    
    $bypass_devices = 0;
    foreach ($allowed_data['devices'] as $device) {
        if (isset($device['bypass_all']) && $device['bypass_all'] === true) $bypass_devices++;
    }
    
    $active_stats = get_active_users_stats();
    
    return [
        'success' => true,
        'totalKeys' => count($keys_data['keys']),
        'activeDevices' => count($devices_data['devices']),
        'totalLeaks' => count($leaks_data['leaks']),
        'blacklistedKeys' => $blacklisted_count,
        'bypassKeys' => $bypass_count,
        'bypassDevices' => $bypass_devices,
        'totalProxies' => count($proxies_data['proxies']),
        'activeProxies' => count(array_filter($proxies_data['proxies'] ?? [], fn($p) => $p['status'] === 'active')),
        'allowed_devices_count' => count($allowed_data['devices'] ?? []),
        'force_maintenance' => $settings['force_maintenance'] ?? false,
        'force_maintenance_reason' => $settings['force_maintenance_reason'] ?? '',
        'kill_switch' => $settings['kill_switch'] ?? false,
        'kill_switch_reason' => $settings['kill_switch_reason'] ?? '',
        'current_loader_version' => $settings['current_loader_version'] ?? '7.1.0-SECURE',
        'maintenance_mode' => $settings['maintenance_mode'] ?? false,
        'integrity_check_enabled' => $settings['integrity_check_enabled'] ?? true,
        'total_used' => $total_used,
        'total_limit' => $total_limit,
        'active_users' => $active_stats['currently_active'] ?? 0,
        'require_hardware_token' => $settings['require_hardware_token'] ?? true,
        'hwid_salt_rotation' => $settings['hwid_salt_rotation'] ?? true
    ];
}

function list_keys() {
    global $keys_file;
    $data = json_decode(file_get_contents($keys_file), true);
    $keys = [];
    foreach ($data['keys'] as $key) {
        $keys[] = [
            'id' => $key['id'],
            'key' => $key['api_key'],
            'label' => $key['label'],
            'limit' => $key['limit'],
            'used' => $key['used'],
            'device_count' => count($key['devices'] ?? []),
            'max_devices' => $key['max_devices'] ?? 8,
            'expiry_date' => $key['expiry_date'] ?? 'permanent',
            'blacklisted' => $key['blacklisted'] ?? false,
            'bypass_all' => $key['bypass_all'] ?? false,
            'created_at' => $key['created_at']
        ];
    }
    return ['success' => true, 'keys' => $keys];
}

function create_key($label, $limit, $expiry_type, $expiry_value, $max_devices = null, $bypass_all = false) {
    global $keys_file, $settings_file;
    $data = json_decode(file_get_contents($keys_file), true);
    $settings = get_settings();
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ0123456789';
    $api_key = 'API-KEY-' . substr(str_shuffle($chars), 0, 4) . '-' . substr(str_shuffle($chars), 0, 4);
    $expiry_date = 'permanent';
    if ($expiry_type === 'days') $expiry_date = date('Y-m-d', strtotime("+{$expiry_value} days"));
    $device_limit = ($max_devices !== null && $max_devices !== '') ? (int)$max_devices : ($settings['max_devices_per_key'] ?? 8);
    $new_key = [
        'id' => $data['next_id'],
        'api_key' => $api_key,
        'label' => $label,
        'limit' => (int)$limit,
        'used' => 0,
        'devices' => [],
        'max_devices' => $device_limit,
        'expiry_date' => $expiry_date,
        'blacklisted' => false,
        'bypass_all' => filter_var($bypass_all, FILTER_VALIDATE_BOOLEAN),
        'created_at' => date('Y-m-d H:i:s')
    ];
    $data['keys'][] = $new_key;
    $data['next_id']++;
    file_put_contents($keys_file, json_encode($data));
    log_action('KEY_CREATED', "Created: $api_key for $label" . ($bypass_all ? " (BYPASS ALL)" : ""));
    if ($bypass_all && function_exists('send_telegram_message')) {
        send_telegram_message("SUPER ADMIN KEY CREATED\nKey: $api_key\nLabel: $label\nCreated by: " . ($_SESSION['admin_username'] ?? 'admin'));
    }
    return ['success' => true, 'api_key' => $api_key];
}

function create_codm_key($label, $expiry_type, $expiry_value, $max_devices = null, $bypass_all = false) {
    global $keys_file, $settings_file;
    $data = json_decode(file_get_contents($keys_file), true);
    $settings = get_settings();
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ0123456789';
    $api_key = 'CODM-' . substr(str_shuffle($chars), 0, 4) . '-' . substr(str_shuffle($chars), 0, 4);
    $expiry_date = 'permanent';
    $value = (int)$expiry_value;
    if ($expiry_type === 'days' && $value > 0) $expiry_date = date('Y-m-d', strtotime("+{$value} days"));
    elseif ($expiry_type === 'months' && $value > 0) $expiry_date = date('Y-m-d', strtotime("+{$value} months"));
    elseif ($expiry_type === 'years' && $value > 0) $expiry_date = date('Y-m-d', strtotime("+{$value} years"));
    $device_limit = ($max_devices !== null && $max_devices !== '') ? (int)$max_devices : ($settings['max_devices_per_key'] ?? 8);
    $new_key = [
        'id' => $data['next_id'],
        'api_key' => $api_key,
        'label' => $label,
        'limit' => 999999999,
        'used' => 0,
        'devices' => [],
        'max_devices' => $device_limit,
        'expiry_date' => $expiry_date,
        'blacklisted' => false,
        'bypass_all' => filter_var($bypass_all, FILTER_VALIDATE_BOOLEAN),
        'created_at' => date('Y-m-d H:i:s')
    ];
    $data['keys'][] = $new_key;
    $data['next_id']++;
    file_put_contents($keys_file, json_encode($data));
    log_action('KEY_CREATED', "Created CODM key: $api_key for $label" . ($bypass_all ? " (BYPASS ALL)" : ""));
    if ($bypass_all && function_exists('send_telegram_message')) {
        send_telegram_message("SUPER ADMIN CODM KEY CREATED\nKey: $api_key\nLabel: $label\nCreated by: " . ($_SESSION['admin_username'] ?? 'admin'));
    }
    return ['success' => true, 'api_key' => $api_key];
}

function delete_key($key_id) {
    global $keys_file;
    $data = json_decode(file_get_contents($keys_file), true);
    foreach ($data['keys'] as $i => $key) {
        if ($key['id'] == $key_id) {
            unset($data['keys'][$i]);
            $data['keys'] = array_values($data['keys']);
            file_put_contents($keys_file, json_encode($data));
            log_action('KEY_DELETED', "Deleted key ID: $key_id");
            return ['success' => true];
        }
    }
    return ['success' => false];
}

function blacklist_key($license_key) {
    global $keys_file;
    $data = json_decode(file_get_contents($keys_file), true);
    foreach ($data['keys'] as &$key) {
        if ($key['api_key'] === $license_key) {
            $key['blacklisted'] = true;
            file_put_contents($keys_file, json_encode($data));
            log_action('BLACKLIST', "Blacklisted: $license_key");
            if (function_exists('send_key_blacklisted_alert')) {
                send_key_blacklisted_alert($license_key, $key['label'] ?? 'Unknown');
            }
            return ['success' => true];
        }
    }
    return ['success' => false];
}

function unblock_key($license_key) {
    global $keys_file;
    $data = json_decode(file_get_contents($keys_file), true);
    foreach ($data['keys'] as &$key) {
        if ($key['api_key'] === $license_key) {
            $key['blacklisted'] = false;
            file_put_contents($keys_file, json_encode($data));
            log_action('UNBLACKLIST', "Unblacklisted: $license_key");
            return ['success' => true];
        }
    }
    return ['success' => false];
}

function update_key_max_devices($api_key, $max_devices) {
    global $keys_file;
    $max_devices = (int)$max_devices;
    if ($max_devices < 1 || $max_devices > 50) {
        return ['success' => false, 'error' => 'Max devices must be between 1 and 50'];
    }
    $data = json_decode(file_get_contents($keys_file), true);
    foreach ($data['keys'] as &$key) {
        if ($key['api_key'] === $api_key) {
            $key['max_devices'] = $max_devices;
            file_put_contents($keys_file, json_encode($data));
            log_action('UPDATE_MAX_DEVICES', "Updated max devices for $api_key to $max_devices");
            return ['success' => true];
        }
    }
    return ['success' => false];
}

function list_devices() {
    global $devices_file, $keys_file, $allowed_devices_file;
    $devices_data = json_decode(file_get_contents($devices_file), true);
    $keys_data = json_decode(file_get_contents($keys_file), true);
    $allowed_data = json_decode(file_get_contents($allowed_devices_file), true);
    $allowed_hwids = array_column($allowed_data['devices'] ?? [], 'hwid');
    $bypass_hwids = [];
    foreach ($allowed_data['devices'] ?? [] as $device) {
        if (isset($device['bypass_all']) && $device['bypass_all'] === true) {
            $bypass_hwids[] = $device['hwid'];
        }
    }
    $key_labels = [];
    foreach ($keys_data['keys'] as $key) $key_labels[$key['id']] = $key['label'];
    $devices = [];
    foreach ($devices_data['devices'] as $device) {
        $devices[] = [
            'full_hwid' => $device['hwid'],
            'hwid' => substr($device['hwid'], 0, 32) . '...',
            'device_name' => $device['device_name'],
            'platform' => $device['platform'],
            'license_label' => $key_labels[$device['key_id']] ?? 'Unknown',
            'last_seen' => $device['last_seen'],
            'usage_count' => $device['usage_count'] ?? 0,
            'is_allowed' => in_array($device['hwid'], $allowed_hwids),
            'is_bypass' => in_array($device['hwid'], $bypass_hwids)
        ];
    }
    return ['success' => true, 'devices' => $devices];
}

function unbind_device($hwid) {
    global $devices_file, $keys_file;
    $devices_data = json_decode(file_get_contents($devices_file), true);
    $keys_data = json_decode(file_get_contents($keys_file), true);
    foreach ($devices_data['devices'] as $i => $device) {
        if ($device['hwid'] === $hwid) {
            unset($devices_data['devices'][$i]);
            $devices_data['devices'] = array_values($devices_data['devices']);
            file_put_contents($devices_file, json_encode($devices_data));
            foreach ($keys_data['keys'] as &$key) {
                if ($key['id'] == $device['key_id']) {
                    $key['devices'] = array_values(array_filter($key['devices'], fn($d) => $d !== $hwid));
                    break;
                }
            }
            file_put_contents($keys_file, json_encode($keys_data));
            log_action('UNBIND', "Device unbound: " . substr($hwid, 0, 32) . "...");
            return ['success' => true];
        }
    }
    return ['success' => false];
}

function unbind_all_devices() {
    global $devices_file, $keys_file;
    file_put_contents($devices_file, json_encode(['devices' => []]));
    $keys_data = json_decode(file_get_contents($keys_file), true);
    foreach ($keys_data['keys'] as &$key) $key['devices'] = [];
    file_put_contents($keys_file, json_encode($keys_data));
    log_action('UNBIND_ALL', "All devices unbound");
    return ['success' => true];
}

function get_allowed_devices() {
    global $allowed_devices_file;
    $data = json_decode(file_get_contents($allowed_devices_file), true);
    return ['success' => true, 'devices' => $data['devices'] ?? []];
}

function add_allowed_device($hwid, $label, $bypass_all = false) {
    global $allowed_devices_file;
    if (!preg_match('/^[a-f0-9]{64}$/', $hwid) && !preg_match('/^[a-f0-9]{128}$/', $hwid)) {
        return ['success' => false, 'error' => 'Invalid HWID format (must be 64 or 128 hex characters)'];
    }
    $data = json_decode(file_get_contents($allowed_devices_file), true);
    foreach ($data['devices'] as $device) {
        if ($device['hwid'] === $hwid) {
            return ['success' => false, 'error' => 'Device already whitelisted'];
        }
    }
    $data['devices'][] = [
        'id' => $data['next_id'],
        'hwid' => $hwid,
        'label' => $label,
        'bypass_all' => filter_var($bypass_all, FILTER_VALIDATE_BOOLEAN),
        'added_date' => date('Y-m-d H:i:s'),
        'added_by' => $_SESSION['admin_username'] ?? 'admin',
        'last_used' => null,
        'use_count' => 0
    ];
    $data['next_id']++;
    file_put_contents($allowed_devices_file, json_encode($data));
    log_action('WHITELIST', "Added device: " . substr($hwid, 0, 32) . "... - $label" . ($bypass_all ? " (BYPASS ALL)" : ""));
    if ($bypass_all && function_exists('send_telegram_message')) {
        send_telegram_message("SUPER ADMIN BYPASS ADDED\nHWID: " . substr($hwid, 0, 32) . "...\nLabel: $label\nAdded by: " . ($_SESSION['admin_username'] ?? 'admin'));
    }
    return ['success' => true];
}

function remove_allowed_device($hwid) {
    global $allowed_devices_file;
    $data = json_decode(file_get_contents($allowed_devices_file), true);
    foreach ($data['devices'] as $i => $device) {
        if ($device['hwid'] === $hwid) {
            unset($data['devices'][$i]);
            $data['devices'] = array_values($data['devices']);
            file_put_contents($allowed_devices_file, json_encode($data));
            log_action('WHITELIST', "Removed device: " . substr($hwid, 0, 32) . "...");
            return ['success' => true];
        }
    }
    return ['success' => false];
}

function is_hwid_bypassed($hwid) {
    global $allowed_devices_file;
    $data = file_exists($allowed_devices_file) ? json_decode(file_get_contents($allowed_devices_file), true) : ['devices' => []];
    $hwid_short = strlen($hwid) == 128 ? substr($hwid, 0, 64) : $hwid;
    
    foreach ($data['devices'] as &$device) {
        if (($device['hwid'] === $hwid || $device['hwid'] === $hwid_short) && isset($device['bypass_all']) && $device['bypass_all'] === true) {
            $device['last_used'] = date('Y-m-d H:i:s');
            $device['use_count'] = ($device['use_count'] ?? 0) + 1;
            file_put_contents($allowed_devices_file, json_encode($data));
            return true;
        }
    }
    return false;
}

function get_blacklisted_hwids() {
    global $hwid_blacklist_file;
    $data = json_decode(file_get_contents($hwid_blacklist_file), true);
    return ['success' => true, 'hwids' => $data['blacklisted_hwids'] ?? []];
}

function add_hwid_to_blacklist($hwid) {
    global $hwid_blacklist_file;
    $data = json_decode(file_get_contents($hwid_blacklist_file), true);
    if (!in_array($hwid, $data['blacklisted_hwids'])) {
        $data['blacklisted_hwids'][] = $hwid;
        file_put_contents($hwid_blacklist_file, json_encode($data));
        log_action('HWID_BLACKLIST', "Banned HWID: " . substr($hwid, 0, 32) . "...");
        return ['success' => true];
    }
    return ['success' => false];
}

function remove_hwid_from_blacklist($hwid) {
    global $hwid_blacklist_file;
    $data = json_decode(file_get_contents($hwid_blacklist_file), true);
    $index = array_search($hwid, $data['blacklisted_hwids']);
    if ($index !== false) {
        unset($data['blacklisted_hwids'][$index]);
        $data['blacklisted_hwids'] = array_values($data['blacklisted_hwids']);
        file_put_contents($hwid_blacklist_file, json_encode($data));
        log_action('HWID_BLACKLIST', "Unbanned HWID: " . substr($hwid, 0, 32) . "...");
        return ['success' => true];
    }
    return ['success' => false];
}

function get_ip_blacklist() {
    global $ip_blacklist_file;
    $blacklist = file_exists($ip_blacklist_file) ? json_decode(file_get_contents($ip_blacklist_file), true) : [];
    return ['success' => true, 'ips' => $blacklist];
}

function add_ip_to_blacklist($ip) {
    global $ip_blacklist_file;
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return ['success' => false, 'error' => 'Invalid IP address'];
    }
    $blacklist = file_exists($ip_blacklist_file) ? json_decode(file_get_contents($ip_blacklist_file), true) : [];
    if (!in_array($ip, $blacklist)) {
        $blacklist[] = $ip;
        file_put_contents($ip_blacklist_file, json_encode($blacklist));
        log_action('IP_BLACKLIST', "Blocked IP: $ip");
        return ['success' => true];
    }
    return ['success' => false];
}

function remove_ip_from_blacklist($ip) {
    global $ip_blacklist_file;
    $blacklist = file_exists($ip_blacklist_file) ? json_decode(file_get_contents($ip_blacklist_file), true) : [];
    $index = array_search($ip, $blacklist);
    if ($index !== false) {
        unset($blacklist[$index]);
        file_put_contents($ip_blacklist_file, json_encode(array_values($blacklist)));
        log_action('IP_BLACKLIST', "Unblocked IP: $ip");
        return ['success' => true];
    }
    return ['success' => false];
}

function get_blacklisted_versions() {
    global $version_blacklist_file;
    $data = json_decode(file_get_contents($version_blacklist_file), true);
    return ['success' => true, 'versions' => $data['blacklisted_versions'] ?? []];
}

function add_version_to_blacklist($version) {
    global $version_blacklist_file;
    $data = json_decode(file_get_contents($version_blacklist_file), true);
    if (!in_array($version, $data['blacklisted_versions'])) {
        $data['blacklisted_versions'][] = $version;
        file_put_contents($version_blacklist_file, json_encode($data));
        log_action('VERSION_BLACKLIST', "Blocked version: $version");
        return ['success' => true];
    }
    return ['success' => false];
}

function remove_version_from_blacklist($version) {
    global $version_blacklist_file;
    $data = json_decode(file_get_contents($version_blacklist_file), true);
    $index = array_search($version, $data['blacklisted_versions']);
    if ($index !== false) {
        unset($data['blacklisted_versions'][$index]);
        $data['blacklisted_versions'] = array_values($data['blacklisted_versions']);
        file_put_contents($version_blacklist_file, json_encode($data));
        log_action('VERSION_BLACKLIST', "Unblocked version: $version");
        return ['success' => true];
    }
    return ['success' => false];
}

function get_maintenance_status() {
    $settings = get_settings();
    return [
        'success' => true,
        'maintenance_mode' => $settings['maintenance_mode'] ?? false,
        'maintenance_message' => $settings['maintenance_message'] ?? '',
        'force_maintenance' => $settings['force_maintenance'] ?? false,
        'force_maintenance_reason' => $settings['force_maintenance_reason'] ?? '',
        'kill_switch' => $settings['kill_switch'] ?? false,
        'kill_switch_reason' => $settings['kill_switch_reason'] ?? '',
        'integrity_check_enabled' => $settings['integrity_check_enabled'] ?? true,
        'current_loader_version' => $settings['current_loader_version'] ?? '7.1.0-SECURE'
    ];
}

function enable_force_maintenance($reason) {
    global $settings_file;
    $settings = get_settings();
    $settings['force_maintenance'] = true;
    $settings['force_maintenance_reason'] = $reason;
    file_put_contents($settings_file, json_encode($settings));
    log_action('FORCE_MAINTENANCE', "ENABLED: $reason");
    return ['success' => true];
}

function disable_force_maintenance() {
    global $settings_file;
    $settings = get_settings();
    $settings['force_maintenance'] = false;
    $settings['force_maintenance_reason'] = '';
    file_put_contents($settings_file, json_encode($settings));
    log_action('FORCE_MAINTENANCE', "DISABLED");
    return ['success' => true];
}

function update_maintenance_settings($maintenance_mode, $maintenance_message, $loader_enabled, $multi_tool_enabled, $allow_admin_bypass) {
    global $settings_file;
    $settings = get_settings();
    $settings['maintenance_mode'] = filter_var($maintenance_mode, FILTER_VALIDATE_BOOLEAN);
    $settings['maintenance_message'] = $maintenance_message;
    file_put_contents($settings_file, json_encode($settings));
    log_action('MAINTENANCE', "Maintenance mode: " . ($maintenance_mode ? 'ON' : 'OFF'));
    return ['success' => true];
}

function get_whitelist_bypass_settings() {
    $settings = get_settings();
    return [
        'success' => true,
        'settings' => [
            'kill_switch' => $settings['whitelist_bypass_kill_switch'] ?? true,
            'force_maintenance' => $settings['whitelist_bypass_force_maintenance'] ?? true,
            'maintenance' => $settings['whitelist_bypass_maintenance'] ?? true,
            'hwid_blacklist' => $settings['whitelist_bypass_hwid_blacklist'] ?? true,
            'key_blacklist' => $settings['whitelist_bypass_key_blacklist'] ?? true,
            'expiry' => $settings['whitelist_bypass_expiry'] ?? true,
            'device_limit' => $settings['whitelist_bypass_device_limit'] ?? true,
            'anti_leak' => $settings['whitelist_bypass_anti_leak'] ?? true,
            'quota_limit' => $settings['whitelist_bypass_quota_limit'] ?? true,
            'bypass_all' => $settings['whitelist_bypass_all'] ?? false
        ]
    ];
}

function update_whitelist_bypass_settings($settings_array) {
    global $settings_file;
    $settings = get_settings();
    foreach ($settings_array as $key => $value) {
        $settings["whitelist_bypass_{$key}"] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
    file_put_contents($settings_file, json_encode($settings));
    log_action('BYPASS_SETTINGS', "Whitelist bypass settings updated");
    return ['success' => true];
}

function get_integrity_check_settings() {
    $settings = get_settings();
    return ['success' => true, 'integrity_check_enabled' => $settings['integrity_check_enabled'] ?? true];
}

function update_integrity_check_settings($enabled, $skip_message) {
    global $settings_file;
    $settings = get_settings();
    $settings['integrity_check_enabled'] = filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
    file_put_contents($settings_file, json_encode($settings));
    log_action('INTEGRITY_SETTINGS', "Integrity check: " . ($enabled ? 'ON' : 'OFF'));
    return ['success' => true];
}

function get_version_lock_settings() {
    $settings = get_settings();
    return [
        'success' => true,
        'current_loader_version' => $settings['current_loader_version'] ?? '7.1.0-SECURE',
        'min_allowed_version' => $settings['min_allowed_version'] ?? '1.0'
    ];
}

function update_version_lock($current_version, $min_version, $auto_block) {
    global $settings_file;
    $settings = get_settings();
    $settings['current_loader_version'] = $current_version;
    $settings['min_allowed_version'] = $min_version;
    file_put_contents($settings_file, json_encode($settings));
    log_action('VERSION_LOCK', "Updated: Current=$current_version, Min=$min_version");
    return ['success' => true];
}

function get_loader_info() {
    $settings = get_settings();
    return [
        'success' => true,
        'version' => $settings['current_loader_version'] ?? LOADER_VERSION,
        'original_hash' => ORIGINAL_LOADER_HASH,
        'current_hash' => $settings['current_loader_hash'] ?? ORIGINAL_LOADER_HASH
    ];
}

function upload_loader_file() {
    if (!isset($_FILES['loader_file']) || $_FILES['loader_file']['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'No file uploaded'];
    }
    $upload_path = __DIR__ . '/loader.py';
    if (move_uploaded_file($_FILES['loader_file']['tmp_name'], $upload_path)) {
        $new_hash = hash_file('sha256', $upload_path);
        global $settings_file;
        $settings = get_settings();
        $settings['current_loader_hash'] = $new_hash;
        $settings['current_loader_version'] = LOADER_VERSION;
        file_put_contents($settings_file, json_encode($settings));
        log_action('LOADER_UPLOAD', "New loader.py uploaded");
        return ['success' => true, 'hash' => $new_hash];
    }
    return ['success' => false, 'error' => 'Failed to save file'];
}

function upload_multi_file() {
    if (!isset($_FILES['multi_file']) || $_FILES['multi_file']['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'No file uploaded'];
    }
    $upload_path = __DIR__ . '/multi.py';
    if (move_uploaded_file($_FILES['multi_file']['tmp_name'], $upload_path)) {
        $new_hash = hash_file('sha256', $upload_path);
        log_action('MULTI_UPLOAD', "New multi.py uploaded");
        return ['success' => true, 'hash' => $new_hash];
    }
    return ['success' => false, 'error' => 'Failed to save file'];
}

function get_loader_content() {
    $loader_path = __DIR__ . '/loader.py';
    if (file_exists($loader_path)) {
        return ['success' => true, 'content' => file_get_contents($loader_path), 'size' => filesize($loader_path)];
    }
    return ['success' => false, 'error' => 'loader.py not found'];
}

function get_multi_content() {
    $multi_path = __DIR__ . '/multi.py';
    if (file_exists($multi_path)) {
        return ['success' => true, 'content' => file_get_contents($multi_path), 'size' => filesize($multi_path)];
    }
    return ['success' => false, 'error' => 'multi.py not found'];
}

function change_admin_password($current_password, $new_password) {
    global $admin_config_file;
    if (strlen($new_password) < 6) {
        return ['success' => false, 'error' => 'Password must be at least 6 characters'];
    }
    $config = json_decode(file_get_contents($admin_config_file), true);
    if (!password_verify($current_password, $config['password'])) {
        return ['success' => false, 'error' => 'Current password is incorrect'];
    }
    $config['password'] = password_hash($new_password, PASSWORD_DEFAULT);
    $config['last_password_change'] = date('Y-m-d H:i:s');
    file_put_contents($admin_config_file, json_encode($config));
    log_action('PASSWORD_CHANGE', "Admin password changed");
    if (function_exists('send_telegram_message')) {
        send_telegram_message("ADMIN PASSWORD CHANGED\nIP: " . get_client_ip());
    }
    return ['success' => true];
}

function change_admin_username($current_username, $new_username) {
    global $admin_config_file;
    if (strlen($new_username) < 3) {
        return ['success' => false, 'error' => 'Username must be at least 3 characters'];
    }
    $config = json_decode(file_get_contents($admin_config_file), true);
    if ($current_username !== $config['username']) {
        return ['success' => false, 'error' => 'Current username is incorrect'];
    }
    $config['username'] = $new_username;
    file_put_contents($admin_config_file, json_encode($config));
    $_SESSION['admin_username'] = $new_username;
    log_action('USERNAME_CHANGE', "Admin username changed to: $new_username");
    if (function_exists('send_telegram_message')) {
        send_telegram_message("ADMIN USERNAME CHANGED\nNew username: $new_username\nIP: " . get_client_ip());
    }
    return ['success' => true];
}

function create_enhanced_backup() {
    global $data_dir, $keys_file, $devices_file, $settings_file, $logs_file, $leaks_file, $proxies_file, $allowed_devices_file;
    $backup_dir = $data_dir . '/backups';
    if (!file_exists($backup_dir)) mkdir($backup_dir, 0755, true);
    $timestamp = date('Y-m-d_H-i-s');
    $backup_file = $backup_dir . "/backup_{$timestamp}.json";
    $export_data = [
        'keys' => json_decode(file_get_contents($keys_file), true),
        'devices' => json_decode(file_get_contents($devices_file), true),
        'settings' => json_decode(file_get_contents($settings_file), true),
        'logs' => json_decode(file_get_contents($logs_file), true),
        'leaks' => json_decode(file_get_contents($leaks_file), true),
        'proxies' => json_decode(file_get_contents($proxies_file), true),
        'allowed_devices' => json_decode(file_get_contents($allowed_devices_file), true),
        'backup_time' => date('Y-m-d H:i:s'),
        'backup_version' => '7.1.0-SECURE'
    ];
    file_put_contents($backup_file, json_encode($export_data, JSON_PRETTY_PRINT));
    
    $files = glob($backup_dir . "/*.json");
    usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
    foreach (array_slice($files, BACKUP_MAX_FILES) as $file) unlink($file);
    
    log_action('BACKUP_CREATED', "Backup created: " . basename($backup_file));
    return ['success' => true, 'file' => basename($backup_file), 'size' => filesize($backup_file)];
}

function list_enhanced_backups() {
    global $data_dir;
    $backup_dir = $data_dir . '/backups';
    if (!file_exists($backup_dir)) return ['success' => true, 'backups' => []];
    $backups = [];
    foreach (glob($backup_dir . "/*.json") as $file) {
        $backups[] = [
            'filename' => basename($file),
            'size' => format_file_size(filesize($file)),
            'size_bytes' => filesize($file),
            'created' => date('Y-m-d H:i:s', filemtime($file)),
            'verified' => true
        ];
    }
    usort($backups, fn($a, $b) => strtotime($b['created']) - strtotime($a['created']));
    return ['success' => true, 'backups' => $backups];
}

function restore_enhanced_backup($filename) {
    global $data_dir, $keys_file, $devices_file, $settings_file, $logs_file, $leaks_file, $proxies_file, $allowed_devices_file;
    $backup_dir = $data_dir . '/backups';
    $backup_path = $backup_dir . '/' . $filename;
    if (!file_exists($backup_path)) return ['success' => false, 'error' => 'Backup file not found'];
    
    $data = json_decode(file_get_contents($backup_path), true);
    if (!$data) return ['success' => false, 'error' => 'Invalid backup data'];
    
    create_enhanced_backup();
    file_put_contents($keys_file, json_encode($data['keys']));
    file_put_contents($devices_file, json_encode($data['devices']));
    file_put_contents($settings_file, json_encode($data['settings']));
    file_put_contents($logs_file, json_encode($data['logs']));
    file_put_contents($leaks_file, json_encode($data['leaks']));
    file_put_contents($proxies_file, json_encode($data['proxies']));
    file_put_contents($allowed_devices_file, json_encode($data['allowed_devices']));
    
    log_action('BACKUP_RESTORED', "Restored from backup: $filename");
    return ['success' => true, 'message' => 'Backup restored successfully'];
}

function reset_all_limits() {
    global $keys_file;
    $data = json_decode(file_get_contents($keys_file), true);
    foreach ($data['keys'] as &$key) $key['used'] = 0;
    file_put_contents($keys_file, json_encode($data));
    log_action('RESET', "All usage limits reset");
    return ['success' => true];
}

function export_database() {
    global $keys_file, $devices_file, $settings_file, $logs_file, $leaks_file, $proxies_file, $allowed_devices_file, $hwid_blacklist_file, $version_blacklist_file, $ip_blacklist_file;
    return [
        'success' => true,
        'data' => [
            'keys' => json_decode(file_get_contents($keys_file), true),
            'devices' => json_decode(file_get_contents($devices_file), true),
            'settings' => json_decode(file_get_contents($settings_file), true),
            'logs' => json_decode(file_get_contents($logs_file), true),
            'leaks' => json_decode(file_get_contents($leaks_file), true),
            'proxies' => json_decode(file_get_contents($proxies_file), true),
            'allowed_devices' => json_decode(file_get_contents($allowed_devices_file), true),
            'hwid_blacklist' => json_decode(file_get_contents($hwid_blacklist_file), true),
            'version_blacklist' => json_decode(file_get_contents($version_blacklist_file), true),
            'ip_blacklist' => json_decode(file_get_contents($ip_blacklist_file), true)
        ]
    ];
}

function list_proxies() {
    global $proxies_file;
    $data = json_decode(file_get_contents($proxies_file), true);
    return ['success' => true, 'proxies' => $data['proxies'] ?? []];
}

function upload_proxy_file() {
    global $proxies_file;
    if (!isset($_FILES['proxy_file']) || $_FILES['proxy_file']['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'No file uploaded'];
    }
    $content = file_get_contents($_FILES['proxy_file']['tmp_name']);
    $lines = explode("\n", $content);
    $data = json_decode(file_get_contents($proxies_file), true);
    $imported = 0;
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        $parts = explode(':', $line);
        if (count($parts) == 4) {
            $formatted_url = "http://{$parts[0]}:{$parts[1]}@{$parts[2]}:{$parts[3]}";
            $exists = false;
            foreach ($data['proxies'] as $existing) {
                if ($existing['url'] == $formatted_url) { $exists = true; break; }
            }
            if (!$exists) {
                $data['proxies'][] = [
                    'id' => $data['next_id'],
                    'url' => $formatted_url,
                    'status' => 'active',
                    'usage_count' => 0,
                    'added_at' => date('Y-m-d H:i:s')
                ];
                $data['next_id']++;
                $imported++;
            }
        }
    }
    file_put_contents($proxies_file, json_encode($data));
    log_action('PROXY_UPLOAD', "Imported $imported proxies");
    return ['success' => true, 'imported' => $imported];
}

function delete_proxy($proxy_id) {
    global $proxies_file;
    $data = json_decode(file_get_contents($proxies_file), true);
    foreach ($data['proxies'] as $i => $proxy) {
        if ($proxy['id'] == $proxy_id) {
            unset($data['proxies'][$i]);
            $data['proxies'] = array_values($data['proxies']);
            file_put_contents($proxies_file, json_encode($data));
            log_action('PROXY_DELETE', "Proxy deleted: ID $proxy_id");
            return ['success' => true];
        }
    }
    return ['success' => false];
}

function get_proxy_stats() {
    global $proxy_stats_file, $proxies_file;
    $stats = file_exists($proxy_stats_file) ? json_decode(file_get_contents($proxy_stats_file), true) : [];
    $proxies_data = json_decode(file_get_contents($proxies_file), true);
    $proxies = $proxies_data['proxies'] ?? [];
    $total_usage = 0;
    $active_proxies = 0;
    foreach ($proxies as $proxy) {
        if ($proxy['status'] === 'active') $active_proxies++;
        $total_usage += $stats[$proxy['url']]['total_usage'] ?? 0;
    }
    return [
        'success' => true,
        'summary' => [
            'total_proxies' => count($proxies),
            'active_proxies' => $active_proxies,
            'total_usage' => $total_usage,
            'overall_success_rate' => $active_proxies > 0 ? round(($active_proxies / count($proxies)) * 100) : 0
        ]
    ];
}

function auto_check_all_proxies() {
    global $proxies_file, $proxy_stats_file;
    
    $data = json_decode(file_get_contents($proxies_file), true);
    $proxies = $data['proxies'] ?? [];
    $total = count($proxies);
    
    if ($total == 0) {
        return ['success' => true, 'checked' => 0, 'valid' => 0, 'invalid' => 0, 'removed' => 0, 'total_proxies' => 0, 'message' => 'No proxies to check'];
    }
    
    $valid = 0;
    $invalid = 0;
    $removed = 0;
    $checked = 0;
    
    foreach ($proxies as $i => $proxy) {
        $proxy_url = $proxy['url'];
        $checked++;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://ip-api.com/json/');
        curl_setopt($ch, CURLOPT_PROXY, $proxy_url);
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_NOBODY, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $is_valid = ($http_code === 200 && $response !== false);
        
        if ($is_valid) {
            $valid++;
            $proxies[$i]['status'] = 'active';
            $proxies[$i]['last_check'] = date('Y-m-d H:i:s');
            $stats = file_exists($proxy_stats_file) ? json_decode(file_get_contents($proxy_stats_file), true) : [];
            if (!isset($stats[$proxy_url])) $stats[$proxy_url] = ['total_usage' => 0, 'success_count' => 0, 'fail_count' => 0];
            $stats[$proxy_url]['success_count']++;
            $stats[$proxy_url]['last_success'] = date('Y-m-d H:i:s');
            file_put_contents($proxy_stats_file, json_encode($stats));
        } else {
            $invalid++;
            $proxies[$i]['status'] = 'inactive';
            $proxies[$i]['last_check'] = date('Y-m-d H:i:s');
            $stats = file_exists($proxy_stats_file) ? json_decode(file_get_contents($proxy_stats_file), true) : [];
            if (!isset($stats[$proxy_url])) $stats[$proxy_url] = ['total_usage' => 0, 'success_count' => 0, 'fail_count' => 0];
            $stats[$proxy_url]['fail_count']++;
            $stats[$proxy_url]['last_fail'] = date('Y-m-d H:i:s');
            file_put_contents($proxy_stats_file, json_encode($stats));
            
            $fail_count = $stats[$proxy_url]['fail_count'] ?? 0;
            if ($fail_count >= 3) {
                unset($proxies[$i]);
                $removed++;
                log_action('PROXY_REMOVED', "Removed dead proxy: $proxy_url");
            }
        }
        usleep(100000);
    }
    
    $data['proxies'] = array_values($proxies);
    file_put_contents($proxies_file, json_encode($data));
    log_action('PROXY_CHECK', "Checked $checked proxies: $valid valid, $invalid invalid, $removed removed");
    
    return ['success' => true, 'checked' => $checked, 'valid' => $valid, 'invalid' => $invalid, 'removed' => $removed, 'total_proxies' => count($data['proxies']), 'message' => "Checked $checked proxies: $valid working, $invalid failed, $removed removed"];
}

function get_leaks() {
    global $leaks_file;
    $leaks = json_decode(file_get_contents($leaks_file), true);
    return ['success' => true, 'leaks' => array_reverse($leaks['leaks'])];
}

function clear_leaks() {
    global $leaks_file;
    file_put_contents($leaks_file, json_encode(['leaks' => []]));
    log_action('LEAKS_CLEAR', "All leaks cleared");
    return ['success' => true];
}

function get_logs($limit = 50) {
    global $logs_file;
    $logs_data = json_decode(file_get_contents($logs_file), true);
    $logs = array_slice(array_reverse($logs_data['logs']), 0, $limit);
    return ['success' => true, 'logs' => $logs];
}

function clear_logs() {
    global $logs_file;
    file_put_contents($logs_file, json_encode(['logs' => []]));
    return ['success' => true];
}

function get_settings_api() { 
    return get_settings(); 
}

function save_settings_action($default_proxy, $max_devices_per_key, $anti_leak_enabled) {
    global $settings_file;
    $settings = get_settings();
    $settings['default_proxy'] = $default_proxy;
    $settings['max_devices_per_key'] = (int)$max_devices_per_key;
    $settings['anti_leak_enabled'] = filter_var($anti_leak_enabled, FILTER_VALIDATE_BOOLEAN);
    file_put_contents($settings_file, json_encode($settings));
    log_action('SETTINGS', "Settings updated");
    return ['success' => true];
}

function update_loader_version($version) {
    global $settings_file;
    $settings = get_settings();
    $settings['current_loader_version'] = $version;
    file_put_contents($settings_file, json_encode($settings));
    log_action('LOADER_VERSION', "Loader version changed to: $version");
    return ['success' => true, 'version' => $version];
}

function check_loader_integrity($loader_hash, $license_key, $hwid, $device_name) {
    $settings = get_settings();
    
    if (!$settings['integrity_check_enabled']) {
        return ['success' => true, 'integrity_check_disabled' => true];
    }
    
    $expected_hash = $settings['current_loader_hash'] ?? ORIGINAL_LOADER_HASH;
    
    if ($loader_hash !== $expected_hash) {
        log_action('TAMPER_DETECTED', "Loader hash mismatch for key: $license_key");
        
        if (function_exists('send_tamper_alert')) {
            $key_data = get_key_by_license($license_key);
            send_tamper_alert($license_key, $key_data, $hwid, $device_name, 
                $_POST['platform'] ?? 'Unknown', $expected_hash, $loader_hash);
        }
        
        return ['success' => true, 'tampered' => true];
    }
    
    return ['success' => true, 'tampered' => false];
}

function handle_challenge($license_key, $hwid, $challenge, $timestamp, $nonce) {
    global $challenge_file;
    
    if (!file_exists($challenge_file)) {
        file_put_contents($challenge_file, json_encode([]));
    }
    
    $challenges = json_decode(file_get_contents($challenge_file), true);
    
    $challenge_id = md5($license_key . $hwid . $nonce);
    $challenges[$challenge_id] = [
        'challenge' => $challenge,
        'license_key' => $license_key,
        'hwid' => $hwid,
        'timestamp' => $timestamp,
        'expires' => time() + 60,
        'solved' => false
    ];
    
    foreach ($challenges as $key => $c) {
        if ($c['expires'] < time()) {
            unset($challenges[$key]);
        }
    }
    
    file_put_contents($challenge_file, json_encode($challenges));
    
    return ['success' => true, 'challenge_response' => $challenge];
}

function verify_challenge_response($license_key, $hwid, $response, $nonce) {
    global $challenge_file;
    
    if (!file_exists($challenge_file)) {
        return ['success' => false, 'verified' => false];
    }
    
    $challenges = json_decode(file_get_contents($challenge_file), true);
    $challenge_id = md5($license_key . $hwid . $nonce);
    
    if (!isset($challenges[$challenge_id])) {
        return ['success' => false, 'verified' => false, 'error' => 'Challenge not found'];
    }
    
    $challenge_data = $challenges[$challenge_id];
    
    if ($challenge_data['expires'] < time()) {
        unset($challenges[$challenge_id]);
        file_put_contents($challenge_file, json_encode($challenges));
        return ['success' => false, 'verified' => false, 'error' => 'Challenge expired'];
    }
    
    $expected = hash('sha256', $challenge_data['challenge'] . $license_key . $hwid);
    
    if ($response === $expected) {
        $challenge_data['solved'] = true;
        $challenges[$challenge_id] = $challenge_data;
        file_put_contents($challenge_file, json_encode($challenges));
        return ['success' => true, 'verified' => true];
    }
    
    log_action('CHALLENGE_FAIL', "Challenge response mismatch for: $license_key");
    return ['success' => false, 'verified' => false, 'error' => 'Invalid response'];
}

function get_key_by_license($license_key) {
    global $keys_file;
    $data = json_decode(file_get_contents($keys_file), true);
    foreach ($data['keys'] as $key) {
        if ($key['api_key'] === $license_key) {
            return $key;
        }
    }
    return null;
}

function validate_license($license_key, $hwid, $device_name = 'localhost', $platform = 'Unknown', $username = '', $additional_info = []) {
    global $keys_file, $devices_file, $first_time_users_file, $nonces_file;
    
    $version_check = get_settings();
    if ($version_check['min_allowed_version'] != '1.0' && isset($_POST['loader_version'])) {
        if (version_compare($_POST['loader_version'], $version_check['min_allowed_version']) < 0) {
            return ['success' => false, 'error' => 'Loader version outdated. Please update to latest version.'];
        }
    }
    
    if (!validate_hwid_format($hwid)) {
        return ['success' => false, 'error' => 'Invalid HWID format'];
    }
    
    $signature = $_POST['hwid_signature'] ?? '';
    if (!empty($signature)) {
        $expected_signature = hash('sha256', $hwid . SHARED_SECRET);
        if ($signature !== $expected_signature) {
            log_action('SIGNATURE_FAIL', "Invalid HWID signature");
            if (function_exists('send_telegram_message')) {
                send_telegram_message("INVALID HWID SIGNATURE\nHWID: " . substr($hwid, 0, 32) . "...\nIP: " . get_client_ip() . "\nLicense: $license_key");
            }
            return ['success' => false, 'error' => 'Invalid HWID signature - Possible tampering detected'];
        }
    }
    
    $timestamp = intval($_POST['timestamp'] ?? 0);
    $nonce = $_POST['nonce'] ?? '';
    $session_token = $_POST['session_token'] ?? '';
    
    if (!validate_session_token($license_key, $hwid, $timestamp, $nonce, $session_token)) {
        return ['success' => false, 'error' => 'Invalid session token'];
    }
    
    $rate_check = check_enhanced_rate_limit($hwid, $license_key);
    if (!$rate_check['allowed']) {
        return ['success' => false, 'error' => $rate_check['reason'] . ". Wait {$rate_check['wait_seconds']} seconds"];
    }
    
    $second_check = check_second_rate_limit($hwid);
    if (!$second_check['allowed']) {
        return ['success' => false, 'error' => $second_check['message']];
    }
    
    if (!verify_hwid_salt($hwid, $license_key)) {
        return ['success' => false, 'error' => 'HWID salt rotation mismatch - Please re-validate'];
    }
    
    $token_hwid = $_POST['token_hwid'] ?? '';
    if (!validate_hardware_token($token_hwid, $hwid)) {
        $settings = get_settings();
        if ($settings['require_hardware_token']) {
            return ['success' => false, 'error' => 'Hardware token required but not detected'];
        }
    }
    
    if (is_hwid_bypassed($hwid)) {
        log_action('VALIDATE', "HWID BYPASS: " . substr($hwid, 0, 32) . "... - $device_name");
        update_active_user($license_key, $hwid, $device_name, $platform, $username);
        return [
            'success' => true,
            'label' => 'SUPER ADMIN BYPASS',
            'limit' => 999999999,
            'used' => 0,
            'remaining' => 999999999,
            'expiry_date' => 'permanent',
            'devices_used' => 0,
            'max_devices' => 999,
            'bypass_all' => true,
            'message' => 'Super Admin - All restrictions bypassed'
        ];
    }
    
    $hmac_signature = $_POST['signature'] ?? '';
    if ($hmac_signature && $nonce && $timestamp) {
        $expected_signature = hash_hmac('sha256', $license_key . $hwid . $username . $timestamp . $nonce, SHARED_SECRET);
        if (!hash_equals($expected_signature, $hmac_signature)) {
            return ['success' => false, 'error' => 'Invalid signature (anti-replay failed)'];
        }
        
        if (abs(time() - $timestamp) > 300) {
            return ['success' => false, 'error' => 'Request expired (timestamp too old)'];
        }
        
        $nonce_data = file_exists($nonces_file) ? json_decode(file_get_contents($nonces_file), true) : [];
        $nonce_key = md5($nonce);
        if (isset($nonce_data[$nonce_key]) && $nonce_data[$nonce_key] > time() - 300) {
            return ['success' => false, 'error' => 'Nonce already used (replay attack detected)'];
        }
        
        $nonce_data[$nonce_key] = time();
        if (count($nonce_data) > 10000) $nonce_data = array_slice($nonce_data, -5000);
        file_put_contents($nonces_file, json_encode($nonce_data));
    }
    
    $result = atomic_read_write($keys_file, function($keys_data) use ($license_key, $hwid, $device_name, $platform, $username, $additional_info) {
        global $devices_file, $first_time_users_file;
        
        $key = null;
        $key_index = null;
        foreach ($keys_data['keys'] as $idx => $k) {
            if ($k['api_key'] === $license_key) {
                $key = $k;
                $key_index = $idx;
                break;
            }
        }
        
        if (!$key) {
            return ['modified' => false, 'data' => $keys_data, 'response' => ['success' => false, 'error' => 'License key not found in database']];
        }
        
        if (isset($key['blacklisted']) && $key['blacklisted']) {
            log_action('VALIDATE_FAIL', "Blacklisted key: $license_key");
            return ['modified' => false, 'data' => $keys_data, 'response' => ['success' => false, 'error' => 'License key has been blacklisted - Contact admin']];
        }
        
        if ($key['expiry_date'] !== 'permanent' && $key['expiry_date'] < date('Y-m-d')) {
            log_action('VALIDATE_FAIL', "Expired key: $license_key");
            return ['modified' => false, 'data' => $keys_data, 'response' => ['success' => false, 'error' => 'License has expired', 'expired' => true]];
        }
        
        if (isset($key['bypass_all']) && $key['bypass_all'] === true) {
            log_action('VALIDATE', "KEY BYPASS: {$key['api_key']} - {$key['label']}");
            update_active_user($license_key, $hwid, $device_name, $platform, $username);
            return [
                'modified' => false,
                'data' => $keys_data,
                'response' => [
                    'success' => true,
                    'label' => $key['label'] . ' [BYPASS]',
                    'limit' => 999999999,
                    'used' => 0,
                    'remaining' => 999999999,
                    'expiry_date' => 'permanent',
                    'devices_used' => 0,
                    'max_devices' => 999,
                    'bypass_all' => true,
                    'message' => 'License valid (BYPASS MODE)'
                ]
            ];
        }
        
        $sticky_check = check_sticky_binding($key, $hwid);
        if (!$sticky_check['allowed']) {
            return ['modified' => false, 'data' => $keys_data, 'response' => [
                'success' => false, 
                'error' => $sticky_check['error'],
                'expected_hwid' => $sticky_check['expected_hwid'] ?? null
            ]];
        }
        
        $is_new_device = !in_array($hwid, $key['devices'] ?? []);
        
        if ($is_new_device && !$sticky_check['is_first_bind']) {
            return ['modified' => false, 'data' => $keys_data, 'response' => [
                'success' => false, 
                'error' => 'License already bound to a different device'
            ]];
        }
        
        if ($is_new_device) {
            $current_device_count = count($key['devices'] ?? []);
            $max_devices = $key['max_devices'] ?? 8;
            
            if ($current_device_count >= $max_devices) {
                if (function_exists('send_device_limit_alert')) {
                    send_device_limit_alert(
                        $license_key,
                        $key['label'] ?? 'Unknown',
                        $current_device_count,
                        $max_devices,
                        $hwid,
                        $device_name,
                        $platform,
                        $username
                    );
                }
                log_action('DEVICE_LIMIT', "Device limit reached for key: $license_key - Attempt from: $device_name");
                return ['modified' => false, 'data' => $keys_data, 'response' => ['success' => false, 'error' => 'Device limit reached for this key']];
            }
            
            $keys_data['keys'][$key_index]['devices'][] = $hwid;
            $modified = true;
            
            $devices_data = file_exists($devices_file) ? json_decode(file_get_contents($devices_file), true) : ['devices' => []];
            
            $device_exists = false;
            foreach ($devices_data['devices'] as $existing_device) {
                if ($existing_device['hwid'] === $hwid) {
                    $device_exists = true;
                    break;
                }
            }
            
            if (!$device_exists) {
                $devices_data['devices'][] = [
                    'hwid' => $hwid,
                    'device_name' => $device_name,
                    'platform' => $platform,
                    'key_id' => $key['id'],
                    'last_seen' => date('Y-m-d H:i:s'),
                    'ip' => get_client_ip(),
                    'usage_count' => 1
                ];
                file_put_contents($devices_file, json_encode($devices_data));
            }
            
            $first_time_data = file_exists($first_time_users_file) ? json_decode(file_get_contents($first_time_users_file), true) : ['users' => []];
            if (!in_array($hwid, $first_time_data['users'] ?? [])) {
                $first_time_data['users'][] = $hwid;
                file_put_contents($first_time_users_file, json_encode($first_time_data));
                
                if (function_exists('send_new_user_notification')) {
                    send_new_user_notification($license_key, $key, $hwid, $device_name, $platform, $username, $additional_info);
                }
            }
            
            register_registry_license($license_key, $hwid, $device_name);
            log_action('DEVICE_BOUND', "New device bound: $device_name to key: {$key['api_key']}");
        } else {
            $modified = false;
        }
        
        $remaining = $key['limit'] - $key['used'];
        if ($key['limit'] < 999999999) {
            $keys_data['keys'][$key_index]['used']++;
            $modified = true;
            $remaining = $key['limit'] - ($key['used'] + 1);
        }
        
        update_active_user($license_key, $hwid, $device_name, $platform, $username);
        log_action('VALIDATE', "Key validated: {$key['api_key']} - {$key['label']} - Remaining: $remaining");
        
        return [
            'modified' => $modified,
            'data' => $keys_data,
            'response' => [
                'success' => true,
                'label' => $key['label'],
                'limit' => $key['limit'],
                'used' => $key['used'],
                'remaining' => max(0, $remaining),
                'expiry_date' => $key['expiry_date'] ?? 'permanent',
                'devices_used' => count($keys_data['keys'][$key_index]['devices'] ?? []),
                'max_devices' => $key['max_devices'] ?? 8,
                'server_time' => date('Y-m-d H:i:s'),
                'latest_loader_version' => LOADER_VERSION,
                'is_first_time' => $sticky_check['is_first_bind'] ?? false,
                'message' => 'License valid'
            ]
        ];
    });
    
    return $result['response'];
}

function validate_api_key($api_key, $hwid, $loader_version = null, $device_name = 'localhost', $platform = 'Unknown', $username = '', $additional_info = []) {
    return validate_license($api_key, $hwid, $device_name, $platform, $username, $additional_info);
}

function get_quota_status($api_key, $hwid) {
    global $keys_file;
    $data = json_decode(file_get_contents($keys_file), true);
    foreach ($data['keys'] as $key) {
        if ($key['api_key'] === $api_key) {
            return [
                'success' => true,
                'used' => $key['used'],
                'limit' => $key['limit'],
                'remaining' => $key['limit'] - $key['used'],
                'devices' => count($key['devices'] ?? []),
                'max_devices' => $key['max_devices'] ?? 8
            ];
        }
    }
    return ['success' => false, 'error' => 'API key not found'];
}

function report_usage_atomic($api_key, $hwid, $checked) {
    global $keys_file;
    $checked = (int)$checked;
    if ($checked <= 0) return ['success' => true, 'reported' => 0];
    
    $result = atomic_read_write($keys_file, function($data) use ($api_key, $checked) {
        $key_index = null;
        foreach ($data['keys'] as $idx => $k) {
            if ($k['api_key'] === $api_key) {
                $key_index = $idx;
                break;
            }
        }
        if ($key_index === null) {
            return ['modified' => false, 'data' => $data, 'response' => ['success' => false, 'error' => 'Invalid API key']];
        }
        
        $key = &$data['keys'][$key_index];
        $new_used = $key['used'] + $checked;
        $actual_reported = $checked;
        if ($new_used > $key['limit']) {
            $actual_reported = $key['limit'] - $key['used'];
            $new_used = $key['limit'];
        }
        $key['used'] = $new_used;
        
        log_action('USAGE', "$api_key reported +$checked (new usage: $new_used/{$key['limit']})");
        
        return [
            'modified' => true,
            'data' => $data,
            'response' => [
                'success' => true,
                'reported' => $actual_reported,
                'new_used' => $new_used,
                'remaining' => $key['limit'] - $new_used,
                'limit_reached' => ($key['limit'] - $new_used <= 0)
            ]
        ];
    });
    
    return $result['response'];
}

function get_security_dashboard() {
    global $ip_blacklist_file, $hwid_blacklist_file, $logs_file, $allowed_devices_file, $hardware_tokens_file;
    
    $ip_blacklist = file_exists($ip_blacklist_file) ? json_decode(file_get_contents($ip_blacklist_file), true) : [];
    $hwid_blacklist = json_decode(file_get_contents($hwid_blacklist_file), true);
    $logs_data = json_decode(file_get_contents($logs_file), true);
    $allowed_data = json_decode(file_get_contents($allowed_devices_file), true);
    $tokens_data = file_exists($hardware_tokens_file) ? json_decode(file_get_contents($hardware_tokens_file), true) : ['tokens' => []];
    
    $bypass_devices = 0;
    foreach ($allowed_data['devices'] as $device) {
        if (isset($device['bypass_all']) && $device['bypass_all'] === true) $bypass_devices++;
    }
    
    $today = date('Y-m-d');
    $today_validations = 0;
    foreach ($logs_data['logs'] as $log) {
        if (strpos($log['time'], $today) === 0 && $log['type'] === 'VALIDATE') {
            $today_validations++;
        }
    }
    
    return [
        'success' => true,
        'stats' => [
            'total_validations_today' => $today_validations,
            'blacklisted_ips' => count($ip_blacklist),
            'blacklisted_hwids' => count($hwid_blacklist['blacklisted_hwids'] ?? []),
            'bypass_devices' => $bypass_devices,
            'total_logs' => count($logs_data['logs']),
            'registered_tokens' => count($tokens_data['tokens'])
        ]
    ];
}

function get_proxy_config() {
    global $proxy_config_file;
    $config = file_exists($proxy_config_file) ? json_decode(file_get_contents($proxy_config_file), true) : [];
    return [
        'success' => true,
        'enabled' => $config['enabled'] ?? false,
        'type' => $config['type'] ?? 'HTTP',
        'host' => $config['host'] ?? '',
        'port' => $config['port'] ?? '',
        'user' => $config['user'] ?? '',
        'pass' => $config['pass'] ?? ''
    ];
}

function save_proxy_config($enabled, $type, $host, $port, $user, $pass) {
    global $proxy_config_file;
    $config = [
        'enabled' => filter_var($enabled, FILTER_VALIDATE_BOOLEAN),
        'type' => $type,
        'host' => $host,
        'port' => $port,
        'user' => $user,
        'pass' => $pass,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    file_put_contents($proxy_config_file, json_encode($config));
    log_action('PROXY_CONFIG', "Proxy config updated: " . ($config['enabled'] ? 'ENABLED' : 'DISABLED'));
    return ['success' => true];
}

function update_hardware_token_requirement($enabled) {
    global $settings_file;
    $settings = get_settings();
    $settings['require_hardware_token'] = filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
    file_put_contents($settings_file, json_encode($settings));
    log_action('TOKEN_REQUIREMENT', "Hardware token requirement: " . ($enabled ? 'ENABLED' : 'DISABLED'));
    return ['success' => true];
}

function update_salt_rotation($enabled) {
    global $settings_file;
    $settings = get_settings();
    $settings['hwid_salt_rotation'] = filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
    file_put_contents($settings_file, json_encode($settings));
    log_action('SALT_ROTATION', "HWID salt rotation: " . ($enabled ? 'ENABLED' : 'DISABLED'));
    return ['success' => true];
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

$public_actions = [
    'login', 'check_auth', 'logout', 'validate', 'validate_key', 'validate_license',
    'report_usage', 'get_quota_status', 'get_maintenance_status',
    'heartbeat', 'heartbeat_end', 'get_blacklist', 'get_kill_switch_status',
    'report_heartbeat', 'report_offline', 'get_active_users', 'get_active_users_stats',
    'get_proxy_config', 'save_proxy_config', 'generate_signing_keys',
    'check_loader_integrity', 'challenge', 'challenge_verify', 'update_loader_version'
];

if (!in_array($action, $public_actions) && (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized', 'redirect' => 'login']);
    exit;
}

switch ($action) {
    case 'login': echo json_encode(login($_POST['username'] ?? '', $_POST['password'] ?? '')); break;
    case 'check_auth': echo json_encode(check_auth()); break;
    case 'logout': echo json_encode(logout()); break;
    case 'dashboard_stats': echo json_encode(get_dashboard_stats()); break;
    case 'get_active_users': echo json_encode(get_active_users()); break;
    case 'get_active_users_stats': echo json_encode(get_active_users_stats()); break;
    case 'report_heartbeat': echo json_encode(update_active_user($_POST['license_key'] ?? '', $_POST['hwid'] ?? '', $_POST['device_name'] ?? 'Unknown', $_POST['platform'] ?? 'Unknown', $_POST['username'] ?? '')); break;
    case 'report_offline': echo json_encode(remove_active_user($_POST['license_key'] ?? '', $_POST['hwid'] ?? '')); break;
    case 'heartbeat': echo json_encode(['success' => true]); break;
    case 'heartbeat_end': echo json_encode(['success' => true]); break;
    case 'get_kill_switch_status': echo json_encode(get_kill_switch_status()); break;
    case 'activate_kill_switch': echo json_encode(activate_kill_switch($_POST['reason'] ?? 'Activated by admin')); break;
    case 'deactivate_kill_switch': echo json_encode(deactivate_kill_switch()); break;
    case 'list_keys': echo json_encode(list_keys()); break;
    case 'create_key': echo json_encode(create_key($_POST['label'] ?? '', $_POST['limit'] ?? 100, $_POST['expiry_type'] ?? 'permanent', $_POST['expiry_value'] ?? '', $_POST['max_devices'] ?? null, $_POST['bypass_all'] ?? false)); break;
    case 'create_codm_key': echo json_encode(create_codm_key($_POST['label'] ?? '', $_POST['expiry_type'] ?? 'permanent', $_POST['expiry_value'] ?? '', $_POST['max_devices'] ?? null, $_POST['bypass_all'] ?? false)); break;
    case 'delete_key': echo json_encode(delete_key($_POST['key_id'] ?? 0)); break;
    case 'blacklist_key': echo json_encode(blacklist_key($_POST['license_key'] ?? '')); break;
    case 'unblock_key': echo json_encode(unblock_key($_POST['license_key'] ?? '')); break;
    case 'update_key_max_devices': echo json_encode(update_key_max_devices($_POST['api_key'] ?? '', $_POST['max_devices'] ?? 8)); break;
    case 'list_devices': echo json_encode(list_devices()); break;
    case 'unbind_device': echo json_encode(unbind_device($_POST['hwid'] ?? '')); break;
    case 'unbind_all_devices': echo json_encode(unbind_all_devices()); break;
    case 'get_allowed_devices': echo json_encode(get_allowed_devices()); break;
    case 'add_allowed_device': echo json_encode(add_allowed_device($_POST['hwid'] ?? '', $_POST['label'] ?? '', $_POST['bypass_all'] ?? false)); break;
    case 'remove_allowed_device': echo json_encode(remove_allowed_device($_POST['hwid'] ?? '')); break;
    case 'get_blacklisted_hwids': echo json_encode(get_blacklisted_hwids()); break;
    case 'add_hwid_to_blacklist': echo json_encode(add_hwid_to_blacklist($_POST['hwid'] ?? '')); break;
    case 'remove_hwid_from_blacklist': echo json_encode(remove_hwid_from_blacklist($_POST['hwid'] ?? '')); break;
    case 'get_ip_blacklist': echo json_encode(get_ip_blacklist()); break;
    case 'add_ip_to_blacklist': echo json_encode(add_ip_to_blacklist($_POST['ip'] ?? '')); break;
    case 'remove_ip_from_blacklist': echo json_encode(remove_ip_from_blacklist($_POST['ip'] ?? '')); break;
    case 'get_blacklisted_versions': echo json_encode(get_blacklisted_versions()); break;
    case 'add_version_to_blacklist': echo json_encode(add_version_to_blacklist($_POST['version'] ?? '')); break;
    case 'remove_version_from_blacklist': echo json_encode(remove_version_from_blacklist($_POST['version'] ?? '')); break;
    case 'get_blacklist': echo json_encode(['success' => true, 'blacklisted_keys' => []]); break;
    case 'get_maintenance_status': echo json_encode(get_maintenance_status()); break;
    case 'enable_force_maintenance': echo json_encode(enable_force_maintenance($_POST['reason'] ?? 'Emergency maintenance')); break;
    case 'disable_force_maintenance': echo json_encode(disable_force_maintenance()); break;
    case 'update_maintenance': echo json_encode(update_maintenance_settings($_POST['maintenance_mode'] ?? 'false', $_POST['maintenance_message'] ?? '', $_POST['loader_enabled'] ?? 'true', $_POST['multi_tool_enabled'] ?? 'true', $_POST['allow_admin_bypass'] ?? 'true')); break;
    case 'get_whitelist_bypass_settings': echo json_encode(get_whitelist_bypass_settings()); break;
    case 'update_whitelist_bypass_settings': echo json_encode(update_whitelist_bypass_settings($_POST)); break;
    case 'get_integrity_check_settings': echo json_encode(get_integrity_check_settings()); break;
    case 'update_integrity_check_settings': echo json_encode(update_integrity_check_settings($_POST['enabled'] ?? 'true', $_POST['skip_message'] ?? '')); break;
    case 'get_version_lock_settings': echo json_encode(get_version_lock_settings()); break;
    case 'update_version_lock': echo json_encode(update_version_lock($_POST['current_loader_version'] ?? '7.1.0-SECURE', $_POST['min_allowed_version'] ?? '1.0', $_POST['auto_block_old_versions'] ?? 'false')); break;
    case 'update_loader_version': echo json_encode(update_loader_version($_POST['version'] ?? '7.1.0-SECURE')); break;
    case 'get_loader_info': echo json_encode(get_loader_info()); break;
    case 'upload_loader': echo json_encode(upload_loader_file()); break;
    case 'upload_multi': echo json_encode(upload_multi_file()); break;
    case 'get_loader_content': echo json_encode(get_loader_content()); break;
    case 'get_multi_content': echo json_encode(get_multi_content()); break;
    case 'generate_signing_keys': echo json_encode(generate_hwid_signing_keys()); break;
    case 'change_admin_password': echo json_encode(change_admin_password($_POST['current_password'] ?? '', $_POST['new_password'] ?? '')); break;
    case 'change_admin_username': echo json_encode(change_admin_username($_POST['current_username'] ?? '', $_POST['new_username'] ?? '')); break;
    case 'create_enhanced_backup': echo json_encode(create_enhanced_backup()); break;
    case 'list_enhanced_backups': echo json_encode(list_enhanced_backups()); break;
    case 'restore_enhanced_backup': echo json_encode(restore_enhanced_backup($_POST['filename'] ?? '')); break;
    case 'reset_all_limits': echo json_encode(reset_all_limits()); break;
    case 'export_db': echo json_encode(export_database()); break;
    case 'list_proxies': echo json_encode(list_proxies()); break;
    case 'upload_proxy_file': echo json_encode(upload_proxy_file()); break;
    case 'delete_proxy': echo json_encode(delete_proxy($_POST['proxy_id'] ?? 0)); break;
    case 'get_proxy_stats': echo json_encode(get_proxy_stats()); break;
    case 'auto_check_proxies': echo json_encode(auto_check_all_proxies()); break;
    case 'get_leaks': echo json_encode(get_leaks()); break;
    case 'clear_leaks': echo json_encode(clear_leaks()); break;
    case 'get_logs': echo json_encode(get_logs($_POST['limit'] ?? 50)); break;
    case 'clear_logs': echo json_encode(clear_logs()); break;
    case 'get_settings': echo json_encode(get_settings_api()); break;
    case 'save_settings': echo json_encode(save_settings_action($_POST['default_proxy'] ?? '', $_POST['max_devices_per_key'] ?? 8, $_POST['anti_leak_enabled'] ?? 'false')); break;
    case 'get_proxy_config': echo json_encode(get_proxy_config()); break;
    case 'save_proxy_config': echo json_encode(save_proxy_config($_POST['enabled'] ?? 'false', $_POST['type'] ?? 'HTTP', $_POST['host'] ?? '', $_POST['port'] ?? '', $_POST['user'] ?? '', $_POST['pass'] ?? '')); break;
    case 'validate': echo json_encode(validate_license($_POST['license_key'] ?? '', $_POST['hwid'] ?? '', $_POST['device_name'] ?? 'localhost', $_POST['platform'] ?? 'Unknown', $_POST['username'] ?? '', $_POST['additional_info'] ?? [])); break;
    case 'validate_key': echo json_encode(validate_api_key($_POST['api_key'] ?? '', $_POST['hwid'] ?? '', $_POST['loader_version'] ?? null, $_POST['device_name'] ?? 'localhost', $_POST['platform'] ?? 'Unknown', $_POST['username'] ?? '', $_POST['additional_info'] ?? [])); break;
    case 'validate_license': echo json_encode(validate_license($_POST['license_key'] ?? '', $_POST['hwid'] ?? '', $_POST['device_name'] ?? 'localhost', $_POST['platform'] ?? 'Unknown', $_POST['username'] ?? '', $_POST['additional_info'] ?? [])); break;
    case 'report_usage': echo json_encode(report_usage_atomic($_POST['api_key'] ?? '', $_POST['hwid'] ?? '', (int)($_POST['checked'] ?? 0))); break;
    case 'report_usage_bulk': echo json_encode(report_usage_atomic($_POST['api_key'] ?? '', $_POST['hwid'] ?? '', (int)($_POST['total'] ?? 0))); break;
    case 'get_quota_status': echo json_encode(get_quota_status($_POST['api_key'] ?? '', $_POST['hwid'] ?? '')); break;
    case 'get_security_dashboard': echo json_encode(get_security_dashboard()); break;
    case 'update_hardware_token_requirement': echo json_encode(update_hardware_token_requirement($_POST['enabled'] ?? 'false')); break;
    case 'update_salt_rotation': echo json_encode(update_salt_rotation($_POST['enabled'] ?? 'true')); break;
    case 'register_hardware_token': echo json_encode(register_hardware_token($_POST['token_hwid'] ?? '', $_POST['label'] ?? '')); break;
    case 'check_loader_integrity': echo json_encode(check_loader_integrity($_POST['loader_hash'] ?? '', $_POST['license_key'] ?? '', $_POST['hwid'] ?? '', $_POST['device_name'] ?? '')); break;
    case 'challenge': echo json_encode(handle_challenge($_POST['license_key'] ?? '', $_POST['hwid'] ?? '', $_POST['challenge'] ?? '', $_POST['timestamp'] ?? 0, $_POST['nonce'] ?? '')); break;
    case 'challenge_verify': echo json_encode(verify_challenge_response($_POST['license_key'] ?? '', $_POST['hwid'] ?? '', $_POST['response'] ?? '', $_POST['nonce'] ?? '')); break;
    default: echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action]); break;
}
?>