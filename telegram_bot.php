<?php

define('TELEGRAM_BOT_TOKEN', '8721270428:AAERh2VtCOyqISpPSinAKDhEKUsipV9JPXU');
define('TELEGRAM_CHAT_ID', '8125585735');

define('PROXY_ENABLED', false);
define('PROXY_TYPE', 'HTTP');
define('PROXY_HOST', '');
define('PROXY_PORT', '');
define('PROXY_USER', '');
define('PROXY_PASS', '');

$proxy_config_file = __DIR__ . '/admin_data/proxy_config.json';
if (file_exists($proxy_config_file)) {
    $proxy_config = json_decode(file_get_contents($proxy_config_file), true);
    if ($proxy_config && isset($proxy_config['enabled']) && $proxy_config['enabled']) {
        define('PROXY_ENABLED', true);
        define('PROXY_TYPE', $proxy_config['type'] ?? 'HTTP');
        define('PROXY_HOST', $proxy_config['host'] ?? '');
        define('PROXY_PORT', $proxy_config['port'] ?? '');
        define('PROXY_USER', $proxy_config['user'] ?? '');
        define('PROXY_PASS', $proxy_config['pass'] ?? '');
    }
}

function get_full_geolocation($ip) {
    if ($ip === 'Unknown' || !filter_var($ip, FILTER_VALIDATE_IP)) {
        return [
            'ip' => $ip, 'country' => 'Unknown', 'country_code' => 'XX',
            'region' => 'Unknown', 'city' => 'Unknown', 'zip' => 'Unknown',
            'lat' => '0', 'lon' => '0', 'timezone' => 'Unknown',
            'isp' => 'Unknown', 'org' => 'Unknown', 'as' => 'Unknown',
            'proxy' => false, 'hosting' => false, 'mobile' => false
        ];
    }
    
    $url = "http://ip-api.com/json/$ip?fields=status,country,countryCode,region,regionName,city,zip,lat,lon,timezone,isp,org,as,mobile,proxy,hosting,query";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    if (function_exists('apply_proxy_to_curl')) {
        apply_proxy_to_curl($ch);
    }
    
    $resp = curl_exec($ch);
    curl_close($ch);
    
    if ($resp) {
        $data = json_decode($resp, true);
        if ($data && isset($data['status']) && $data['status'] === 'success') {
            return [
                'ip' => $data['query'] ?? $ip,
                'country' => $data['country'] ?? 'Unknown',
                'country_code' => $data['countryCode'] ?? 'XX',
                'region' => $data['regionName'] ?? 'Unknown',
                'city' => $data['city'] ?? 'Unknown',
                'zip' => $data['zip'] ?? 'Unknown',
                'lat' => $data['lat'] ?? '0',
                'lon' => $data['lon'] ?? '0',
                'timezone' => $data['timezone'] ?? 'Unknown',
                'isp' => $data['isp'] ?? 'Unknown',
                'org' => $data['org'] ?? 'Unknown',
                'as' => $data['as'] ?? 'Unknown',
                'mobile' => $data['mobile'] ?? false,
                'proxy' => $data['proxy'] ?? false,
                'hosting' => $data['hosting'] ?? false
            ];
        }
    }
    
    return [
        'ip' => $ip, 'country' => 'Unknown', 'country_code' => 'XX',
        'region' => 'Unknown', 'city' => 'Unknown', 'zip' => 'Unknown',
        'lat' => '0', 'lon' => '0', 'timezone' => 'Unknown',
        'isp' => 'Unknown', 'org' => 'Unknown', 'as' => 'Unknown',
        'proxy' => false, 'hosting' => false, 'mobile' => false
    ];
}

