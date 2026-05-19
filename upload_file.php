<?php
/**
 * Alternative File Upload Handler - Direct upload without curl
 * Use this if upload_loader.php is not working
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

session_start();

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

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Only POST method allowed']);
    exit();
}

if (!is_admin_authenticated()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Please login first.']);
    exit();
}

// Get upload type
$upload_type = $_POST['upload_type'] ?? '';

if (empty($upload_type)) {
    if (isset($_FILES['loader_file'])) {
        $upload_type = 'loader';
    } elseif (isset($_FILES['file'])) {
        $filename = $_FILES['file']['name'] ?? '';
        if (stripos($filename, 'multi') !== false) {
            $upload_type = 'multi';
        } else {
            $upload_type = 'loader';
        }
    }
}

// Upload MULTI TOOL
if ($upload_type === 'multi') {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error']);
        exit();
    }
    
    if ($_FILES['file']['size'] > 50 * 1024 * 1024) {
        echo json_encode(['success' => false, 'error' => 'File too large. Max 50MB.']);
        exit();
    }
    
    $target_path = __DIR__ . '/multi.py';
    
    if (move_uploaded_file($_FILES['file']['tmp_name'], $target_path)) {
        chmod($target_path, 0644);
        echo json_encode(['success' => true, 'message' => 'Multi Tool uploaded successfully!']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save file - check permissions']);
    }
    
// Upload LOADER
} elseif ($upload_type === 'loader') {
    $file_key = isset($_FILES['loader_file']) ? 'loader_file' : 'file';
    
    if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error']);
        exit();
    }
    
    if ($_FILES[$file_key]['size'] > 50 * 1024 * 1024) {
        echo json_encode(['success' => false, 'error' => 'File too large. Max 50MB.']);
        exit();
    }
    
    $target_path = __DIR__ . '/loader.py';
    
    if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $target_path)) {
        chmod($target_path, 0644);
        echo json_encode(['success' => true, 'message' => 'Loader uploaded successfully!']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save file - check permissions']);
    }
    
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid upload type']);
}
?>