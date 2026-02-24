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

// Get all products across all branches with stock information
$products = [];
try {
    $query = "SELECT p.*, b.name as branch_name, b.location as branch_location,
              c.name as category_name,
              (p.stock * p.purchase_price) as stock_value,
              CASE 
                WHEN p.stock <= p.min_stock THEN 'Critical'
                WHEN p.stock <= (p.min_stock * 2) THEN 'Low'
                ELSE 'Good'
              END as stock_status
              FROM products p 
              LEFT JOIN branches b ON p.branch_id = b.id 
              LEFT JOIN categories c ON p.category_id = c.id 
              ORDER BY b.name, p.name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $products[] = [
            'id' => (int) $row['id'],
            'code' => $row['code'],
            'barcode' => $row['barcode'],
            'name' => $row['name'],
            'brand' => $row['brand'],
            'model' => $row['model'],
            'category' => $row['category_name'] ?? 'Uncategorized',
            'branch_name' => $row['branch_name'] ?? 'Not Assigned',
            'branch_location' => $row['branch_location'] ?? 'Not Assigned',
            'stock' => (int) $row['stock'],
            'min_stock' => (int) $row['min_stock'],
            'purchase_price' => (float) $row['purchase_price'],
            'selling_price' => (float) $row['selling_price'],
            'stock_value' => (float) $row['stock_value'],
            'stock_status' => $row['stock_status'],
            'is_active' => (bool) $row['is_active'],
            'created_at' => $row['created_at']
        ];
    }
} catch (Exception $e) {
    error_log("Error loading products: " . $e->getMessage());
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

// Get all categories for filter dropdown
$categories = [];
try {
    $query = "SELECT id, name FROM categories ORDER BY name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $categories[] = $row;
    }
} catch (Exception $e) {
    error_log("Error loading categories: " . $e->getMessage());
}