function send_telegram_message($message) {
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
    $postData = [
        'chat_id' => TELEGRAM_CHAT_ID,
        'text' => $message,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    if (function_exists('apply_proxy_to_curl')) {
        apply_proxy_to_curl($ch);
    } elseif (defined('PROXY_ENABLED') && PROXY_ENABLED && defined('PROXY_HOST') && PROXY_HOST) {
        curl_setopt($ch, CURLOPT_PROXY, PROXY_HOST);
        curl_setopt($ch, CURLOPT_PROXYPORT, PROXY_PORT);
        if (defined('PROXY_USER') && PROXY_USER && defined('PROXY_PASS') && PROXY_PASS) {
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, PROXY_USER . ':' . PROXY_PASS);
        }
        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $http_code === 200;
}

function send_device_limit_alert($license_key, $key_label, $current_devices, $max_devices, $hwid, $device_name, $platform, $username = '', $additional_info = []) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $loc = get_full_geolocation($ip);
    
    $line = "═══════════════════════════════════════════════════════════════════════════════";
    $thin = "───────────────────────────────────────────────────────────────────────────────";
    
    $msg = "🚨🚨🚨 <b>⚠️ DEVICE LIMIT REACHED! ACCESS DENIED ⚠️</b> 🚨🚨🚨\n\n";
    $msg .= $line . "\n";
    $msg .= "🕐 <b>Time:</b> " . date('Y-m-d H:i:s') . " UTC\n";
    $msg .= "📅 <b>Date:</b> " . date('F j, Y') . "\n";
    $msg .= $line . "\n\n";
    
    $msg .= "<b>🔑 LICENSE INFORMATION</b>\n";
    $msg .= $thin . "\n";
    $msg .= "   ├─ <b>License Key:</b> <code>$license_key</code>\n";
    $msg .= "   ├─ <b>Label/Plan:</b> $key_label\n";
    $msg .= "   ├─ <b>Devices Used:</b> <b>$current_devices / $max_devices</b>\n";
    $msg .= "   ├─ <b>Status:</b> <b>⚠️ LIMIT REACHED – ACCESS DENIED</b>\n";
    $msg .= "   └─ <b>Action Needed:</b> Unbind a device or increase limit\n\n";
    
    $msg .= "<b>🛑 BLOCKED DEVICE (Attempting to connect)</b>\n";
    $msg .= $thin . "\n";
    if ($username) {
        $msg .= "   ├─ <b>Telegram:</b> @$username\n";
    }
    $msg .= "   ├─ <b>Device Name:</b> $device_name\n";
    $msg .= "   ├─ <b>Platform:</b> $platform\n";
    $msg .= "   ├─ <b>HWID:</b> <code>" . substr($hwid, 0, 48) . "...</code>\n";
    $msg .= "   ├─ <b>Full HWID:</b> <code>$hwid</code>\n";
    $msg .= "   └─ <b>IP Address:</b> <code>$ip</code>\n\n";
    
    $msg .= "<b>📍 LOCATION DETAILS</b>\n";
    $msg .= $thin . "\n";
    $msg .= "   ├─ <b>Country:</b> {$loc['country']} ({$loc['country_code']})\n";
    $msg .= "   ├─ <b>City:</b> {$loc['city']}\n";
    $msg .= "   ├─ <b>Region:</b> {$loc['region']}\n";
    $msg .= "   ├─ <b>Zip/Postal:</b> {$loc['zip']}\n";
    $msg .= "   ├─ <b>Timezone:</b> {$loc['timezone']}\n";
    $msg .= "   ├─ <b>ISP:</b> {$loc['isp']}\n";
    $msg .= "   ├─ <b>Organization:</b> {$loc['org']}\n";
    $msg .= "   ├─ <b>ASN:</b> {$loc['as']}\n";
    $msg .= "   ├─ <b>Coordinates:</b> {$loc['lat']}, {$loc['lon']}\n";
    $msg .= "   ├─ <b>Mobile Carrier:</b> " . ($loc['mobile'] ? '✅ Yes' : '❌ No') . "\n";
    $msg .= "   ├─ <b>Proxy/VPN:</b> " . ($loc['proxy'] ? '⚠️ DETECTED' : '✓ Clean') . "\n";
    $msg .= "   └─ <b>Hosting/Cloud:</b> " . ($loc['hosting'] ? '✅ Yes' : '❌ No') . "\n\n";
    
    if (!empty($additional_info)) {
        $msg .= "<b>📱 ADDITIONAL DEVICE INFO</b>\n";
        $msg .= $thin . "\n";
        if (isset($additional_info['android_model'])) {
            $msg .= "   ├─ <b>Device Model:</b> {$additional_info['android_model']}\n";
        }
        if (isset($additional_info['android_manufacturer'])) {
            $msg .= "   ├─ <b>Manufacturer:</b> {$additional_info['android_manufacturer']}\n";
        }
        if (isset($additional_info['android_brand'])) {
            $msg .= "   ├─ <b>Brand:</b> {$additional_info['android_brand']}\n";
        }
        if (isset($additional_info['android_version'])) {
            $msg .= "   ├─ <b>Android Version:</b> {$additional_info['android_version']}\n";
        }
        if (isset($additional_info['sdk_version'])) {
            $msg .= "   ├─ <b>SDK Version:</b> {$additional_info['sdk_version']}\n";
        }
        if (isset($additional_info['cpu_model'])) {
            $msg .= "   ├─ <b>CPU:</b> " . substr($additional_info['cpu_model'], 0, 50) . "\n";
        }
        if (isset($additional_info['cpu_cores'])) {
            $msg .= "   ├─ <b>CPU Cores:</b> {$additional_info['cpu_cores']}\n";
        }
        if (isset($additional_info['ram_total_gb'])) {
            $msg .= "   ├─ <b>RAM:</b> {$additional_info['ram_total_gb']} GB\n";
        }
        if (isset($additional_info['screen_resolution'])) {
            $msg .= "   └─ <b>Screen:</b> {$additional_info['screen_resolution']}\n";
        }
        $msg .= "\n";
    }
    
    $msg .= "<b>🖥️ SERVER INFORMATION</b>\n";
    $msg .= $thin . "\n";
    $msg .= "   ├─ <b>PHP Version:</b> " . phpversion() . "\n";
    $msg .= "   ├─ <b>Server Time:</b> " . date('Y-m-d H:i:s') . " UTC\n";
    $msg .= "   └─ <b>Script:</b> " . basename(__FILE__) . "\n\n";
    
    $msg .= $line . "\n";
    $msg .= "<b>🔴 ACTION REQUIRED (ADMIN)</b>\n";
    $msg .= $thin . "\n";
    $msg .= "   • This device was <b>BLOCKED</b> from using the license\n";
    $msg .= "   • License has reached its <b>maximum device limit</b>\n";
    $msg .= "   • To allow more devices:\n";
    $msg .= "     1. Unbind an existing device from admin panel\n";
    $msg .= "     2. Increase the max devices limit for this key\n";
    $msg .= "     3. Create a new license key for this user\n";
    $msg .= $line . "\n";
    $msg .= "\n📞 <b>Support:</b> @Khatelynnnnnn\n";
    $msg .= "🔗 <b>Admin Panel:</b> " . ($_SERVER['HTTP_HOST'] ?? 'admin') . "\n";
    $msg .= $line;
    
    send_telegram_message($msg);
}

