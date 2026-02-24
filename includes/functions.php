<?php
/**
 * Common Functions for IBS - Inventory Management System
 * Shared utility functions used across the application
 */

// Prevent direct access
if (!defined('IBS_ACCESS')) {
    die('Direct access to this file is not allowed.');
}

/**
 * Format currency amount
 */
function formatCurrency($amount, $currency = 'EGP') {
    return $currency . ' ' . number_format($amount, 2, '.', ',');
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'Y-m-d H:i:s') {
    return date($format, strtotime($date));
}

/**
 * Generate unique receipt number
 */
function generateReceiptNumber() {
    return 'RCP' . date('YmdHis') . rand(100, 999);
}

/**
 * Calculate EAN13 checksum
 */
function calculateEAN13Checksum($digits) {
    $checksum = 0;
    $odd = true;
    
    for ($i = strlen($digits) - 1; $i >= 0; $i--) {
        $digit = (int)$digits[$i];
        if ($odd) {
            $checksum += $digit;
        } else {
            $checksum += $digit * 3;
        }
        $odd = !$odd;
    }
    
    $checksum = (10 - ($checksum % 10)) % 10;
    return $checksum;
}

/**
 * Validate EAN13 barcode
 */
function validateEAN13($barcode) {
    if (strlen($barcode) != 13) {
        return false;
    }
    
    $digits = substr($barcode, 0, 12);
    $checksum = calculateEAN13Checksum($digits);
    
    return $checksum == (int)$barcode[12];
}

/**
 * Generate barcode image
 */
function generateBarcode($barcode, $width = 200, $height = 50) {
    // This is a placeholder - in production, use a proper barcode library
    return "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhQDwEB+e6U8AAAAASUVORK5CYII=";
}

/**
 * Send email notification
 */
function sendEmail($to, $subject, $message, $from = 'noreply@ibs.com') {
    $headers = "From: $from\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

/**
 * Log system activity
 */
function logActivity($action, $user_id, $details = '') {
    global $db;
    
    try {
        $stmt = $db->prepare("INSERT INTO activity_log (user_id, action, details, ip_address, user_agent, created_at) 
                              VALUES (?, ?, ?, ?, ?, NOW())");
        
        $stmt->execute([
            $user_id,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Get user permissions
 */
function getUserPermissions($user_id) {
    global $db;
    
    try {
        $stmt = $db->prepare("SELECT role, permissions FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            return [
                'role' => $user['role'],
                'permissions' => json_decode($user['permissions'] ?? '[]', true)
            ];
        }
    } catch (Exception $e) {
        error_log("Failed to get user permissions: " . $e->getMessage());
    }
    
    return ['role' => 'guest', 'permissions' => []];
}

/**
 * Check if user has permission
 */
function hasPermission($permission, $user_id = null) {
    if ($user_id === null) {
        $user_id = $_SESSION['user_id'] ?? null;
    }
    
    $permissions = getUserPermissions($user_id);
    
    // Owner has all permissions
    if ($permissions['role'] === 'owner') {
        return true;
    }
    
    // Check specific permission
    return in_array($permission, $permissions['permissions']);
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number
 */
function validatePhone($phone) {
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Check if it's a valid phone number (10-15 digits)
    return strlen($phone) >= 10 && strlen($phone) <= 15;
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $randomString;
}

/**
 * Check if date is valid
 */
function isValidDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Calculate age from date of birth
 */
function calculateAge($dob) {
    $today = new DateTime();
    $birthdate = new DateTime($dob);
    $age = $today->diff($birthdate)->y;
    return $age;
}

/**
 * Get file extension
 */
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Check if file is an image
 */
function isImage($filename) {
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extension = getFileExtension($filename);
    return in_array($extension, $allowedExtensions);
}

/**
 * Upload file
 */
function uploadFile($file, $targetDir, $allowedExtensions = []) {
    if (empty($allowedExtensions)) {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
    }
    
    // Check if file was uploaded
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'message' => 'No file uploaded'];
    }
    
    $filename = $file['name'];
    $extension = getFileExtension($filename);
    
    // Check file extension
    if (!in_array($extension, $allowedExtensions)) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }
    
    // Check file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'message' => 'File too large'];
    }
    
    // Generate unique filename
    $newFilename = generateRandomString(10) . '_' . time() . '.' . $extension;
    $targetPath = $targetDir . '/' . $newFilename;
    
    // Create directory if it doesn't exist
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    // Move file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return [
            'success' => true,
            'filename' => $newFilename,
            'path' => $targetPath
        ];
    } else {
        return ['success' => false, 'message' => 'Failed to upload file'];
    }
}

/**
 * Create pagination
 */