// Calculate statistics
$stats = [];
try {
    // Total products across all branches
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM products");
    $stmt->execute();
    $stats['total_products'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total stock value
    $stmt = $db->prepare("SELECT SUM(stock * purchase_price) as total_value FROM products");
    $stmt->execute();
    $stats['total_stock_value'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_value'] ?? 0;
    
    // Low stock items
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM products WHERE stock <= min_stock");
    $stmt->execute();
    $stats['low_stock_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Critical stock items
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM products WHERE stock <= min_stock / 2");
    $stmt->execute();
    $stats['critical_stock_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
} catch (Exception $e) {
    error_log("Error calculating stats: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Management - Owner Dashboard</title>
    <link rel="stylesheet" href="components/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .owner-container {
            padding: 20px;
            max-width: 1600px;
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid;
        }
        
        .stat-card.total { border-left-color: #3498db; }
        .stat-card.value { border-left-color: #27ae60; }
        .stat-card.low { border-left-color: #f39c12; }
        .stat-card.critical { border-left-color: #e74c3c; }
        
        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9em;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 1px;
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
        
        .stock-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stock-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .stock-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: bold;
            color: #2c3e50;
            border-bottom: 2px solid #dee2e6;
            white-space: nowrap;
        }
        
        .stock-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
            white-space: nowrap;
        }
        
        .stock-table tr:hover {
            background: #f8f9fa;
        }
        
        .product-name {
            font-weight: bold;
            color: #2c3e50;
        }
        
        .product-details {
            font-size: 0.85em;
            color: #666;
        }
        
        .branch-info {
            font-size: 0.9em;
            color: #666;
        }
        
        .stock-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .stock-status.good {
            background: #d4edda;
            color: #155724;
        }
        
        .stock-status.low {
            background: #fff3cd;
            color: #856404;
        }
        
        .stock-status.critical {
            background: #f8d7da;
            color: #721c24;
        }
        
        .action-btns {
            display: flex;
            gap: 6px;
        }
        
        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
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
            <h1><i class="fas fa-warehouse"></i> Stock Management</h1>
            <a href="owner_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Statistics Overview -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-value"><?php echo number_format($stats['total_products']); ?></div>
                <div class="stat-label">Total Products</div>
            </div>
            
            <div class="stat-card value">
                <div class="stat-value"><?php echo number_format($stats['total_stock_value'], 2); ?> EGP</div>
                <div class="stat-label">Total Stock Value</div>
            </div>
            
            <div class="stat-card low">
                <div class="stat-value"><?php echo number_format($stats['low_stock_count']); ?></div>
                <div class="stat-label">Low Stock Items</div>
            </div>
            
            <div class="stat-card critical">
                <div class="stat-value"><?php echo number_format($stats['critical_stock_count']); ?></div>
                <div class="stat-label">Critical Stock Items</div>
            </div>
        </div>

        <!-- Controls Section -->
        <div class="controls-section">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="🔍 Search products by name, code, barcode, brand...">
            </div>
            
            <select class="filter-select" id="branchFilter">
                <option value="">All Branches</option>
                <?php foreach ($branches as $branch): ?>
                    <option value="<?php echo $branch['id']; ?>"><?php echo htmlspecialchars($branch['name']); ?></option>
                <?php endforeach; ?>
            </select>
            
            <select class="filter-select" id="categoryFilter">
                <option value="">All Categories</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                <?php endforeach; ?>
            </select>
            
            <select class="filter-select" id="statusFilter">
                <option value="">All Status</option>
                <option value="good">Good Stock</option>
                <option value="low">Low Stock</option>
                <option value="critical">Critical Stock</option>
            </select>
            
            <a href="owner_add_product.php" class="add-btn">
                <i class="fas fa-plus"></i> Add Product
            </a>
        </div>

        <!-- Stock Table -->
        <div class="stock-table">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Branch</th>
                        <th>Stock</th>
                        <th>Stock Status</th>
                        <th>Purchase Price</th>
                        <th>Selling Price</th>
                        <th>Stock Value</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="stock-tbody">
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="8" class="no-results">
                                <i class="fas fa-box-open"></i> No products found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                    <div class="product-details">
                                        <?php echo htmlspecialchars($product['brand']); ?> <?php echo htmlspecialchars($product['model']); ?>
                                        <br>Code: <?php echo htmlspecialchars($product['code']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="branch-info">
                                        <strong><?php echo htmlspecialchars($product['branch_name']); ?></strong>
                                        <br><?php echo htmlspecialchars($product['branch_location']); ?>
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo number_format($product['stock']); ?></strong>
                                    <?php if ($product['min_stock'] > 0): ?>
                                        <br><small>Min: <?php echo $product['min_stock']; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="stock-status <?php echo $product['stock_status']; ?>">
                                        <?php echo $product['stock_status']; ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($product['purchase_price'], 2); ?> EGP</td>
                                <td><?php echo number_format($product['selling_price'], 2); ?> EGP</td>
                                <td><strong><?php echo number_format($product['stock_value'], 2); ?> EGP</strong></td>
                                <td>
                                    <div class="action-btns">
                                        <button class="action-btn view-btn" onclick="viewProduct(<?php echo $product['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="action-btn edit-btn" onclick="editProduct(<?php echo $product['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="action-btn delete-btn" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                            <i class="fas fa-trash"></i>
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
            const rows = document.querySelectorAll('#stock-tbody tr');
            
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
            const rows = document.querySelectorAll('#stock-tbody tr');
            
            rows.forEach(row => {
                if (branchId === '') {
                    row.style.display = '';
                } else {
                    // This would need to be implemented with data attributes
                    row.style.display = '';
                }
            });
        });

        // Category filter
        document.getElementById('categoryFilter').addEventListener('change', function() {
            const categoryId = this.value;
            const rows = document.querySelectorAll('#stock-tbody tr');
            
            rows.forEach(row => {
                if (categoryId === '') {
                    row.style.display = '';
                } else {
                    // This would need to be implemented with data attributes
                    row.style.display = '';
                }
            });
        });

        // Status filter
        document.getElementById('statusFilter').addEventListener('change', function() {
            const status = this.value;
            const rows = document.querySelectorAll('#stock-tbody tr');
            
            rows.forEach(row => {
                if (status === '') {
                    row.style.display = '';
                } else {
                    // This would need to be implemented with data attributes
                    row.style.display = '';
                }
            });
        });

        function viewProduct(id) {
            window.location.href = 'owner_view_product.php?id=' + id;
        }

        function editProduct(id) {
            window.location.href = 'owner_edit_product.php?id=' + id;
        }

        function deleteProduct(id) {
            if (confirm('Are you sure you want to delete this product?')) {
                window.location.href = 'owner_delete_product.php?id=' + id;
            }
        }
    </script>
</body>
</html>
