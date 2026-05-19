<?php
/**
 * Simple Loader Upload Handler - Accepts ANY file (including obfuscated)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

session_start();

// ==================== CONFIGURATION ====================
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB - increased for large obfuscated files

// ==================== SIMPLE AUTH CHECK ====================

function is_admin_authenticated() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        return false;
    }
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 28800) {
        session_destroy();
        return false;
    }
    return true;
}

// ==================== MAIN HANDLER ====================

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Only POST method allowed']);
    exit();
}

if (!is_admin_authenticated()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login first.']);
    exit();
}

// Get upload type
$upload_type = $_POST['upload_type'] ?? '';

if (empty($upload_type)) {
    if (isset($_FILES['loader_file'])) {
        $upload_type = 'loader';
    } elseif (isset($_FILES['file'])) {
        $filename = $_FILES['file']['name'] ?? '';
        if (stripos($filename, 'multi') !== false || stripos($filename, 'multi_tool') !== false) {
            $upload_type = 'multi';
        } else {
            $upload_type = 'loader';
        }
    }
}

// Upload MULTI TOOL
if ($upload_type === 'multi') {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $error_msg = isset($_FILES['file']) ? 'Upload error code: ' . $_FILES['file']['error'] : 'No file uploaded';
        echo json_encode(['success' => false, 'message' => $error_msg]);
        exit();
    }
    
    if ($_FILES['file']['size'] > MAX_FILE_SIZE) {
        echo json_encode(['success' => false, 'message' => 'File too large. Max 50MB.']);
        exit();
    }
    
    $target_path = __DIR__ . '/multi.py';
    
    // Delete old file if exists
    if (file_exists($target_path)) {
        unlink($target_path);
    }
    
    if (move_uploaded_file($_FILES['file']['tmp_name'], $target_path)) {
        chmod($target_path, 0644);
        $file_hash = hash_file('sha256', $target_path);
        echo json_encode([
            'success' => true, 
            'message' => 'Multi Tool uploaded successfully!',
            'hash' => $file_hash
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save file - check directory permissions']);
    }
    
// Upload LOADER
} elseif ($upload_type === 'loader') {
    $file_key = isset($_FILES['loader_file']) ? 'loader_file' : 'file';
    
    if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] !== UPLOAD_ERR_OK) {
        $error_msg = isset($_FILES[$file_key]) ? 'Upload error code: ' . $_FILES[$file_key]['error'] : 'No file uploaded';
        echo json_encode(['success' => false, 'message' => $error_msg]);
        exit();
    }
    
    if ($_FILES[$file_key]['size'] > MAX_FILE_SIZE) {
        echo json_encode(['success' => false, 'message' => 'File too large. Max 50MB.']);
        exit();
    }
    
    $target_path = __DIR__ . '/loader.py';
    
    // Delete old file if exists
    if (file_exists($target_path)) {
        unlink($target_path);
    }
    
    if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $target_path)) {
        chmod($target_path, 0644);
        $file_hash = hash_file('sha256', $target_path);
        
        // Also update the original loader hash in a config file for integrity check
        $config_file = __DIR__ . '/admin_data/loader_config.json';
        if (!file_exists(__DIR__ . '/admin_data')) {
            mkdir(__DIR__ . '/admin_data', 0755, true);
        }
        file_put_contents($config_file, json_encode([
            'current_hash' => $file_hash,
            'last_updated' => date('Y-m-d H:i:s'),
            'updated_by_ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
        ]));
        
        echo json_encode([
            'success' => true, 
            'message' => 'Loader uploaded successfully!',
            'hash' => $file_hash
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save file - check directory permissions']);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid upload type. Use upload_type=loader or upload_type=multi']);
}
?>