function send_new_user_notification($license_key, $key, $hwid, $device_name, $platform, $username, $additional_info = []) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $loc = get_full_geolocation($ip);
    
    $line = "═══════════════════════════════════════════════════════════════════════════════";
    $thin = "───────────────────────────────────────────────────────────────────────────────";
    
    $msg = "🎉🎉🎉 <b>✨ NEW USER REGISTERED! ✨</b> 🎉🎉🎉\n\n";
    $msg .= $line . "\n";
    $msg .= "🕐 <b>Time:</b> " . date('Y-m-d H:i:s') . " UTC\n";
    $msg .= $line . "\n\n";
    
    $msg .= "<b>🔑 LICENSE DETAILS</b>\n";
    $msg .= $thin . "\n";
    $msg .= "   ├─ <b>License Key:</b> <code>$license_key</code>\n";
    $msg .= "   ├─ <b>Label:</b> " . ($key['label'] ?? 'Unknown') . "\n";
    $msg .= "   ├─ <b>Device Usage:</b> " . count($key['devices'] ?? []) . " / " . ($key['max_devices'] ?? 8) . "\n";
    $msg .= "   ├─ <b>Remaining Slots:</b> " . (($key['max_devices'] ?? 8) - count($key['devices'] ?? [])) . "\n";
    $msg .= "   ├─ <b>Quota Used:</b> " . number_format($key['used'] ?? 0) . " / " . number_format($key['limit'] ?? 0) . "\n";
    $msg .= "   └─ <b>Expires:</b> " . ($key['expiry_date'] ?? 'permanent') . "\n\n";
    
    $msg .= "<b>👤 USER DETAILS</b>\n";
    $msg .= $thin . "\n";
    $msg .= "   ├─ <b>Telegram:</b> @$username\n";
    $msg .= "   ├─ <b>Device Name:</b> $device_name\n";
    $msg .= "   ├─ <b>Platform:</b> $platform\n";
    $msg .= "   ├─ <b>HWID:</b> <code>" . substr($hwid, 0, 48) . "...</code>\n";
    $msg .= "   └─ <b>IP Address:</b> <code>$ip</code>\n\n";
    
    $msg .= "<b>📍 LOCATION</b>\n";
    $msg .= $thin . "\n";
    $msg .= "   ├─ <b>Country:</b> {$loc['country']} ({$loc['country_code']})\n";
    $msg .= "   ├─ <b>City:</b> {$loc['city']}\n";
    $msg .= "   ├─ <b>Region:</b> {$loc['region']}\n";
    $msg .= "   ├─ <b>ISP:</b> {$loc['isp']}\n";
    $msg .= "   ├─ <b>Coordinates:</b> {$loc['lat']}, {$loc['lon']}\n";
    $msg .= "   └─ <b>Proxy/VPN:</b> " . ($loc['proxy'] ? '⚠️ DETECTED' : '✓ Clean') . "\n\n";
    
    if (!empty($additional_info)) {
        $msg .= "<b>📱 DEVICE SPECIFICATIONS</b>\n";
        $msg .= $thin . "\n";
        if (isset($additional_info['android_model'])) {
            $msg .= "   ├─ <b>Model:</b> {$additional_info['android_model']}\n";
        }
        if (isset($additional_info['android_manufacturer'])) {
            $msg .= "   ├─ <b>Manufacturer:</b> {$additional_info['android_manufacturer']}\n";
        }
        if (isset($additional_info['cpu_model'])) {
            $msg .= "   ├─ <b>CPU:</b> " . substr($additional_info['cpu_model'], 0, 45) . "\n";
        }
        if (isset($additional_info['ram_total_gb'])) {
            $msg .= "   ├─ <b>RAM:</b> {$additional_info['ram_total_gb']} GB\n";
        }
        if (isset($additional_info['screen_resolution'])) {
            $msg .= "   ├─ <b>Screen:</b> {$additional_info['screen_resolution']}\n";
        }
        if (isset($additional_info['battery_percent'])) {
            $msg .= "   └─ <b>Battery:</b> {$additional_info['battery_percent']}%\n";
        }
        $msg .= "\n";
    }
    
    $msg .= $line . "\n";
    $msg .= "<b>✅ New device successfully bound to license!</b>\n";
    $msg .= $line . "\n";
    $msg .= "\n📞 <b>Support:</b> @Khatelynnnnnn";
    
    send_telegram_message($msg);
}