function createPagination($total, $perPage, $currentPage) {
    $totalPages = ceil($total / $perPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    
    $pagination = [
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'total_items' => $total,
        'per_page' => $perPage,
        'offset' => ($currentPage - 1) * $perPage,
        'has_previous' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
    
    return $pagination;
}

/**
 * Get pagination HTML
 */
function getPaginationHTML($pagination, $url = '') {
    $html = '<div class="pagination">';
    
    // Previous button
    if ($pagination['has_previous']) {
        $html .= '<a href="' . $url . '?page=' . ($pagination['current_page'] - 1) . '" class="pagination-btn">« Previous</a>';
    }
    
    // Page numbers
    $start = max(1, $pagination['current_page'] - 2);
    $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $pagination['current_page']) {
            $html .= '<span class="pagination-btn active">' . $i . '</span>';
        } else {
            $html .= '<a href="' . $url . '?page=' . $i . '" class="pagination-btn">' . $i . '</a>';
        }
    }
    
    // Next button
    if ($pagination['has_next']) {
        $html .= '<a href="' . $url . '?page=' . ($pagination['current_page'] + 1) . '" class="pagination-btn">Next »</a>';
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Get browser information
 */
function getBrowserInfo() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $browser = 'Unknown';
    $platform = 'Unknown';
    
    // Detect browser
    if (preg_match('/MSIE/i', $user_agent)) {
        $browser = 'Internet Explorer';
    } elseif (preg_match('/Firefox/i', $user_agent)) {
        $browser = 'Firefox';
    } elseif (preg_match('/Chrome/i', $user_agent)) {
        $browser = 'Chrome';
    } elseif (preg_match('/Safari/i', $user_agent)) {
        $browser = 'Safari';
    } elseif (preg_match('/Edge/i', $user_agent)) {
        $browser = 'Edge';
    }
    
    // Detect platform
    if (preg_match('/Windows/i', $user_agent)) {
        $platform = 'Windows';
    } elseif (preg_match('/Mac/i', $user_agent)) {
        $platform = 'Mac';
    } elseif (preg_match('/Linux/i', $user_agent)) {
        $platform = 'Linux';
    } elseif (preg_match('/Android/i', $user_agent)) {
        $platform = 'Android';
    } elseif (preg_match('/iOS/i', $user_agent)) {
        $platform = 'iOS';
    }
    
    return [
        'browser' => $browser,
        'platform' => $platform,
        'user_agent' => $user_agent
    ];
}

/**
 * Get client IP address
 */
function getClientIP() {
    $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
}

/**
 * Debug function
 */
function debug($data, $label = 'DEBUG') {
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo '<pre>';
        echo $label . ': ';
        print_r($data);
        echo '</pre>';
    }
}

/**
 * Safe JSON encode
 */
function safeJSONEncode($data) {
    return json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
}

/**
 * Get current page URL
 */
function getCurrentURL() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $path = $_SERVER['REQUEST_URI'];
    
    return $protocol . '://' . $host . $path;
}

/**
 * Redirect with delay
 */
function redirect($url, $delay = 0) {
    if ($delay > 0) {
        echo '<meta http-equiv="refresh" content="' . $delay . ';url=' . $url . '">';
    } else {
        header('Location: ' . $url);
    }
    exit();
}

/**
 * Check if request is AJAX
 */
function isAJAXRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Return JSON response
 */
function jsonResponse($data, $status = 200) {
    header('Content-Type: application/json');
    http_response_code($status);
    echo safeJSONEncode($data);
    exit();
}

/**
 * Return error response
 */
function errorResponse($message, $status = 400) {
    jsonResponse([
        'success' => false,
        'message' => $message
    ], $status);
}

/**
 * Return success response
 */
function successResponse($data = null, $message = 'Success') {
    jsonResponse([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
}

/**
 * Get database connection with error handling
 */
function getDBConnection() {
    try {
        include_once 'config/database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        if (!$db) {
            throw new Exception('Database connection failed');
        }
        
        return $db;
    } catch (Exception $e) {
        error_log('Database connection error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Execute prepared statement with error handling
 */
function executeQuery($query, $params = []) {
    $db = getDBConnection();
    
    if (!$db) {
        return false;
    }
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch (Exception $e) {
        error_log('Query execution error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get single record from database
 */
function getRecord($query, $params = []) {
    $stmt = executeQuery($query, $params);
    
    if ($stmt) {
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    return false;
}

/**
 * Get multiple records from database
 */
function getRecords($query, $params = []) {
    $stmt = executeQuery($query, $params);
    
    if ($stmt) {
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    return [];
}

/**
 * Insert record into database
 */
function insertRecord($table, $data) {
    $db = getDBConnection();
    
    if (!$db) {
        return false;
    }
    
    try {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $query = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = $db->prepare($query);
        $stmt->execute(array_values($data));
        
        return $db->lastInsertId();
    } catch (Exception $e) {
        error_log('Insert error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Update record in database
 */
function updateRecord($table, $data, $where, $whereParams = []) {
    $db = getDBConnection();
    
    if (!$db) {
        return false;
    }
    
    try {
        $setClause = [];
        foreach ($data as $key => $value) {
            $setClause[] = "$key = ?";
        }
        
        $setClause = implode(', ', $setClause);
        $query = "UPDATE $table SET $setClause WHERE $where";
        
        $stmt = $db->prepare($query);
        $stmt->execute(array_merge(array_values($data), $whereParams));
        
        return $stmt->rowCount();
    } catch (Exception $e) {
        error_log('Update error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Delete record from database
 */
function deleteRecord($table, $where, $params = []) {
    $query = "DELETE FROM $table WHERE $where";
    $stmt = executeQuery($query, $params);
    
    if ($stmt) {
        return $stmt->rowCount();
    }
    
    return false;
}

// Define access constant
define('IBS_ACCESS', true);
?>
