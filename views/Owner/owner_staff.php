<?php
session_start();

// Check if user is logged in and has owner role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header('Location: index.php');
    exit();
}

include_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get all staff across all branches
$staffMembers = [];
try {
    $query = "SELECT u.*, b.name as branch_name, b.location as branch_location 
              FROM users u 
              LEFT JOIN branches b ON u.branch_id = b.id 
              WHERE u.role IN ('admin', 'staff') 
              ORDER BY b.name, u.name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $staffMembers[] = [
            'id' => (int) $row['id'],
            'username' => $row['username'],
            'name' => $row['name'],
            'role' => $row['role'],
            'branch_name' => $row['branch_name'] ?? 'Not Assigned',
            'branch_location' => $row['branch_location'] ?? 'Not Assigned',
            'is_active' => (bool) $row['is_active'],
            'status' => $row['is_active'] ? 'Active' : 'Inactive',
            'created_at' => $row['created_at']
        ];
    }
} catch (Exception $e) {
    error_log("Error loading staff members: " . $e->getMessage());
}

// Get all branches for filter dropdown
$branches = [];
try {
    $query = "SELECT id, name, location FROM branches ORDER BY name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $branches[] = $row;
    }
} catch (Exception $e) {
    error_log("Error loading branches: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management - Owner Dashboard</title>
    <link rel="stylesheet" href="components/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .owner-container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .owner-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .owner-header h1 {
            margin: 0;
            font-size: 2.2em;
        }
        
        .back-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid white;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background: rgba(255,255,255,0.3);
            color: white;
            text-decoration: none;
        }
        
        .controls-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .search-box {
            flex: 1;
            min-width: 300px;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .filter-select {
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            background: white;
            cursor: pointer;
        }
        
        .add-btn {
            background: #27ae60;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            transition: background 0.3s ease;
        }
        
        .add-btn:hover {
            background: #229954;
            color: white;
            text-decoration: none;
        }
        
        .staff-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .staff-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .staff-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: bold;
            color: #2c3e50;
            border-bottom: 2px solid #dee2e6;
        }
        
        .staff-table td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .staff-table tr:hover {
            background: #f8f9fa;
        }
        
        .role-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .role-badge.admin {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .role-badge.staff {
            background: #e8f5e8;
            color: #388e3c;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-badge.active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .action-btns {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s ease;
        }
        
        .edit-btn {
            background: #3498db;
            color: white;
        }
        
        .edit-btn:hover {
            background: #2980b9;
        }
        
        .delete-btn {
            background: #e74c3c;
            color: white;
        }
        
        .delete-btn:hover {
            background: #c0392b;
        }
        
        .branch-info {
            font-size: 0.9em;
            color: #666;
        }
        
        .no-results {
            text-align: center;
            padding: 40px;
            color: #666;
            font-size: 1.1em;
        }
    </style>
</head>
<body>
    <div class="owner-container">
        <div class="owner-header">
            <h1><i class="fas fa-users-cog"></i> Staff Management</h1>
            <a href="owner_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Controls Section -->
        <div class="controls-section">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="🔍 Search staff by name, username, role, or branch...">
            </div>
            
            <select class="filter-select" id="branchFilter">
                <option value="">All Branches</option>
                <?php foreach ($branches as $branch): ?>
                    <option value="<?php echo $branch['id']; ?>"><?php echo htmlspecialchars($branch['name']); ?></option>
                <?php endforeach; ?>
            </select>
            
            <select class="filter-select" id="roleFilter">
                <option value="">All Roles</option>
                <option value="admin">Admin</option>
                <option value="staff">Staff</option>
            </select>
            
            <a href="owner_add_staff.php" class="add-btn">
                <i class="fas fa-plus"></i> Add Staff
            </a>
        </div>

        <!-- Staff Table -->
        <div class="staff-table">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Branch</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="staff-tbody">
                    <?php if (empty($staffMembers)): ?>
                        <tr>
                            <td colspan="6" class="no-results">
                                <i class="fas fa-users-slash"></i> No staff members found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($staffMembers as $member): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($member['username']); ?></td>
                                <td><?php echo htmlspecialchars($member['name']); ?></td>
                                <td>
                                    <span class="role-badge <?php echo $member['role']; ?>">
                                        <?php echo ucfirst($member['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="branch-info">
                                        <strong><?php echo htmlspecialchars($member['branch_name']); ?></strong>
                                        <br><?php echo htmlspecialchars($member['branch_location']); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $member['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $member['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <button class="action-btn edit-btn" onclick="editStaff(<?php echo $member['id']; ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="action-btn delete-btn" onclick="deleteStaff(<?php echo $member['id']; ?>)">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#staff-tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Branch filter
        document.getElementById('branchFilter').addEventListener('change', function() {
            const branchId = this.value;
            const rows = document.querySelectorAll('#staff-tbody tr');
            
            rows.forEach(row => {
                if (branchId === '') {
                    row.style.display = '';
                } else {
                    // This would need to be implemented with data attributes
                    row.style.display = '';
                }
            });
        });

        // Role filter
        document.getElementById('roleFilter').addEventListener('change', function() {
            const role = this.value;
            const rows = document.querySelectorAll('#staff-tbody tr');
            
            rows.forEach(row => {
                if (role === '') {
                    row.style.display = '';
                } else {
                    // This would need to be implemented with data attributes
                    row.style.display = '';
                }
            });
        });

        function editStaff(id) {
            // Redirect to edit page or open modal
            window.location.href = 'owner_edit_staff.php?id=' + id;
        }

        function deleteStaff(id) {
            if (confirm('Are you sure you want to delete this staff member?')) {
                window.location.href = 'owner_delete_staff.php?id=' + id;
            }
        }
    </script>
</body>
</html>