function send_anti_leak_alert($license_key, $key, $hwid, $device_name, $platform, $username, $reason, $additional_info = []) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $loc = get_full_geolocation($ip);
    
    $line = "═══════════════════════════════════════════════════════════════════════════════";
    $thin = "───────────────────────────────────────────────────────────────────────────────";
    
    $msg = "🚨🚨🚨 <b>⚠️ ANTI-LEAK VIOLATION DETECTED! LICENSE REVOKED ⚠️</b> 🚨🚨🚨\n\n";
    $msg .= $line . "\n";
    $msg .= "🕐 <b>Time:</b> " . date('Y-m-d H:i:s') . " UTC\n";
    $msg .= $line . "\n\n";
    
    $msg .= "<b>🔑 LICENSE INFORMATION</b>\n";
    $msg .= $thin . "\n";
    $msg .= "   ├─ <b>License Key:</b> <code>$license_key</code>\n";
    $msg .= "   ├─ <b>Label:</b> " . ($key['label'] ?? 'Unknown') . "\n";
    $msg .= "   ├─ <b>Used:</b> " . number_format($key['used'] ?? 0) . " / " . number_format($key['limit'] ?? 0) . "\n";
    $msg .= "   └─ <b>Expires:</b> " . ($key['expiry_date'] ?? 'N/A') . "\n\n";
    
    $msg .= "<b>👤 VIOLATOR INFORMATION</b>\n";
    $msg .= $thin . "\n";
    $msg .= "   ├─ <b>Telegram:</b> @$username\n";
    $msg .= "   ├─ <b>Device:</b> $device_name ($platform)\n";
    $msg .= "   ├─ <b>HWID:</b> <code>" . substr($hwid, 0, 48) . "...</code>\n";
    $msg .= "   └─ <b>IP:</b> <code>$ip</code>\n\n";
    
    $msg .= "<b>📍 LOCATION</b>\n";
    $msg .= $thin . "\n";
    $msg .= "   ├─ <b>Country:</b> {$loc['country']} ({$loc['country_code']})\n";
    $msg .= "   ├─ <b>City:</b> {$loc['city']}\n";
    $msg .= "   ├─ <b>ISP:</b> {$loc['isp']}\n";
    $msg .= "   └─ <b>Proxy/VPN:</b> " . ($loc['proxy'] ? '⚠️ DETECTED' : '✓ Clean') . "\n\n";
    
    $msg .= "<b>⚠️ VIOLATION DETAILS</b>\n";
    $msg .= $thin . "\n";
    $msg .= "   └─ <b>Reason:</b> $reason\n\n";
    
    $msg .= $line . "\n";
    $msg .= "<b>🔴 LICENSE PERMANENTLY REVOKED</b>\n";
    $msg .= "<b>🚫 HWID BLACKLISTED</b>\n";
    $msg .= "<b>🔒 IP BLOCKED</b>\n";
    $msg .= $line . "\n";
    $msg .= "\n📞 <b>Support:</b> @Khatelynnnnnn";
    
    send_telegram_message($msg);
}

