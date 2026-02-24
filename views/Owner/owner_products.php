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
error_log("Owner products dashboard accessed - User ID: " . $_SESSION['user_id'] . ", Username: " . ($_SESSION['username'] ?? 'Not set'));

include_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get product management data
$products = [];
$categories = [];
$suppliers = [];
$branches = [];
$searchTerm = '';
$selectedCategory = '';
$selectedSupplier = '';
$selectedBranch = '';

try {
    // Get all categories
    $categoryQuery = "SELECT * FROM categories ORDER BY name";
    $categoryStmt = $db->prepare($categoryQuery);
    $categoryStmt->execute();
    while ($row = $categoryStmt->fetch(PDO::FETCH_ASSOC)) {
        $categories[] = [
            'id' => (int) $row['id'],
            'name' => $row['name']
        ];
    }
    
    // Get all suppliers
    $supplierQuery = "SELECT * FROM suppliers ORDER BY name";
    $supplierStmt = $db->prepare($supplierQuery);
    $supplierStmt->execute();
    while ($row = $supplierStmt->fetch(PDO::FETCH_ASSOC)) {
        $suppliers[] = [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'contact_person' => $row['contact_person'],
            'phone' => $row['phone'],
            'email' => $row['email']
        ];
    }
    
    // Get all branches
    $branchQuery = "SELECT * FROM branches ORDER BY name";
    $branchStmt = $db->prepare($branchQuery);
    $branchStmt->execute();
    while ($row = $branchStmt->fetch(PDO::FETCH_ASSOC)) {
        $branches[] = [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'location' => $row['location']
        ];
    }
    
    // Handle search and filtering
    if (isset($_GET['search'])) {
        $searchTerm = $_GET['search'];
    }
    
    if (isset($_GET['category'])) {
        $selectedCategory = $_GET['category'];
    }
    
    if (isset($_GET['supplier'])) {
        $selectedSupplier = $_GET['supplier'];
    }
    
    if (isset($_GET['branch'])) {
        $selectedBranch = $_GET['branch'];
    }
    
    // Get products with filtering
    $whereClause = "1=1";
    $params = [];
    
    if (!empty($searchTerm)) {
        $whereClause .= " AND (p.brand LIKE ? OR p.model LIKE ? OR EXISTS (SELECT 1 FROM product_items pi WHERE pi.product_id = p.id AND (pi.item_code LIKE ? OR pi.barcode LIKE ? OR pi.imei LIKE ? OR pi.serial_number LIKE ?)))";
        $params[] = "%$searchTerm%";
        $params[] = "%$searchTerm%";
        $params[] = "%$searchTerm%";
        $params[] = "%$searchTerm%";
        $params[] = "%$searchTerm%";
    }
    
    if (!empty($selectedCategory)) {
        $whereClause .= " AND p.category_id = ?";
        $params[] = $selectedCategory;
    }
    
    if (!empty($selectedSupplier)) {
        $whereClause .= " AND p.supplier_id = ?";
        $params[] = $selectedSupplier;
    }
    
    if (!empty($selectedBranch)) {
        $whereClause .= " AND p.branch_id = ?";
        $params[] = $selectedBranch;
    }
    
    // Base query
    $productQuery = "SELECT p.*, c.name as category_name, s.name as supplier_name, b.name as branch_name 
                     FROM products p 
                     LEFT JOIN categories c ON p.category_id = c.id 
                     LEFT JOIN suppliers s ON p.supplier_id = s.id 
                     LEFT JOIN branches b ON p.branch_id = b.id 
                     WHERE $whereClause 
                     ORDER BY p.brand ASC";
    
    $productStmt = $db->prepare($productQuery);
    $productStmt->execute($params);
    
    while ($row = $productStmt->fetch(PDO::FETCH_ASSOC)) {
        $products[] = [
            'id' => (int) $row['id'],
            'code' => 'Multiple',
            'barcode' => 'Multiple',
            'brand' => $row['brand'],
            'model' => $row['model'],
            'category_name' => $row['category_name'],
            'supplier_name' => $row['supplier_name'],
            'branch_name' => $row['branch_name'],
            'stock' => (int) $row['quantity'],
            'min_stock' => (int) $row['min_stock'],
            'purchase_price' => (float) $row['purchase_price'],
            'selling_price' => (float) $row['suggested_price'],
            'stock_value' => (float) ($row['quantity'] * $row['purchase_price']),
            'is_active' => (bool) $row['is_active'],
            'created_at' => $row['created_at']
        ];
    }
    
    // Handle form submissions
    if ($_POST) {
        if (isset($_POST['add_product'])) {
            // Validate required fields
            $brand = trim($_POST['brand']);
            $model = trim($_POST['model']);
            $category_id = intval($_POST['category_id']);
            $supplier_id = intval($_POST['supplier_id']);
            $branch_id = intval($_POST['branch_id']);
            $purchase_price = floatval($_POST['purchase_price']);
            $min_selling_price = floatval($_POST['min_selling_price']);
            $stock = intval($_POST['stock']);
            
            $errors = [];
            
            if (empty($brand)) {
                $errors[] = "Brand is required";
            }
            
            if (empty($model)) {
                $errors[] = "Model is required";
            }
            
            if ($category_id <= 0) {
                $errors[] = "Please select a valid category";
            }
            
            if ($supplier_id <= 0) {
                $errors[] = "Please select a valid supplier";
            }
            
            if ($branch_id <= 0) {
                $errors[] = "Please select a valid branch";
            }
            
            if ($purchase_price <= 0) {
                $errors[] = "Purchase price must be greater than 0";
            }
            
            if ($min_selling_price <= 0) {
                $errors[] = "Minimum selling price must be greater than 0";
            }
            
            if ($stock < 0) {
                $errors[] = "Stock must be greater than or equal to 0";
            }
            
            if (empty($errors)) {
                // Insert new product
                $insertQuery = "INSERT INTO products (brand, model, category_id, supplier_id, branch_id, purchase_price, min_selling_price, quantity, is_active, created_at) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";
                $insertStmt = $db->prepare($insertQuery);
                $insertStmt->execute([$brand, $model, $category_id, $supplier_id, $branch_id, $purchase_price, $min_selling_price, $stock]);
                
                $_SESSION['success_message'] = "Product added successfully!";
                header('Location: owner_products.php');
                exit();
            }
        }
        
        if (isset($_POST['edit_product'])) {
            $product_id = intval($_POST['product_id']);
            $brand = trim($_POST['brand']);
            $model = trim($_POST['model']);
            $category_id = intval($_POST['category_id']);
            $supplier_id = intval($_POST['supplier_id']);
            $branch_id = intval($_POST['branch_id']);
            $purchase_price = floatval($_POST['purchase_price']);
            $min_selling_price = floatval($_POST['min_selling_price']);
            $stock = intval($_POST['stock']);
            
            // Update product
            $updateQuery = "UPDATE products SET brand = ?, model = ?, category_id = ?, supplier_id = ?, branch_id = ?, purchase_price = ?, min_selling_price = ?, quantity = ? WHERE id = ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([$brand, $model, $category_id, $supplier_id, $branch_id, $purchase_price, $min_selling_price, $stock, $product_id]);
            
            $_SESSION['success_message'] = "Product updated successfully!";
            header('Location: owner_products.php');
            exit();
        }
        
        if (isset($_POST['delete_product'])) {
            $product_id = intval($_POST['product_id']);
            
            // Delete product
            $deleteQuery = "DELETE FROM products WHERE id = ?";
            $deleteStmt = $db->prepare($deleteQuery);
            $deleteStmt->execute([$product_id]);
            
            $_SESSION['success_message'] = "Product deleted successfully!";
            header('Location: owner_products.php');
            exit();
        }
        
        if (isset($_POST['add_category'])) {
            $categoryName = trim($_POST['category_name']);
            
            if (empty($categoryName)) {
                $errors[] = "Category name is required";
            }
            
            if (empty($errors)) {
                // Insert new category
                $insertQuery = "INSERT INTO categories (name, created_at) VALUES (?, NOW())";
                $insertStmt = $db->prepare($insertQuery);
                $insertStmt->execute([$categoryName]);
                
                $_SESSION['success_message'] = "Category added successfully!";
                header('Location: owner_products.php');
                exit();
            }
        }
        
        if (isset($_POST['add_supplier'])) {
            $supplierName = trim($_POST['supplier_name']);
            $contactPerson = trim($_POST['contact_person']);
            $phone = trim($_POST['phone']);
            $email = trim($_POST['email']);
            
            $errors = [];
            
            if (empty($supplierName)) {
                $errors[] = "Supplier name is required";
            }
            
            if (empty($errors)) {
                // Insert new supplier
                $insertQuery = "INSERT INTO suppliers (name, contact_person, phone, email, created_at) VALUES (?, ?, ?, ?, NOW())";
                $insertStmt = $db->prepare($insertQuery);
                $insertStmt->execute([$supplierName, $contactPerson, $phone, $email]);
                
                $_SESSION['success_message'] = "Supplier added successfully!";
                header('Location: owner_products.php');
                exit();
            }
        }
    }
    
} catch (Exception $e) {
    error_log("Error in owner products dashboard: " . $e->getMessage());
    $products = [];
    $categories = [];
    $suppliers = [];
    $branches = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - IBS</title>
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
        
        .products-dashboard {
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
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="products-dashboard">
        <div class="dashboard-header">
            <h1 class="dashboard-title">📦 Product Management</h1>
            <p>Complete Product Catalog & Inventory Control</p>
        </div>
        
        <!-- Search and Filter Section -->
        <div class="search-filters">
            <div class="search-box">
                <input type="text" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="🔍 Search products..." class="search-box">
            </div>
            
            <div class="filter-box">
                <select name="category_filter" class="filter-box">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo ($selectedCategory == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-box">
                <select name="supplier_filter" class="filter-box">
                    <option value="">All Suppliers</option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?php echo $supplier['id']; ?>" <?php echo ($selectedSupplier == $supplier['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($supplier['name']); ?>
                        </option>
                    <?php endforeach; ?>
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
            </div>
            
            <button type="submit" class="btn btn-primary">🔍 Search</button>
        </div>
        
        <!-- Action Buttons -->
        <div style="margin-bottom: 20px;">
            <a href="owner_products.php?action=add_product" class="btn btn-success">➕ Add Product</a>
            <a href="owner_products.php?action=add_category" class="btn btn-primary">📂 Add Category</a>
            <a href="owner_products.php?action=add_supplier" class="btn btn-primary">🏢 Add Supplier</a>
            <a href="owner_dashboard.php" class="btn btn-primary">📊 Dashboard</a>
        </div>
        
        <!-- Success Message -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($_SESSION['success_message']); ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <!-- Products Table -->
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Brand</th>
                    <th>Model</th>
                    <th>Category</th>
                    <th>Supplier</th>
                    <th>Branch</th>
                    <th>Stock</th>
                    <th>Purchase Price</th>
                    <th>Selling Price</th>
                    <th>Stock Value</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="13" style="text-align: center; padding: 40px; color: #666;">
                            No products found matching your criteria
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['code']); ?></td>
                        <td><?php echo htmlspecialchars($product['brand']); ?></td>
                        <td><?php echo htmlspecialchars($product['model']); ?></td>
                        <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                        <td><?php echo htmlspecialchars($product['supplier_name']); ?></td>
                        <td><?php echo htmlspecialchars($product['branch_name']); ?></td>
                        <td><?php echo $product['stock']; ?></td>
                        <td><?php echo number_format($product['purchase_price'], 2); ?></td>
                        <td><?php echo number_format($product['selling_price'], 2); ?></td>
                        <td><?php echo number_format($product['stock_value'], 2); ?></td>
                        <td>
                            <span class="badge <?php echo $product['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <a href="owner_products.php?action=edit_product&id=<?php echo $product['id']; ?>" class="btn btn-primary">Edit</a>
                            <a href="owner_products.php?action=delete_product&id=<?php echo $product['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>