<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';
include_once '../config/twilio_config.php';

// Verification for Admin/Owner
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'owner')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (empty($data->message)) {
    echo json_encode(['success' => false, 'message' => 'Message content is required']);
    exit;
}

$message = $data->message;
$database = new Database();
$db = $database->getConnection();

// Fetch all staff phone numbers - EXCLUDE the current sender to avoid double messages
$current_user_id = $_SESSION['user_id'];
$query = "SELECT name, phone FROM users WHERE is_active = 1 AND phone IS NOT NULL AND phone != '' AND id != ?";
$stmt = $db->prepare($query);
$stmt->execute([$current_user_id]);

$staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Apply Professional Template (Arabic)
$templateHeader = "📢 *رسالة إدارية من IBS* 📢\n" . str_repeat("━", 20) . "\n\n";
$templateFooter = "\n\n" . str_repeat("━", 20) . "\n*هذا تنبيه إداري رسمي.*";
$templatedMessage = $templateHeader . $message . $templateFooter;

if (empty($staff)) {
    echo json_encode(['success' => false, 'message' => 'No other active staff with phone numbers found']);
    exit;
}

$results = [
    'success' => 0,
    'failed' => 0,
    'total' => count($staff),
    'details' => []
];

foreach ($staff as $member) {
    if (empty($member['phone'])) continue;
    
    // Clean and format number for WhatsApp
    // 1. Remove everything except digits
    $rawPhone = preg_replace('/[^0-9]/', '', $member['phone']);
    
    // Skip obviously fake or too short numbers
    if (strlen($rawPhone) < 10) {
        $results['total']--; // Don't count skipped as part of total for stats if desired, or handle as skipped
        $results['details'][] = ['name' => $member['name'], 'status' => 'skipped', 'error' => 'Invalid phone number format'];
        continue;
    }
    
    // 2. Handle international codes
    // If it starts with 0 and it's likely an Egyptian number (10, 11, 12, 15)
    if (preg_match('/^01[0125]/', $rawPhone)) {
        $rawPhone = '2' . $rawPhone; 
    }
    
    // Ensure it has a + prefix for Twilio
    $to = 'whatsapp:+' . ltrim($rawPhone, '+');
    
    if (TWILIO_SIMULATION) {
        $results['success']++;
        $results['details'][] = ['name' => $member['name'], 'status' => 'simulated'];
        continue;
    }

    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.twilio.com/2010-04-01/Accounts/" . TWILIO_ACCOUNT_SID . "/Messages.json");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "Body=" . urlencode($templatedMessage) . "&From=" . urlencode(TWILIO_WHATSAPP_FROM) . "&To=" . urlencode($to));
        curl_setopt($ch, CURLOPT_USERPWD, TWILIO_ACCOUNT_SID . ":" . TWILIO_AUTH_TOKEN);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        // SSL Fix for Windows/WAMP environments
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        $resObj = json_decode($response);
        curl_close($ch);

        if ($status_code >= 200 && $status_code < 300) {
            $results['success']++;
            $results['details'][] = ['name' => $member['name'], 'status' => 'success'];
        } else {
            $results['failed']++;
            // Extract the most readable error from Twilio
            $errorDetail = $resObj->message ?? ($resObj->error_message ?? ($curl_error ?: ($response ?: 'HTTP ' . $status_code)));
            
            $results['details'][] = [
                'name' => $member['name'], 
                'status' => 'failed', 
                'error' => $errorDetail,
                'code' => $resObj->code ?? $status_code
            ];
            error_log("[TWILIO ERROR] To $to: " . ($response ?: $curl_error));
        }
    } catch (Exception $e) {
        $results['failed']++;
        $results['details'][] = ['name' => $member['name'], 'status' => 'error', 'error' => $e->getMessage()];
    }
}

// Log the broadcast
error_log("[WHATSAPP BROADCAST] Result: " . $results['success'] . "/" . ($results['success'] + $results['failed']));

$skippedCount = count(array_filter($results['details'], function($d) { return $d['status'] === 'skipped'; }));

if ($results['failed'] === 0 && $results['success'] > 0) {
    $finalMessage = "Broadcast completed successfully! 🎉 (" . $results['success'] . " sent" . ($skippedCount > 0 ? ", $skippedCount skipped" : "") . ")";
} else if ($results['success'] === 0 && $results['failed'] > 0) {
    $finalMessage = "Broadcast failed. None of the " . $results['failed'] . " messages could be sent.";
} else {
    $finalMessage = "Broadcast partially completed. " . $results['success'] . " sent, " . $results['failed'] . " failed" . ($skippedCount > 0 ? ", $skippedCount skipped" : "") . ".";
}

if ($results['failed'] > 0) {
    // Collect unique errors
    $errors = [];
    foreach ($results['details'] as $det) {
        if ($det['status'] === 'failed' || $det['status'] === 'error') {
            $errText = $det['error'] ?? 'Unknown Error';
            $errCode = $det['code'] ?? '';
            $key = $errCode ? "($errCode) $errText" : $errText;
            $errors[$key] = true;
        }
    }
    $finalMessage .= " Errors: " . implode(" | ", array_keys($errors));
}

echo json_encode([
    'success' => $results['success'] > 0 && $results['failed'] === 0,
    'message' => $finalMessage,
    'stats' => $results
]);
?>