function send_tamper_alert($license_key, $key, $hwid, $device_name, $platform, $expected_hash, $actual_hash) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $loc = get_full_geolocation($ip);
    
    $line = "═══════════════════════════════════════════════════════════════════════════════";
    $thin = "───────────────────────────────────────────────────────────────────────────────";
    
    $msg = "🔐🔐🔐 <b>⚠️ LOADER TAMPERING DETECTED! ⚠️</b> 🔐🔐🔐\n\n";
    $msg .= $line . "\n";
    $msg .= "🕐 <b>Time:</b> " . date('Y-m-d H:i:s') . " UTC\n";
    $msg .= $line . "\n\n";
    
    $msg .= "<b>🔑 LICENSE INFORMATION</b>\n";
    $msg .= $thin . "\n";
    $msg .= "   ├─ <b>License Key:</b> <code>$license_key</code>\n";
    $msg .= "   ├─ <b>Label:</b> " . ($key['label'] ?? 'Unknown') . "\n";
    $msg .= "   └─ <b>Status:</b> <b>⚠️ ACCESS DENIED</b>\n\n";
    
    $msg .= "<b>👤 OFFENDING DEVICE</b>\n";
    $msg .= $thin . "\n";
    $msg .= "   ├─ <b>Device:</b> $device_name ($platform)\n";
    $msg .= "   ├─ <b>HWID:</b> <code>" . substr($hwid, 0, 48) . "...</code>\n";
    $msg .= "   └─ <b>IP:</b> <code>$ip</code>\n\n";
    
    $msg .= "<b>📍 LOCATION</b>\n";
    $msg .= $thin . "\n";
    $msg .= "   ├─ <b>Country:</b> {$loc['country']} ({$loc['country_code']})\n";
    $msg .= "   ├─ <b>City:</b> {$loc['city']}\n";
    $msg .= "   └─ <b>ISP:</b> {$loc['isp']}\n\n";
    
    $msg .= "<b>🔍 TAMPER DETAILS</b>\n";
    $msg .= $thin . "\n";
    $msg .= "   ├─ <b>Expected Hash:</b> <code>$expected_hash</code>\n";
    $msg .= "   ├─ <b>Actual Hash:</b> <code>$actual_hash</code>\n";
    $msg .= "   └─ <b>Conclusion:</b> <b>Loader has been modified!</b>\n\n";
    
    $msg .= $line . "\n";
    $msg .= "<b>🔴 ACCESS DENIED – Loader integrity check failed</b>\n";
    $msg .= $line . "\n";
    $msg .= "\n📞 <b>Support:</b> @Khatelynnnnnn";
    
    send_telegram_message($msg);
}

