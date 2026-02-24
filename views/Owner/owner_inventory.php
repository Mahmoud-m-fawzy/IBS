<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Check if user is logged in and has owner role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    error_log("Access denied - User ID: " . ($_SESSION['user_id'] ?? 'Not set') . ", Role: " . ($_SESSION['role'] ?? 'Not set'));
    header('Location: index.php');
    exit();
}

// Debug: Log successful access
error_log("Owner inventory dashboard accessed - User ID: " . $_SESSION['user_id'] . ", Username: " . ($_SESSION['username'] ?? 'Not set'));

include_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get inventory data
$inventory = [];
try {
    // Get all products with stock information
    $productQuery = "SELECT p.*, b.name as branch_name, c.name as category_name 
                     FROM products p 
                     LEFT JOIN branches b ON p.branch_id = b.id 
                     LEFT JOIN categories c ON p.category_id = c.id 
                     ORDER BY b.name, p.name";
    $productStmt = $db->prepare($productQuery);
    $productStmt->execute();
    $allProducts = [];
    
    while ($row = $productStmt->fetch(PDO::FETCH_ASSOC)) {
        $allProducts[] = [
            'id' => (int) $row['id'],
            'code' => $row['code'],
            'barcode' => $row['barcode'],
            'brand' => $row['brand'],
            'model' => $row['model'],
            'category_name' => $row['category_name'],
            'branch_name' => $row['branch_name'],
            'stock' => (int) $row['stock'],
            'min_stock' => (int) $row['min_stock'],
            'purchase_price' => (float) $row['purchase_price'],
            'selling_price' => (float) $row['selling_price'],
            'stock_value' => (float) ($row['stock'] * $row['purchase_price']),
            'is_active' => (bool) $row['is_active'],
            'created_at' => $row['created_at']
        ];
    }
    
    // Get low stock alerts
    $lowStockQuery = "SELECT p.*, b.name as branch_name, c.name as category_name 
                        FROM products p 
                        LEFT JOIN branches b ON p.branch_id = b.id 
                        LEFT JOIN categories c ON p.category_id = c.id 
                        WHERE p.stock <= p.min_stock 
                        ORDER BY p.stock ASC 
                        LIMIT 20";
    $lowStockStmt = $db->prepare($lowStockQuery);
    $lowStockStmt->execute();
    $lowStockItems = [];
    
    while ($row = $lowStockStmt->fetch(PDO::FETCH_ASSOC)) {
        $lowStockItems[] = [
            'id' => (int) $row['id'],
            'code' => $row['code'],
            'barcode' => $row['barcode'],
            'brand' => $row['brand'],
            'model' => $row['model'],
            'category_name' => $row['category_name'],
            'branch_name' => $row['branch_name'],
            'stock' => (int) $row['stock'],
            'min_stock' => (int) $row['min_stock'],
            'purchase_price' => (float) $row['purchase_price'],
            'selling_price' => (float) $row['selling_price'],
            'stock_value' => (float) ($row['stock'] * $row['purchase_price']),
            'stock_status' => $row['stock'] <= $row['min_stock'] ? 'Critical' : ($row['stock'] <= ($row['min_stock'] * 2) ? 'Low' : 'Good'),
            'is_active' => (bool) $row['is_active'],
            'created_at' => $row['created_at']
        ];
    }
    
    // Get out of stock items
    $outOfStockQuery = "SELECT p.*, b.name as branch_name, c.name as category_name 
                         FROM products p 
                         LEFT JOIN branches b ON p.branch_id = b.id 
                         LEFT JOIN categories c ON p.category_id = c.id 
                         WHERE p.stock = 0 
                         ORDER BY b.name, p.name 
                         LIMIT 20";
    $outOfStockStmt = $db->prepare($outOfStockQuery);
    $outOfStockStmt->execute();
    $outOfStockItems = [];
    
    while ($row = $outOfStockStmt->fetch(PDO::FETCH_ASSOC)) {
        $outOfStockItems[] = [
            'id' => (int) $row['id'],
            'code' => $row['code'],
            'barcode' => $row['barcode'],
            'brand' => $row['brand'],
            'model' => $row['model'],
            'category_name' => $row['category_name'],
            'branch_name' => $row['branch_name'],
            'stock' => (int) $row['stock'],
            'min_stock' => (int) $row['min_stock'],
            'purchase_price' => (float) $row['purchase_price'],
            'selling_price' => (float) $row['selling_price'],
            'stock_value' => (float) ($row['stock'] * $row['purchase_price']),
            'stock_status' => 'Out of Stock',
            'is_active' => (bool) $row['is_active'],
            'created_at' => $row['created_at']
        ];
    }
    
    // Get branch statistics
    $branchQuery = "SELECT b.id, b.name, b.location, 
                       COUNT(p.id) as total_products,
                       SUM(p.stock) as total_stock,
                       SUM(p.stock * p.purchase_price) as total_stock_value,
                       SUM(CASE WHEN p.stock <= p.min_stock THEN 1 ELSE 0 END) as low_stock_count
                       FROM branches b 
                       LEFT JOIN products p ON b.id = p.branch_id 
                       GROUP BY b.id";
    $branchStmt = $db->prepare($branchQuery);
    $branchStmt->execute();
    $branchStats = [];
    
    while ($row = $branchStmt->fetch(PDO::FETCH_ASSOC)) {
        $branchStats[] = [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'location' => $row['location'],
            'total_products' => (int) $row['total_products'],
            'total_stock' => (int) $row['total_stock'],
            'total_stock_value' => (float) $row['total_stock_value'],
            'low_stock_count' => (int) $row['low_stock_count'],
            'low_stock_percentage' => $row['total_products'] > 0 ? round(($row['low_stock_count'] / $row['total_products']) * 100, 1) : 0
        ];
    }
    
    // Get category statistics
    $categoryQuery = "SELECT c.id, c.name, COUNT(p.id) as product_count, 
                       SUM(p.stock) as total_stock,
                       SUM(p.stock * p.purchase_price) as total_stock_value
                       FROM categories c 
                       LEFT JOIN products p ON c.id = p.category_id 
                       GROUP BY c.id";
    $categoryStmt = $db->prepare($categoryQuery);
    $categoryStmt->execute();
    $categoryStats = [];
    
    while ($row = $categoryStmt->fetch(PDO::FETCH_ASSOC)) {
        $categoryStats[] = [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'product_count' => (int) $row['product_count'],
            'total_stock' => (int) $row['total_stock'],
            'total_stock_value' => (float) $row['total_stock_value']
        ];
    }
    
    // Calculate overall statistics
    $totalProducts = count($allProducts);
    $totalStockValue = array_sum(array_column($allProducts, 'stock_value'));
    $totalLowStock = count($lowStockItems);
    $totalOutOfStock = count($outOfStockItems);
    $totalActiveProducts = count(array_filter($allProducts, function($product) {
        return $product['is_active'] && $product['stock'] > 0;
    }));
    
    // Calculate inventory health metrics
    $inventory['total_products'] = $totalProducts;
    $inventory['total_stock_value'] = $totalStockValue;
    $inventory['low_stock_items'] = $totalLowStock;
    $inventory['out_of_stock_items'] = $totalOutOfStock;
    $inventory['active_products'] = $totalActiveProducts;
    $inventory['inactive_products'] = $totalProducts - $totalActiveProducts;
    $inventory['low_stock_percentage'] = $totalProducts > 0 ? round(($totalLowStock / $totalProducts) * 100, 1) : 0;
    $inventory['out_of_stock_percentage'] = $totalProducts > 0 ? round(($totalOutOfStock / $totalProducts) * 100, 1) : 0;
    $inventory['stock_turnover'] = $totalStockValue > 0 ? ($totalStockValue / ($totalActiveProducts * 12)) : 0; // Monthly turnover rate
    $inventory['branches'] = $branchStats;
    $inventory['categories'] = $categoryStats;
    
} catch (Exception $e) {
    error_log("Error getting inventory data: " . $e->getMessage());
    $inventory = [
        'total_products' => 0,
        'total_stock_value' => 0,
        'low_stock_items' => [],
        'out_of_stock_items' => [],
        'active_products' => 0,
        'inactive_products' => 0,
        'low_stock_percentage' => 0,
        'out_of_stock_percentage' => 0,
        'stock_turnover' => 0,
        'branches' => [],
        'categories' => []
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - IBS</title>
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
        
        .inventory-dashboard {
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
        
        .alert-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .alert-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #dc3545;
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
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
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
        
        .search-box {
            margin-bottom: 20px;
        }
        
        .search-box input {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="inventory-dashboard">
        <div class="dashboard-header">
            <h1 class="dashboard-title">📦 Inventory Management</h1>
            <p>Complete Stock Control & Analysis</p>
        </div>
        
        <!-- Inventory Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>📊 Total Products</h3>
                <div class="value"><?php echo number_format($inventory['total_products']); ?></div>
                <div class="label">Items in System</div>
            </div>
            
            <div class="stat-card">
                <h3>💰 Total Stock Value</h3>
                <div class="value">EGP <?php echo number_format($inventory['total_stock_value'], 2); ?></div>
                <div class="label">Current Inventory Worth</div>
            </div>
            
            <div class="stat-card">
                <h3>📦 Active Products</h3>
                <div class="value"><?php echo number_format($inventory['active_products']); ?></div>
                <div class="label">Items with Stock > 0</div>
            </div>
            
            <div class="stat-card">
                <h3>📉 Low Stock Items</h3>
                <div class="value"><?php echo number_format($inventory['low_stock_items']); ?></div>
                <div class="label">Below Minimum Stock</div>
            </div>
            
            <div class="stat-card">
                <h3>🚫 Out of Stock Items</h3>
                <div class="value"><?php echo number_format($inventory['out_of_stock_items']); ?></div>
                <div class="label">Zero Stock Items</div>
            </div>
            
            <div class="stat-card">
                <h3>📊 Stock Health</h3>
                <div class="value"><?php echo (100 - $inventory['low_stock_percentage'] - $inventory['out_of_stock_percentage']); ?>%</div>
                <div class="label">Healthy Stock Level</div>
            </div>
            
            <div class="stat-card">
                <h3>🔄 Stock Turnover</h3>
                <div class="value"><?php echo number_format($inventory['stock_turnover'], 1); ?>/month</div>
                <div class="label">Monthly Turnover Rate</div>
            </div>
        </div>
        
        <!-- Low Stock Alerts -->
        <?php if ($inventory['low_stock_items'] > 0): ?>
        <div class="alert-section">
            <h3 class="alert-title">⚠️ Low Stock Alerts</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product Code</th>
                        <th>Brand</th>
                        <th>Model</th>
                        <th>Category</th>
                        <th>Branch</th>
                        <th>Current Stock</th>
                        <th>Min Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lowStockItems as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['code']); ?></td>
                        <td><?php echo htmlspecialchars($item['brand']); ?></td>
                        <td><?php echo htmlspecialchars($item['model']); ?></td>
                        <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['branch_name']); ?></td>
                        <td><?php echo $item['stock']; ?></td>
                        <td><?php echo $item['min_stock']; ?></td>
                        <td>
                            <span class="badge badge-warning">
                                <?php echo $item['stock_status']; ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-primary" onclick="editProduct(<?php echo $item['id']; ?>)">Edit</button>
                            <button class="btn btn-success" onclick="restockProduct(<?php echo $item['id']; ?>)">Restock</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <!-- Out of Stock Alerts -->
        <?php if ($inventory['out_of_stock_items'] > 0): ?>
        <div class="alert-section">
            <h3 class="alert-title">🚫 Out of Stock Items</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product Code</th>
                        <th>Brand</th>
                        <th>Model</th>
                        <th>Category</th>
                        <th>Branch</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($outOfStockItems as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['code']); ?></td>
                        <td><?php echo htmlspecialchars($item['brand']); ?></td>
                        <td><?php echo htmlspecialchars($item['model']); ?></td>
                        <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['branch_name']); ?></td>
                        <td><?php echo $item['stock']; ?></td>
                        <td>
                            <span class="badge badge-danger">
                                <?php echo $item['stock_status']; ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-primary" onclick="editProduct(<?php echo $item['id']; ?>)">Edit</button>
                            <button class="btn btn-success" onclick="restockProduct(<?php echo $item['id']; ?>)">Restock</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <!-- Branch Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>🏢 Branch Performance</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Branch Name</th>
                            <th>Location</th>
                            <th>Total Products</th>
                            <th>Total Stock Value</th>
                            <th>Low Stock Items</th>
                            <th>Low Stock %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventory['branches'] as $branch): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($branch['name']); ?></td>
                                <td><?php echo htmlspecialchars($branch['location']); ?></td>
                                <td><?php echo number_format($branch['total_products']); ?></td>
                                <td>EGP <?php echo number_format($branch['total_stock_value'], 2); ?></td>
                                <td><?php echo number_format($branch['low_stock_count']); ?></td>
                                <td><?php echo number_format($branch['low_stock_percentage'], 1); ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="stat-card">
                <h3>📂 Category Performance</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Total Products</th>
                            <th>Total Stock Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventory['categories'] as $category): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                <td><?php echo number_format($category['product_count']); ?></td>
                                <td>EGP <?php echo number_format($category['total_stock_value'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="action-buttons">
            <button class="btn btn-primary" onclick="window.location.href='owner_dashboard.php'">📊 Dashboard</button>
            <button class="btn btn-success" onclick="window.location.href='owner_customers.php'">👥 Customer Management</button>
            <button class="btn btn-primary" onclick="window.location.href='owner_staff.php'">👥 Staff Management</button>
            <button class="btn btn-primary" onclick="window.location.href='owner_inventory.php'">📦 Inventory Management</button>
        </div>
    </div>
</body>
</html>
