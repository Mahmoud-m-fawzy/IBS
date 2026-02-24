<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    error_log("Access denied - User ID: " . ($_SESSION['user_id'] ?? 'Not set') . ", Role: " . ($_SESSION['role'] ?? 'Not set'));
    header('Location: index.php');
    exit();
}

// Debug: Log successful access
error_log("Admin staff dashboard accessed - User ID: " . $_SESSION['user_id'] . ", Username: " . ($_SESSION['username'] ?? 'Not set'));

include_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get staff management data
$staffMembers = [];
$users = [];
$branches = [];
$searchTerm = '';
$selectedRole = '';
$selectedBranch = '';
$selectedStatus = '';

try {
    // Get all users with admin and staff roles
    $userQuery = "SELECT u.*, b.name as branch_name FROM users u 
                     LEFT JOIN branches b ON u.branch_id = b.id 
                     WHERE u.role IN ('admin', 'staff') 
                     ORDER BY u.name ASC";
    $userStmt = $db->prepare($userQuery);
    $userStmt->execute();
    while ($row = $userStmt->fetch(PDO::FETCH_ASSOC)) {
        $staffMembers[] = [
            'id' => (int) $row['id'],
            'username' => $row['username'],
            'name' => $row['name'],
            'email' => $row['email'] ?? 'Not Assigned',
            'phone' => $row['phone'] ?? 'Not Assigned',
            'role' => $row['role'],
            'branch_name' => $row['branch_name'] ?? 'Not Assigned',
            'is_active' => (bool) $row['is_active'],
            'status' => $row['is_active'] ? 'Active' : 'Inactive',
            'created_at' => $row['created_at'],
            'last_login' => $row['last_login'] ?? 'Never',
            'total_sales' => 0, // Will be calculated
            'total_revenue' => 0, // Will be calculated
            'created_at' => $row['created_at']
        ];
    }
    
    // Get all branches
    $branchQuery = "SELECT b.id, b.name, b.location FROM branches b ORDER BY b.name";
    $branchStmt = $db->prepare($branchQuery);
    $branchStmt->execute();
    while ($row = $branchStmt->fetch(PDO::FETCH_ASSOC)) {
        $branches[] = [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'location' => $row['location'],
            'staff_count' => 0 // Will be calculated
        ];
    }
    
    // Calculate branch statistics
    foreach ($branches as $branch) {
        $branchStaffCount = 0;
        foreach ($staffMembers as $staff) {
            if ($staff['branch_id'] == $branch['id']) {
                $branchStaffCount++;
            }
        }
        $branches[] = [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'location' => $row['location'],
            'staff_count' => $branchStaffCount
        ];
    }
    
    // Handle search and filtering
    if (isset($_GET['search'])) {
        $searchTerm = $_GET['search'];
    }
    
    if (isset($_GET['role'])) {
        $selectedRole = $_GET['role'];
    }
    
    if (isset($_GET['branch'])) {
        $selectedBranch = $_GET['branch'];
    }
    
    if (isset($_GET['status'])) {
        $selectedStatus = $_GET['status'];
    }
    
    // Get filtered staff
    $whereClause = "1=1";
    $params = [];
    
    if (!empty($searchTerm)) {
        $whereClause .= " AND (u.username LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
        $params[] = "%$searchTerm%";
    }
    
    if (!empty($selectedRole)) {
        $whereClause .= " AND u.role = ?";
        $params[] = $selectedRole;
    }
    
    if (!empty($selectedBranch)) {
        $whereClause .= " AND u.branch_id = ?";
        $params[] = $selectedBranch;
    }
    
    if (!empty($selectedStatus)) {
        $whereClause .= " AND u.is_active = ?";
        $params[] = ($selectedStatus === 'active') ? 1 : 0;
    }
    
    // Apply filters
    $userQuery = "SELECT u.*, b.name as branch_name FROM users u 
                     LEFT JOIN branches b ON u.branch_id = b.id 
                     WHERE $whereClause 
                     ORDER BY u.name ASC";
    $userStmt = $db->prepare($userQuery);
    $userStmt->execute($params);
    
    while ($row = $userStmt->fetch(PDO::FETCH_ASSOC)) {
        $staffMembers[] = [
            'id' => (int) $row['id'],
            'username' => $row['username'],
            'name' => $row['name'],
            'email' => $row['email'] ?? 'Not Assigned',
            'role' => $row['role'],
            'branch_name' => $row['branch_name'] ?? 'Not Assigned',
            'is_active' => (bool) $row['is_active'],
            'status' => $row['is_active'] ? 'Active' : 'Inactive',
            'created_at' => $row['created_at'],
            'last_login' => $row['last_login'] ?? 'Never',
            'total_sales' => 0, // Will be calculated
            'total_revenue' => 0, // Will be calculated
            'created_at' => $row['created_at']
        ];
    }
    
    // Handle form submissions
    if ($_POST) {
        if (isset($_POST['add_user'])) {
            // Validate required fields
            $username = trim($_POST['username']);
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $password = trim($_POST['password']);
            $role = trim($_POST['role']);
            $branch_id = intval($_POST['branch_id']);
            
            $errors = [];
            
            if (empty($username)) {
                $errors[] = "Username is required";
            }
            
            if (empty($name)) {
                $errors[] = "Name is required";
            }
            
            if (empty($password)) {
                $errors[] = "Password is required";
            }
            
            if (empty($role)) {
                $errors[] = "Role is required";
            }
            
            if (!in_array($role, ['admin', 'staff'])) {
                $errors[] = "Role must be either admin or staff";
            }
            
            if ($branch_id <= 0) {
                $errors[] = "Please select a valid branch";
            }
            
            // Check if username already exists
            $checkQuery = "SELECT id FROM users WHERE username = ?";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([$username]);
            
            if ($checkStmt->fetch()) {
                $errors[] = "Username already exists";
            }
            
            if (empty($errors)) {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $insertQuery = "INSERT INTO users (username, password, role, branch_id, is_active, created_at) 
                                 VALUES (?, ?, ?, ?, ?, 1, NOW())";
                $insertStmt = $db->prepare($insertQuery);
                $insertStmt->execute([$username, $hashedPassword, $role, $branch_id]);
                
                $_SESSION['success_message'] = "User added successfully! Username: $username";
                header('Location: admin_staff.php');
                exit();
            }
        }
        
        if (isset($_POST['edit_user'])) {
            $user_id = intval($_POST['user_id']);
            $username = trim($_POST['username']);
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $role = trim($_POST['role']);
            $branch_id = intval($_POST['branch_id']);
            
            // Update user
            $updateQuery = "UPDATE users SET username = ?, name = ?, email = ?, phone = ?, role = ?, branch_id = ? WHERE id = ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([$username, $name, $email, $phone, $role, $branch_id, $user_id]);
            
            $_SESSION['success_message'] = "User updated successfully!";
                header('Location: admin_staff.php');
                exit();
            }
        }
        
        if (isset($_POST['toggle_user_status'])) {
            $user_id = intval($_POST['user_id']);
            
            // Toggle user status
            $toggleQuery = "UPDATE users SET is_active = NOT is_active, updated_at = NOW() WHERE id = ?";
            $toggleStmt = $db->prepare($toggleQuery);
            $toggleStmt->execute([$user_id]);
            
            $_SESSION['success_message'] = "User status updated successfully!";
                header('Location: admin_staff.php');
                exit();
        }
        
        if (isset($_POST['delete_user'])) {
            $user_id = intval($_POST['user_id']);
            
            // Delete user
            $deleteQuery = "DELETE FROM users WHERE id = ?";
            $deleteStmt = $db->prepare($deleteQuery);
            $deleteStmt->execute([$user_id]);
            
            $_SESSION['success_message'] = "User deleted successfully!";
                header('Location: admin_staff.php');
                exit();
        }
    }
    
} catch (Exception $e) {
    error_log("Error in admin staff dashboard: " . $e->getMessage());
    $staffMembers = [];
    $users = [];
    $branches = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Administration - IBS</title>
    <link rel="stylesheet" href="components/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
            color: #333;
        }
        
        .staff-dashboard {
            padding: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .dashboard-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .search-filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .search-box {
            flex: 1;
            min-width: 200px;
        }
        
        .search-box input {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .filter-box {
            min-width: 150px;
        }
        
        .filter-box select {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a6268;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #1e7e34;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .data-table th {
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        
        .data-table tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-success {
            background: #28a745;
            color: white;
        }
        
        .badge-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .badge-danger {
            background: #dc3545;
            color: white;
        }
        
        .badge-inactive {
            background: #6c757d;
            color: white;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 12px rgba(0,0,0,0.15);
        }
        
        .stat-card h3 {
            font-size: 16px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .stat-card .value {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-card .label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="staff-dashboard">
        <div class="dashboard-header">
            <h1 class="dashboard-title">👥 Staff Administration</h1>
            <p>Complete User Management & Access Control</p>
        </div>
        
        <!-- Search and Filter Section -->
        <div class="search-filters">
            <div class="search-box">
                <input type="text" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="🔍 Search staff members..." class="search-box">
            </div>
            
            <div class="filter-box">
                <select name="role_filter" class="filter-box">
                    <option value="">All Roles</option>
                    <option value="admin" <?php echo ($selectedRole == 'admin') ? 'selected' : ''; ?>>Admin</option>
                    <option value="staff" <?php echo ($selectedRole == 'staff') ? 'selected' : ''; ?>>Staff</option>
                </select>
            </div>
            
            <div class="filter-box">
                <select name="branch_filter" class="filter-box">
                    <option value="">All Branches</option>
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?php echo $branch['id']; ?>" <?php echo ($selectedBranch == $branch['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($branch['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            
            <div class="filter-box">
                <select name="status_filter" class="filter-box">
                    <option value="">All Status</option>
                    <option value="1" <?php echo ($selectedStatus == '1') ? 'selected' : ''; ?>>Active</option>
                    <option value="0" <?php echo ($selectedStatus == '0') ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">🔍 Search</button>
        </div>
        
        <!-- Action Buttons -->
        <div style="margin-bottom: 20px;">
            <a href="admin_staff.php?action=add_user" class="btn btn-success">➕ Add User</a>
            <a href="admin_dashboard.php" class="btn btn-primary">📊 Dashboard</a>
        </div>
        
        <!-- Users Table -->
        <table class="data-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Branch</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($staffMembers)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px; color: #666;">
                            No staff members found matching your criteria
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($staffMembers as $member): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($member['username']); ?></td>
                        <td><?php echo htmlspecialchars($member['name']); ?></td>
                        <td><?php echo htmlspecialchars($member['email']); ?></td>
                        <td><?php echo htmlspecialchars($member['phone']); ?></td>
                        <td><?php echo htmlspecialchars($member['role']); ?></td>
                        <td><?php echo htmlspecialchars($member['branch_name']); ?></td>
                        <td>
                            <span class="badge <?php echo $member['is_active'] ? 'badge-success' : 'badge-inactive'; ?>">
                                <?php echo $member['status']; ?>
                            </span>
                        </td>
                        <td>
                            <a href="admin_staff.php?action=edit_user&id=<?php echo $member['id']; ?>" class="btn btn-primary">Edit</a>
                            <a href="admin_staff.php?action=toggle_user_status&id=<?php echo $member['id']; ?>" class="btn <?php echo $member['is_active'] ? 'btn-danger' : 'btn-success'; ?>">
                                <?php echo $member['is_active'] ? 'Deactivate' : 'Activate'; ?>
                            </a>
                            <a href="admin_staff.php?action=delete_user&id=<?php echo $member['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center;">
            <?php echo htmlspecialchars($_SESSION['success_message']); ?>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
</body>
</html>