function send_quota_limit_alert($license_key, $label, $limit, $used, $remaining) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $loc = get_full_geolocation($ip);
    
    $line = "═══════════════════════════════════════════════════════════════════════════════";
    $thin = "───────────────────────────────────────────────────────────────────────────────";
    
    $msg = "⚠️⚠️⚠️ <b>⚠️ QUOTA LIMIT REACHED! ⚠️</b> ⚠️⚠️⚠️\n\n";
    $msg .= $line . "\n";
    $msg .= "🕐 <b>Time:</b> " . date('Y-m-d H:i:s') . " UTC\n";
    $msg .= $line . "\n\n";
    
    $msg .= "<b>🔑 LICENSE INFORMATION</b>\n";
    $msg .= $thin . "\n";
    $msg .= "   ├─ <b>License Key:</b> <code>$license_key</code>\n";
    $msg .= "   ├─ <b>Label:</b> $label\n";
    $msg .= "   ├─ <b>Total Limit:</b> " . number_format($limit) . "\n";
    $msg .= "   ├─ <b>Used:</b> " . number_format($used) . "\n";
    $msg .= "   ├─ <b>Remaining:</b> <b>$remaining</b>\n";
    $msg .= "   └─ <b>Status:</b> <b>⚠️ QUOTA EXHAUSTED – ACCESS DENIED</b>\n\n";
    
    $msg .= "<b>📍 REQUEST FROM</b>\n";
    $msg .= $thin . "\n";
    $msg .= "   ├─ <b>IP:</b> <code>$ip</code>\n";
    $msg .= "   ├─ <b>Country:</b> {$loc['country']}\n";
    $msg .= "   └─ <b>City:</b> {$loc['city']}\n\n";
    
    $msg .= $line . "\n";
    $msg .= "<b>🔴 ACCESS DENIED – Quota exhausted!</b>\n";
    $msg .= "<b>💡 Action:</b> Reset quota or upgrade the license\n";
    $msg .= $line . "\n";
    $msg .= "\n📞 <b>Support:</b> @Khatelynnnnnn";
    
    send_telegram_message($msg);
}

function send_admin_login_alert($username, $ip) {
    $loc = get_full_geolocation($ip);
    
    $msg = "🔐🔐🔐 <b>🔐 ADMIN LOGIN DETECTED</b> 🔐🔐🔐\n\n";
    $msg .= "════════════════════════════════════════════════════════\n";
    $msg .= "🕐 <b>Time:</b> " . date('Y-m-d H:i:s') . " UTC\n";
    $msg .= "════════════════════════════════════════════════════════\n\n";
    $msg .= "<b>👤 Admin:</b> $username\n";
    $msg .= "<b>🌐 IP:</b> <code>$ip</code>\n";
    $msg .= "<b>📍 Location:</b> {$loc['city']}, {$loc['country']}\n";
    $msg .= "<b>🖥️ ISP:</b> {$loc['isp']}\n\n";
    $msg .= "════════════════════════════════════════════════════════\n";
    $msg .= "✅ <b>Login successful</b>\n";
    $msg .= "════════════════════════════════════════════════════════";
    
    send_telegram_message($msg);
}

