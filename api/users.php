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

// Log unauthorized access attempts
function logSecurityAttempt($action, $target_role = null, $success = false) {
    $log_entry = sprintf(
        "[%s] User ID: %d (%s) - Action: %s - Target Role: %s - Success: %s\n",
        date('Y-m-d H:i:s'),
        $_SESSION['user_id'] ?? 'unknown',
        $_SESSION['role'] ?? 'unknown',
        $action,
        $target_role ?? 'none',
        $success ? 'YES' : 'NO'
    );
    error_log("SECURITY_AUDIT: " . $log_entry, 3, 'security_audit.log');
}

switch($method) {
    case 'GET':
        // Only Owner and Admin can view users
        if ($user_role !== 'owner' && $user_role !== 'admin') {
            logSecurityAttempt('VIEW_USERS', null, false);
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied - Insufficient permissions']);
            exit;
        }
        
        // Get all users/staff
        $query = "SELECT id, username, name, role, phone, email, is_active, created_at 
                  FROM users 
                  ORDER BY role, name";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $users = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $users[] = [
                'id' => (int)$row['id'],
                'username' => $row['username'],
                'name' => $row['name'],
                'role' => $row['role'],
                'phone' => $row['phone'],
                'email' => $row['email'],
                'is_active' => (bool)$row['is_active'],
                'status' => $row['is_active'] ? 'Active' : 'Inactive',
                'created_at' => $row['created_at']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $users
        ]);
        break;
        
    case 'POST':
        // Add new user with hierarchical permissions
        if ($user_role !== 'owner' && $user_role !== 'admin') {
            logSecurityAttempt('CREATE_USER', null, false);
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied - Insufficient permissions']);
            exit;
        }
        
        $data = json_decode(file_get_contents("php://input"));
        
        if (!empty($data->username) && !empty($data->password) && !empty($data->name) && !empty($data->role)) {
            // Validate hierarchical permissions
            $target_role = $data->role;
            
            // Owner can create any user (admin, staff)
            // Admin can only create staff users
            if ($user_role === 'admin' && $target_role === 'admin') {
                logSecurityAttempt('CREATE_ADMIN', 'admin', false);
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied - Admin cannot create other Admin users']);
                exit;
            }
            
            // Only Owner can create Owner users
            if ($target_role === 'owner' && $user_role !== 'owner') {
                logSecurityAttempt('CREATE_OWNER', 'owner', false);
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied - Only Owner can create Owner users']);
                exit;
            }
            
            $query = "INSERT INTO users (username, password, name, role, phone, email, is_active) 
                      VALUES (?, ?, ?, ?, ?, ?, 1)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $data->username);
            $stmt->bindParam(2, $data->password);
            $stmt->bindParam(3, $data->name);
            $stmt->bindParam(4, $data->role);
            $stmt->bindParam(5, $data->phone ?? '');
            $stmt->bindParam(6, $data->email ?? '');
            
            if ($stmt->execute()) {
                logSecurityAttempt('CREATE_USER', $target_role, true);
                echo json_encode([
                    'success' => true,
                    'message' => 'User added successfully',
                    'id' => $db->lastInsertId()
                ]);
            } else {
                logSecurityAttempt('CREATE_USER', $target_role, false);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to add user'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Missing required fields'
            ]);
        }
        break;
        
    case 'PUT':
        // Update user details with hierarchical permissions
        if ($user_role !== 'owner' && $user_role !== 'admin') {
            logSecurityAttempt('UPDATE_USER', null, false);
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied - Insufficient permissions']);
            exit;
        }
        
        $data = json_decode(file_get_contents("php://input"));
        
        if (!empty($data->id)) {
            // Get target user info to validate permissions
            $target_id = intval($data->id);
            $query = "SELECT role FROM users WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$target_id]);
            $target_user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$target_user) {
                logSecurityAttempt('UPDATE_USER', 'unknown', false);
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }
            
            $target_role = $target_user['role'];
            
            // Validate hierarchical permissions for updates
            if (isset($data->role)) {
                $new_role = $data->role;
                
                // Admin cannot modify other Admins or Owners
                if ($user_role === 'admin' && ($target_role === 'admin' || $target_role === 'owner')) {
                    logSecurityAttempt('UPDATE_ADMIN_OR_OWNER', $target_role, false);
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Access denied - Admin cannot modify other Admins or Owners']);
                    exit;
                }
                
                // Only Owner can create or assign Owner role
                if ($new_role === 'owner' && $user_role !== 'owner') {
                    logSecurityAttempt('ASSIGN_OWNER_ROLE', 'owner', false);
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Access denied - Only Owner can assign Owner role']);
                    exit;
                }
                
                // Admin cannot promote users to Admin
                if ($new_role === 'admin' && $user_role !== 'owner') {
                    logSecurityAttempt('PROMOTE_TO_ADMIN', 'admin', false);
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Access denied - Only Owner can promote users to Admin']);
                    exit;
                }
            }
            
            try {
                // Check if password is being updated
                if (isset($data->password) && !empty($data->password)) {
                    // Update all user fields including password
                    $query = "UPDATE users SET name = ?, role = ?, phone = ?, email = ?, is_active = ?, password = ? WHERE id = ?";
                    $stmt = $db->prepare($query);
                    
                    $stmt->bindParam(1, $data->name);
                    $stmt->bindParam(2, $data->role);
                    $stmt->bindParam(3, $data->phone ?? null);
                    $stmt->bindParam(4, $data->email ?? null);
                    $stmt->bindParam(5, $data->is_active);
                    $stmt->bindParam(6, $data->password);
                    $stmt->bindParam(7, $data->id);
                    
                    error_log("DEBUG: Password update - Query: $query, Params: " . json_encode([$data->name, $data->role, $data->phone, $data->email, $data->is_active, $data->password, $data->id]));
                } else {
                    // Update all user fields except password - handle missing columns gracefully
                    $update_fields = [];
                    $update_values = [];
                    $update_types = "";
                    
                    // Always update name and role
                    $update_fields[] = "name = ?";
                    $update_values[] = $data->name;
                    $update_types .= "s";
                    
                    $update_fields[] = "role = ?";
                    $update_values[] = $data->role;
                    $update_types .= "s";
                    
                    // Conditionally update phone if column exists
                    try {
                        $phone_check = $db->query("DESCRIBE users");
                        $columns = [];
                        while ($row = $phone_check->fetch(PDO::FETCH_ASSOC)) {
                            $columns[] = $row['Field'];
                        }
                        
                        if (in_array('phone', $columns)) {
                            $update_fields[] = "phone = ?";
                            $update_values[] = $data->phone ?? null;
                            $update_types .= "s";
                        }
                        
                        // Conditionally update email if column exists
                        if (in_array('email', $columns)) {
                            $update_fields[] = "email = ?";
                            $update_values[] = $data->email ?? null;
                            $update_types .= "s";
                        }
                        
                        // Always update is_active
                        $update_fields[] = "is_active = ?";
                        $update_values[] = $data->is_active;
                        $update_types .= "s";
                        
                        $update_fields[] = "id = ?";
                        $update_values[] = $data->id;
                        $update_types .= "i";
                        
                        $query = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE id = ?";
                        $stmt = $db->prepare($query);
                        
                        error_log("DEBUG: Dynamic update - Query: $query, Fields: " . implode(', ', $update_fields) . ", Values: " . json_encode($update_values) . ", Types: $update_types");
                        
                        // Bind parameters dynamically - count actual parameters in query
                        $param_index = 1;
                        foreach ($update_values as $value) {
                            error_log("DEBUG: Binding param $param_index with value: " . json_encode($value));
                            $stmt->bindValue($param_index, $value);
                            $param_index++;
                        }
                    } catch (Exception $e) {
                        // Fallback: update without phone and email if columns don't exist
                        $query = "UPDATE users SET name = ?, role = ?, is_active = ?, password = ? WHERE id = ?";
                        $stmt = $db->prepare($query);
                        
                        // Only bind parameters that are actually present in the query
                        $stmt->bindParam(1, $data->name);
                        $stmt->bindParam(2, $data->role);
                        $stmt->bindParam(3, $data->is_active);
                        $stmt->bindParam(4, $data->password ?? null);
                        $stmt->bindParam(5, $data->id);
                    }
                }
                
                if ($stmt->execute()) {
                    logSecurityAttempt('UPDATE_USER', $target_role, true);
                    echo json_encode([
                        'success' => true,
                        'message' => 'User updated successfully'
                    ]);
                } else {
                    logSecurityAttempt('UPDATE_USER', $target_role, false);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to update user'
                    ]);
                }
            } catch (Exception $e) {
                logSecurityAttempt('UPDATE_USER', $target_role, false);
                echo json_encode([
                    'success' => false,
                    'message' => 'Database error: ' . $e->getMessage()
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'User ID is required'
            ]);
        }
        break;
        
    case 'DELETE':
        // Delete user with hierarchical permissions
        if ($user_role !== 'owner' && $user_role !== 'admin') {
            logSecurityAttempt('DELETE_USER', null, false);
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied - Insufficient permissions']);
            exit;
        }
        
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'User ID is required']);
            exit;
        }
        
        $target_id = intval($_GET['id']);
        
        // Prevent self-deletion
        if ($target_id === $user_id) {
            logSecurityAttempt('DELETE_SELF', null, false);
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied - Cannot delete your own account']);
            exit;
        }
        
        // Get target user info to validate permissions
        $query = "SELECT role FROM users WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$target_id]);
        $target_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$target_user) {
            logSecurityAttempt('DELETE_USER', 'unknown', false);
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }
        
        $target_role = $target_user['role'];
        
        // Validate hierarchical permissions for deletion
        // Admin cannot delete other Admins or Owners
        if ($user_role === 'admin' && ($target_role === 'admin' || $target_role === 'owner')) {
            logSecurityAttempt('DELETE_ADMIN_OR_OWNER', $target_role, false);
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied - Admin cannot delete other Admins or Owners']);
            exit;
        }
        
        // Only Owner can delete Owners
        if ($target_role === 'owner' && $user_role !== 'owner') {
            logSecurityAttempt('DELETE_OWNER', 'owner', false);
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied - Only Owner can delete Owner accounts']);
            exit;
        }
        
        try {
            $query = "DELETE FROM users WHERE id = ?";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([$target_id]);
            
            if ($result && $stmt->rowCount() > 0) {
                logSecurityAttempt('DELETE_USER', $target_role, true);
                echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            } else {
                logSecurityAttempt('DELETE_USER', $target_role, false);
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'User not found or already deleted']);
            }
        } catch (Exception $e) {
            logSecurityAttempt('DELETE_USER', $target_role, false);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error deleting user: ' . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
?>
