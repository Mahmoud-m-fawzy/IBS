<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized - No session found']);
    exit;
}

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Log security attempts
function logIncomeAttempt($action, $success = false, $details = '') {
    $log_entry = sprintf(
        "[%s] User ID: %d (%s) - Action: %s - Success: %s - Details: %s\n",
        date('Y-m-d H:i:s'),
        $_SESSION['user_id'] ?? 'unknown',
        $_SESSION['role'] ?? 'unknown',
        $action,
        $success ? 'YES' : 'NO',
        $details
    );
    error_log("INCOME_AUDIT: " . $log_entry, 3, 'income_audit.log');
}

switch ($method) {
    case 'GET':
        // Only Admin and Owner can view all income entries
        if ($user_role !== 'admin' && $user_role !== 'owner') {
            logIncomeAttempt('VIEW_ALL_INCOME', false, 'Insufficient permissions');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied - Insufficient permissions']);
            exit;
        }
        
        try {
            $query = "SELECT ie.*, u.name as created_by_name
                     FROM income_entries ie
                     LEFT JOIN users u ON ie.created_by = u.id
                     ORDER BY ie.entry_date DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();

            $income_entries = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $income_entries[] = [
                    'id' => (int) $row['id'],
                    'price' => (float) ($row['price'] / 100),
                    'description' => $row['description'],
                    'entry_date' => $row['entry_date'],
                    'created_by' => (int) $row['created_by'],
                    'created_by_name' => $row['created_by_name'],
                    'created_at' => $row['created_at']
                ];
            }

            echo json_encode(['success' => true, 'data' => $income_entries]);
            logIncomeAttempt('VIEW_ALL_INCOME', true, 'Retrieved ' . count($income_entries) . ' entries');
        } catch (Exception $e) {
            logIncomeAttempt('VIEW_ALL_INCOME', false, 'Database error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error fetching income entries: ' . $e->getMessage()]);
        }
        break;

    case 'POST':
        // Add new income entry - all authenticated users can create
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || !isset($data['price']) || !isset($data['description'])) {
            logIncomeAttempt('CREATE_INCOME', false, 'Missing required fields');
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Price and description are required']);
            exit;
        }

        $price = intval(round(floatval($data['price']) * 100));
        $description = trim($data['description']);

        if ($price <= 0) {
            logIncomeAttempt('CREATE_INCOME', false, 'Invalid price: ' . $price);
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Price must be greater than 0']);
            exit;
        }

        if (empty($description)) {
            logIncomeAttempt('CREATE_INCOME', false, 'Empty description');
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Description cannot be empty']);
            exit;
        }

        try {
            $query = "INSERT INTO income_entries (price, description, created_by) VALUES (?, ?, ?)";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([$price, $description, $_SESSION['user_id']]);

            if ($result) {
                $newId = $db->lastInsertId();
                logIncomeAttempt('CREATE_INCOME', true, 'ID: ' . $newId . ', Price: ' . ($price/100));
                echo json_encode([
                    'success' => true,
                    'message' => 'Income entry added successfully',
                    'income_id' => $newId
                ]);
            } else {
                logIncomeAttempt('CREATE_INCOME', false, 'Database insert failed');
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to add income entry']);
            }
        } catch (Exception $e) {
            logIncomeAttempt('CREATE_INCOME', false, 'Database error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error adding income entry: ' . $e->getMessage()]);
        }
        break;

    case 'PUT':
        // Update income entry - users can only update their own entries, Admin/Owner can update any
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || !isset($data['id']) || !isset($data['price']) || !isset($data['description'])) {
            logIncomeAttempt('UPDATE_INCOME', false, 'Missing required fields');
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID, price and description are required']);
            exit;
        }

        $id = intval($data['id']);
        $price = intval(round(floatval($data['price']) * 100));
        $description = trim($data['description']);

        if ($price <= 0) {
            logIncomeAttempt('UPDATE_INCOME', false, 'Invalid price: ' . $price);
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Price must be greater than 0']);
            exit;
        }

        if (empty($description)) {
            logIncomeAttempt('UPDATE_INCOME', false, 'Empty description');
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Description cannot be empty']);
            exit;
        }

        try {
            // Check if user can update this entry (own entry or admin/owner)
            $isAdminOrOwner = ($user_role === 'admin' || $user_role === 'owner');
            
            if (!$isAdminOrOwner) {
                // For regular users, check if they created this entry
                $checkQuery = "SELECT created_by FROM income_entries WHERE id = ?";
                $checkStmt = $db->prepare($checkQuery);
                $checkStmt->execute([$id]);
                $entry = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$entry || $entry['created_by'] != $user_id) {
                    logIncomeAttempt('UPDATE_INCOME', false, 'Access denied - not owner of entry');
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Access denied - You can only update your own entries']);
                    exit;
                }
            }
            
            $query = "UPDATE income_entries SET price = ?, description = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([$price, $description, $id]);

            if ($result && $stmt->rowCount() > 0) {
                logIncomeAttempt('UPDATE_INCOME', true, 'ID: ' . $id . ', Price: ' . ($price/100));
                echo json_encode(['success' => true, 'message' => 'Income entry updated successfully']);
            } else {
                logIncomeAttempt('UPDATE_INCOME', false, 'Entry not found or no changes made');
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Income entry not found or not authorized to edit']);
            }
        } catch (Exception $e) {
            logIncomeAttempt('UPDATE_INCOME', false, 'Database error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error updating income entry: ' . $e->getMessage()]);
        }
        break;

    case 'DELETE':
        // Delete income entry with hierarchical permissions
        if (!isset($_GET['id'])) {
            logIncomeAttempt('DELETE_INCOME', false, 'Missing ID parameter');
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Income entry ID is required']);
            exit;
        }

        $id = intval($_GET['id']);

        try {
            // Check if user can delete this entry
            $isAdminOrOwner = ($user_role === 'admin' || $user_role === 'owner');
            
            if (!$isAdminOrOwner) {
                // For regular users, check if they created this entry
                $checkQuery = "SELECT created_by FROM income_entries WHERE id = ?";
                $checkStmt = $db->prepare($checkQuery);
                $checkStmt->execute([$id]);
                $entry = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$entry || $entry['created_by'] != $user_id) {
                    logIncomeAttempt('DELETE_INCOME', false, 'Access denied - not owner of entry');
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Access denied - You can only delete your own entries']);
                    exit;
                }
            }

            // Admin can delete any entry, regular users can only delete their own
            if ($isAdminOrOwner) {
                $query = "DELETE FROM income_entries WHERE id = ?";
                $stmt = $db->prepare($query);
                $result = $stmt->execute([$id]);
            } else {
                $query = "DELETE FROM income_entries WHERE id = ? AND created_by = ?";
                $stmt = $db->prepare($query);
                $result = $stmt->execute([$id, $user_id]);
            }

            if ($result && $stmt->rowCount() > 0) {
                logIncomeAttempt('DELETE_INCOME', true, 'ID: ' . $id);
                echo json_encode(['success' => true, 'message' => 'Income entry deleted successfully']);
            } else {
                logIncomeAttempt('DELETE_INCOME', false, 'Entry not found or not authorized');
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Income entry not found or not authorized to delete']);
            }
        } catch (Exception $e) {
            logIncomeAttempt('DELETE_INCOME', false, 'Database error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error deleting income entry: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
?>