function send_key_blacklisted_alert($license_key, $label) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $loc = get_full_geolocation($ip);
    
    $msg = "🚫🚫🚫 <b>🚫 LICENSE BLACKLISTED</b> 🚫🚫🚫\n\n";
    $msg .= "════════════════════════════════════════════════════════\n";
    $msg .= "🕐 <b>Time:</b> " . date('Y-m-d H:i:s') . " UTC\n";
    $msg .= "════════════════════════════════════════════════════════\n\n";
    $msg .= "<b>🔑 License:</b> <code>$license_key</code>\n";
    $msg .= "<b>🏷️ Label:</b> $label\n";
    $msg .= "<b>👤 Action by:</b> " . ($_SESSION['admin_username'] ?? 'Unknown') . "\n";
    $msg .= "<b>🌐 IP:</b> <code>$ip</code>\n";
    $msg .= "<b>📍 Location:</b> {$loc['city']}, {$loc['country']}\n\n";
    $msg .= "════════════════════════════════════════════════════════\n";
    $msg .= "⚠️ <b>This license can no longer be used</b>\n";
    $msg .= "════════════════════════════════════════════════════════";
    
    send_telegram_message($msg);
}

function send_kill_switch_alert($status, $reason = '') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $loc = get_full_geolocation($ip);
    
    $line = "═══════════════════════════════════════════════════════════════════════════════";
    $thin = "───────────────────────────────────────────────────────────────────────────────";
    
    if ($status) {
        $msg = "💀💀💀 <b>💀 KILL SWITCH ACTIVATED! 💀</b> 💀💀💀\n\n";
        $msg .= $line . "\n";
        $msg .= "🕐 <b>Time:</b> " . date('Y-m-d H:i:s') . " UTC\n";
        $msg .= $line . "\n\n";
        
        $msg .= "<b>⚠️ SYSTEM STATUS</b>\n";
        $msg .= $thin . "\n";
        $msg .= "   ├─ <b>Status:</b> <b>🔴 KILL SWITCH ACTIVE</b>\n";
        $msg .= "   ├─ <b>All Loader Access:</b> <b>🚫 BLOCKED</b>\n";
        $msg .= "   ├─ <b>All Multi-Tool Access:</b> <b>🚫 BLOCKED</b>\n";
        if ($reason) {
            $msg .= "   └─ <b>Reason:</b> $reason\n";
        }
        $msg .= "\n";
        
        $msg .= "<b>📍 ACTIVATED FROM</b>\n";
        $msg .= $thin . "\n";
        $msg .= "   ├─ <b>IP:</b> <code>$ip</code>\n";
        $msg .= "   ├─ <b>Country:</b> {$loc['country']}\n";
        $msg .= "   └─ <b>City:</b> {$loc['city']}\n";
    } else {
        $msg = "🔓🔓🔓 <b>🔓 KILL SWITCH DEACTIVATED! 🔓</b> 🔓🔓🔓\n\n";
        $msg .= $line . "\n";
        $msg .= "🕐 <b>Time:</b> " . date('Y-m-d H:i:s') . " UTC\n";
        $msg .= $line . "\n\n";
        
        $msg .= "<b>✅ SYSTEM STATUS</b>\n";
        $msg .= $thin . "\n";
        $msg .= "   ├─ <b>Status:</b> <b>🟢 KILL SWITCH OFF</b>\n";
        $msg .= "   ├─ <b>Loader Access:</b> <b>✅ RESTORED</b>\n";
        $msg .= "   └─ <b>Multi-Tool Access:</b> <b>✅ RESTORED</b>\n\n";
        
        $msg .= "<b>📍 DEACTIVATED FROM</b>\n";
        $msg .= $thin . "\n";
        $msg .= "   ├─ <b>IP:</b> <code>$ip</code>\n";
        $msg .= "   └─ <b>Location:</b> {$loc['country']}, {$loc['city']}\n";
    }
    
    $msg .= "\n" . $line . "\n";
    $msg .= "\n📞 <b>Support:</b> @Khatelynnnnnn";
    
    send_telegram_message($msg);
}
?>