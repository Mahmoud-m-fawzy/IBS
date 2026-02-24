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

// Get all customers across all branches
$customers = [];
try {
    $query = "SELECT c.*, b.name as branch_name, b.location as branch_location,
              COUNT(s.id) as total_orders,
              COALESCE(SUM(s.total_amount), 0) as total_spent
              FROM customers c 
              LEFT JOIN branches b ON c.branch_id = b.id 
              LEFT JOIN sales s ON c.id = s.customer_id 
              GROUP BY c.id 
              ORDER BY c.name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $customers[] = [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'phone' => $row['phone'] ?? 'N/A',
            'email' => $row['email'] ?? 'N/A',
            'address' => $row['address'] ?? 'N/A',
            'branch_name' => $row['branch_name'] ?? 'Not Assigned',
            'branch_location' => $row['branch_location'] ?? 'Not Assigned',
            'total_orders' => (int) $row['total_orders'],
            'total_spent' => (float) $row['total_spent'],
            'created_at' => $row['created_at']
        ];
    }
} catch (Exception $e) {
    error_log("Error loading customers: " . $e->getMessage());
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
    <title>Customer Management - Owner Dashboard</title>
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
        
        .customer-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .customer-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .customer-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: bold;
            color: #2c3e50;
            border-bottom: 2px solid #dee2e6;
        }
        
        .customer-table td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .customer-table tr:hover {
            background: #f8f9fa;
        }
        
        .customer-name {
            font-weight: bold;
            color: #2c3e50;
        }
        
        .customer-contact {
            font-size: 0.9em;
            color: #666;
        }
        
        .branch-info {
            font-size: 0.9em;
            color: #666;
        }
        
        .stats-info {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .stat-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .orders-badge {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .spent-badge {
            background: #e8f5e8;
            color: #388e3c;
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
        
        .view-btn {
            background: #3498db;
            color: white;
        }
        
        .view-btn:hover {
            background: #2980b9;
        }
        
        .edit-btn {
            background: #f39c12;
            color: white;
        }
        
        .edit-btn:hover {
            background: #e67e22;
        }
        
        .delete-btn {
            background: #e74c3c;
            color: white;
        }
        
        .delete-btn:hover {
            background: #c0392b;
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
            <h1><i class="fas fa-user-friends"></i> Customer Management</h1>
            <a href="owner_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Controls Section -->
        <div class="controls-section">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="🔍 Search customers by name, phone, or email...">
            </div>
            
            <select class="filter-select" id="branchFilter">
                <option value="">All Branches</option>
                <?php foreach ($branches as $branch): ?>
                    <option value="<?php echo $branch['id']; ?>"><?php echo htmlspecialchars($branch['name']); ?></option>
                <?php endforeach; ?>
            </select>
            
            <a href="owner_add_customer.php" class="add-btn">
                <i class="fas fa-plus"></i> Add Customer
            </a>
        </div>

        <!-- Customer Table -->
        <div class="customer-table">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Branch</th>
                        <th>Statistics</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="customer-tbody">
                    <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="5" class="no-results">
                                <i class="fas fa-users-slash"></i> No customers found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td>
                                    <div class="customer-name"><?php echo htmlspecialchars($customer['name']); ?></div>
                                </td>
                                <td>
                                    <div class="customer-contact">
                                        <div><i class="fas fa-phone"></i> <?php echo htmlspecialchars($customer['phone']); ?></div>
                                        <div><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($customer['email']); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="branch-info">
                                        <strong><?php echo htmlspecialchars($customer['branch_name']); ?></strong>
                                        <br><?php echo htmlspecialchars($customer['branch_location']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="stats-info">
                                        <span class="stat-badge orders-badge">
                                            <?php echo $customer['total_orders']; ?> Orders
                                        </span>
                                        <span class="stat-badge spent-badge">
                                            <?php echo number_format($customer['total_spent'], 2); ?> EGP
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <button class="action-btn view-btn" onclick="viewCustomer(<?php echo $customer['id']; ?>)">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <button class="action-btn edit-btn" onclick="editCustomer(<?php echo $customer['id']; ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="action-btn delete-btn" onclick="deleteCustomer(<?php echo $customer['id']; ?>)">
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
            const rows = document.querySelectorAll('#customer-tbody tr');
            
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
            const rows = document.querySelectorAll('#customer-tbody tr');
            
            rows.forEach(row => {
                if (branchId === '') {
                    row.style.display = '';
                } else {
                    // This would need to be implemented with data attributes
                    row.style.display = '';
                }
            });
        });

        function viewCustomer(id) {
            window.location.href = 'owner_view_customer.php?id=' + id;
        }

        function editCustomer(id) {
            window.location.href = 'owner_edit_customer.php?id=' + id;
        }

        function deleteCustomer(id) {
            if (confirm('Are you sure you want to delete this customer?')) {
                window.location.href = 'owner_delete_customer.php?id=' + id;
            }
        }
    </script>
</body>
</html>
