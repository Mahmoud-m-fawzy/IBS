<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

include_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Barcode generation functions
function generateEAN13Barcode($productCode) {
    // Extract numeric part from product code
    $numeric = preg_replace('/[^0-9]/', '', $productCode);
    
    // Pad to 12 digits (EAN-13 without checksum)
    if (strlen($numeric) < 12) {
        $numeric = str_pad($numeric, 12, '0', STR_PAD_LEFT);
    } elseif (strlen($numeric) > 12) {
        $numeric = substr($numeric, 0, 12);
    }
    
    // Calculate checksum
    $checksum = calculateEAN13Checksum($numeric);
    
    return $numeric . $checksum;
}

function calculateEAN13Checksum($digits) {
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $digit = (int) $digits[$i];
        if ($i % 2 == 0) {
            $sum += $digit;
        } else {
            $sum += $digit * 3;
        }
    }
    $checksum = (10 - ($sum % 10)) % 10;
    return $checksum;
}

$staffMembers = [];
try {
    // Debug: Check database connection
    error_log("Database connection status: " . ($db ? "Connected" : "Not connected"));
    
    // Test query to check if table exists and has data
    $testQuery = "SELECT COUNT(*) as total FROM users";
    $testStmt = $db->prepare($testQuery);
    $testStmt->execute();
    $totalUsers = $testStmt->fetch(PDO::FETCH_ASSOC)['total'];
    error_log("Total users in database: " . $totalUsers);
    
    $query = "SELECT id, username, name, role, phone, email, is_active, created_at 
              FROM users 
              WHERE role != 'admin'
              ORDER BY name";
    error_log("SQL Query: " . $query);
    
    $stmt = $db->prepare($query);
    $result = $stmt->execute();
    error_log("Execute result: " . ($result ? "Success" : "Failed"));
    
    $rowCount = $stmt->rowCount();
    error_log("Row count: " . $rowCount);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $staffMembers[] = [
            'id' => (int) $row['id'],
            'username' => $row['username'],
            'name' => $row['name'],
            'role' => $row['role'],
            'phone' => $row['phone'] ?? '', 
            'email' => $row['email'] ?? '', 
            'is_active' => (int) $row['is_active'],
            'status' => (int)$row['is_active'] === 1 ? 'Active' : 'Inactive',
            'created_at' => $row['created_at']
        ];
    }
    
    // Debug: Log loaded data
    error_log("Staff members loaded: " . count($staffMembers) . " items");
    if (count($staffMembers) > 0) {
        error_log("First staff member: " . print_r($staffMembers[0], true));
    } else {
        error_log("No staff members found in database");
    }
} catch (Exception $e) {
    error_log("Error loading staff members: " . $e->getMessage());
    $staffMembers = [];
}

if (isset($_GET['logout'])) {
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session
    if (session_destroy()) {
        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Redirect to login page
        header('Location: index.php');
        exit;
    } else {
        // If session destruction fails, try alternative method
        unset($_SESSION['user_id']);
        unset($_SESSION['username']);
        unset($_SESSION['role']);
        unset($_SESSION['name']);
        header('Location: index.php');
        exit;
    }
}

// Handle form submissions
if ($_POST) {
    if (isset($_POST['add_product'])) {
        // Validate required fields and values
        $purchase_price = floatval($_POST['purchase_price']);
        $min_selling_price = floatval($_POST['min_selling_price']);
        $suggested_price = floatval($_POST['suggested_price']);
        $stock = intval($_POST['stock']);

        if ($purchase_price <= 0) {
            $error = "Purchase price must be greater than 0.";
        } elseif ($min_selling_price <= 0) {
            $error = "Minimum selling price must be greater than 0.";
        } elseif ($suggested_price <= 0) {
            $error = "Suggested selling price must be greater than 0.";
        } elseif ($min_selling_price < $purchase_price) {
            $error = "Minimum selling price cannot be less than purchase price.";
        } elseif ($suggested_price < $min_selling_price) {
            $error = "Suggested selling price cannot be less than minimum selling price.";
        } elseif ($stock <= 0) {
            $error = "Stock quantity must be greater than 0.";
        } elseif (empty($_POST['brand']) || empty($_POST['model'])) {
            $error = "Brand and Model are required fields.";
        } else {
            // Handle optional min_stock and category_id
            $minStock = !empty($_POST['min_stock']) ? (int)$_POST['min_stock'] : null;
            $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;

            $query = "INSERT INTO products (brand, model, purchase_price, min_selling_price, suggested_price, quantity, min_stock, category_id, description) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            if ($stmt->execute([$_POST['brand'], $_POST['model'], $purchase_price, $min_selling_price, $suggested_price, $stock, $minStock, $categoryId, $_POST['description']])) {
                // Redirect to prevent form resubmission
                header('Location: admin_dashboard.php?success=' . urlencode("Product template added successfully! You can now add individual items with serial/IMEI in the Inventory tab."));
                exit;
            } else {
                $error = "Failed to add product. Please try again.";
            }
        }
    }

    if (isset($_POST['add_user'])) {
        $query = "INSERT INTO users (username, password, role, name, phone, email, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)";
        $stmt = $db->prepare($query);
        if ($stmt->execute([$_POST['username'], $_POST['password'], $_POST['role'], $_POST['name'], $_POST['phone'], $_POST['email']])) {
            // Redirect to prevent form resubmission
            header('Location: admin_dashboard.php?success=' . urlencode("User added successfully!"));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title data-translate="navigation.dashboard">Admin Dashboard - IBS</title>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <link rel="stylesheet" href="components/css/style.css?v=<?php echo time(); ?>">
    <style>
        /* Fallback icons using Unicode symbols */
        .fa-language::before { content: "🌐"; }
        .fa-user::before { content: "👤"; }
        .fa-users::before { content: "👥"; }
        .fa-box::before { content: "📦"; }
        .fa-shopping-cart::before { content: "🛒"; }
        .fa-chart-bar::before { content: "📊"; }
        .fa-money-bill::before { content: "💰"; }
        .fa-credit-card::before { content: "💳"; }
        .fa-receipt::before { content: "🧾"; }
        .fa-edit::before { content: "✏️"; }
        .fa-trash::before { content: "🗑️"; }
        .fa-plus::before { content: "➕"; }
        .fa-minus::before { content: "➖"; }
        .fa-search::before { content: "🔍"; }
        .fa-print::before { content: "🖨️"; }
        .fa-camera::before { content: "📷"; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="components/js/translations.js?v=<?php echo time(); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
</head>

<body id="body-lang">
    <!-- Language Toggle Button -->
    <button class="language-toggle" id="languageToggle" onclick="toggleLanguage()" title="Toggle Language">
        <span class="lang-icon">🇪🇬</span>
        <span class="lang-text">EG</span>
    </button>
    
    <div class="header">
        <div style="display: flex; align-items: center; gap: 15px;">
            <img src="components/css/logo.jpeg" alt="IBS Store Logo" style="width: 40px; height: auto; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);" />
            <h1 data-translate="navigation.dashboard">🛠️ IBS Admin Dashboard</h1>
        </div>
        <div>
            <span data-translate="navigation.welcome">Welcome</span>, <?php echo $_SESSION['name']; ?>
            <a href="?logout=1" 
               style="color: white; margin-left: 15px; text-decoration: none; padding: 8px 15px; border-radius: 6px; transition: all 0.3s; display: inline-block; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); font-weight: 500; position: relative; z-index: 1000;" 
               data-translate="navigation.logout" 
               onmouseover="this.style.background='rgba(255,255,255,0.2)'; this.style.transform='translateY(-2px)';" 
               onmouseout="this.style.background='rgba(255,255,255,0.1)'; this.style.transform='translateY(0)';">
                🚪 Logout
            </a>
        </div>
    </div>

    <div class="nav-tabs">
        <button class="nav-tab active" onclick="console.log('Receipt tab clicked'); showTab('receipt')" data-translate="sales.receipt">🧾 Receipt</button>
        <button class="nav-tab" onclick="console.log('Products tab clicked'); showTab('products')" data-translate="inventory.addProduct">📦 Add Product</button>
        <button class="nav-tab" onclick="console.log('Inventory tab clicked'); showTab('inventory')" data-translate="navigation.inventory">📋 Inventory</button>
        <button class="nav-tab" onclick="console.log('Sales tab clicked'); showTab('sales')" data-translate="navigation.sales">💰 Sales</button>
        <button class="nav-tab" onclick="console.log('Customers tab clicked'); showTab('customers')" data-translate="navigation.customers">👥 CUSTOMERS</button>
        <button class="nav-tab" onclick="console.log('Staff tab clicked'); showTab('staff')" data-translate="navigation.staff">👥 Staff</button>
        <button class="nav-tab" onclick="console.log('Financial tab clicked'); showTab('financial'); loadIncome(); loadPayment();" data-translate="navigation.financial">💰 Financial</button>
        <button class="nav-tab" onclick="console.log('Reports tab clicked'); showTab('reports')" data-translate="navigation.reports">📊 Reports</button>
    </div>

    <div class="content">
        <?php if (isset($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php include 'views/admin/receipt_tab.php'; ?>

        <?php include 'views/admin/products_tab.php'; ?>

        <?php include 'views/admin/inventory_tab.php'; ?>

        <?php include 'views/admin/sales_tab.php'; ?>

        <?php include 'views/admin/customers_tab.php'; ?>

        <?php include 'views/admin/staff_tab.php'; ?>

        <?php include 'views/admin/financial_tab.php'; ?>

        <?php include 'views/admin/reports_tab.php'; ?>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal"
        style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); overflow-y: auto;">
        <div
            style="background-color: white; margin: 2% auto; padding: 40px; border-radius: 15px; width: 90%; max-width: 700px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); min-height: auto;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px;">
                <h2 style="margin: 0; color: #333; font-size: 1.8em;">✏️ Edit User Information</h2>
                <button onclick="closeEditModal()"
                    style="background: none; border: none; font-size: 28px; cursor: pointer; color: #666; padding: 5px; border-radius: 50%; transition: background 0.3s;"
                    onmouseover="this.style.background='#f0f0f0'"
                    onmouseout="this.style.background='none'">&times;</button>
            </div>

            <form id="editUserForm">
                <input type="hidden" id="editUserId">

                <!-- Account Information Section -->
                <div style="margin-bottom: 25px;">
                    <h3
                        style="color: #0056b3; margin-bottom: 15px; font-size: 1.2em; border-left: 4px solid #0056b3; padding-left: 10px;">
                        🔐 Account Information</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Username:</label>
                            <input type="text" id="editUsername" readonly
                                style="background: #f5f5f5; cursor: not-allowed;">
                            <small style="color: #666; font-size: 12px;">Username cannot be changed</small>
                        </div>

                        <div class="form-group">
                            <label>New Password:</label>
                            <input type="password" id="editPassword" placeholder="Leave blank to keep current password"
                                minlength="4">
                            <small style="color: #666; font-size: 12px;">Minimum 4 characters (optional)</small>
                        </div>
                    </div>
                </div>

                <!-- Personal Information Section -->
                <div style="margin-bottom: 25px;">
                    <h3
                        style="color: #0056b3; margin-bottom: 15px; font-size: 1.2em; border-left: 4px solid #0056b3; padding-left: 10px;">
                        👤 Personal Information</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Full Name: <span style="color: red;">*</span></label>
                            <input type="text" id="editName" required>
                        </div>
                        <div class="form-group">
                            <label>Phone Number:</label>
                            <input type="tel" id="editPhone" placeholder="Enter phone number">
                        </div>
                        <div class="form-group">
                            <label>Email Address:</label>
                            <input type="email" id="editEmail" placeholder="Enter email address">
                        </div>

                        <div class="form-group">
                            <label>Role: <span style="color: red;">*</span></label>
                            <select id="editRole" required>
                                <option value="staff">Staff</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>

                    <!-- Account Status Section -->
                    <div style="margin-bottom: 25px;">
                        <h3
                            style="color: #0056b3; margin-bottom: 15px; font-size: 1.2em; border-left: 4px solid #0056b3; padding-left: 10px;">
                            🔐 Account Status</h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label>Status: <span style="color: red;">*</span></label>
                                <select id="editStatus" required>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div
                        style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px; padding-top: 20px; border-top: 2px solid #f0f0f0;">
                        <button type="button" onclick="closeEditModal()" class="btn btn-secondary"
                            style="padding: 12px 25px; font-size: 16px;">Cancel</button>
                        <button type="submit" class="btn" style="padding: 12px 25px; font-size: 16px;">💾 Save
                            Changes</button>
                    </div>
                </form>
            </div>
        </div>

    <!-- Edit Product Modal -->
    <div id="editProductModal"
        style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); overflow-y: auto;">
        <div
            style="background-color: white; margin: 2% auto; padding: 40px; border-radius: 15px; width: 90%; max-width: 700px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); min-height: auto;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px;">
                <h2 style="margin: 0; color: #333; font-size: 1.8em;">📦 Edit Product Information</h2>
                <button onclick="closeEditProductModal()"
                    style="background: none; border: none; font-size: 28px; cursor: pointer; color: #666; padding: 5px; border-radius: 50%; transition: background 0.3s;"
                    onmouseover="this.style.background='#f0f0f0'"
                    onmouseout="this.style.background='none'">&times;</button>
            </div>

            <form id="editProductForm">
                <input type="hidden" id="editProductId">

                <!-- Product Code Section -->
                <div style="margin-bottom: 25px;">
                    <h3
                        style="color: #0056b3; margin-bottom: 15px; font-size: 1.2em; border-left: 4px solid #0056b3; padding-left: 10px;">
                        🏷️ Product Code</h3>
                    <div class="form-group">
                        <label>Product Code:</label>
                        <input type="text" id="editProductCode" readonly
                            style="width: 50%; max-width: 50%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px; background: #f5f5f5; cursor: not-allowed;">
                        <small style="color: #666; font-size: 12px;">Product code cannot be changed</small>

                <!-- Categorization & Status Section -->
                <div style="margin-bottom: 25px;">
                    <h3
                        style="color: #0056b3; margin-bottom: 15px; font-size: 1.2em; border-left: 4px solid #0056b3; padding-left: 10px;">
                        🏷️ Categorization & Status</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Category ID:</label>
                            <input type="number" id="editProductCategoryId" min="0"
                                style="width: 100%; max-width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                            <small style="color: #666; font-size: 12px;">Numeric category identifier</small>
                        </div>
                        <div class="form-group">
                            <label>Status:</label>
                            <select id="editProductIsActive"
                                style="width: 100%; max-width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="form-group" style="grid-column: 1 / span 2;">
                            <label>Image URL:</label>
                            <input type="text" id="editProductImageUrl"
                                style="width: 100%; max-width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                        </div>
                    </div>
                </div>
                
                <!-- Pricing & Stock Section -->
                <div style="margin-bottom: 25px;">
                    <h3
                        style="color: #28a745; margin-bottom: 15px; font-size: 1.2em; border-left: 4px solid #28a745; padding-left: 10px;">
                        💰 Pricing & Stock</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Search Product (Type or Scan Barcode):</label>
                            <div style="position: relative;">
                                <input type="text" id="product-search-edit"
                                    placeholder="🔍 Type product code, brand, model, or scan barcode..."
                                    style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px; padding-right: 40px;"
                                    onkeyup="searchProducts(this.value)"
                                    onkeypress="if(event.key==='Enter') selectFirstProduct()"
                                    oninput="handleProductInput(this.value)">
                                <div style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: #666;">
                                    <span id="scan-indicator-edit">📷</span>
                                </div>
                            </div>
                            <div id="product-search-results"
                                style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; border-top: none; display: none; background: white; position: relative; z-index: 100;">
                            </div>
                            <div id="scan-feedback" style="margin-top: 5px; font-size: 12px; color: #666; display: none;">
                                Scanning barcode...
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Brand:</label>
                            <input type="text" id="editProductBrand" required
                                style="width: 100%; max-width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                        </div>
                        <div class="form-group">
                            <label>Model:</label>
                            <input type="text" id="editProductModel" required
                                style="width: 100%; max-width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                        </div>
                        <div class="form-group">
                            <label>Price:</label>
                            <input type="number" id="editProductPrice" step="0.01" min="0.01" required
                                style="width: 100%; max-width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                        </div>
                        <div class="form-group">
                            <label>Stock Quantity:</label>
                            <input type="number" id="editProductStock" min="0" required
                                style="width: 100%; max-width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                        </div>
                        <div class="form-group">
                            <label>Minimum Stock:</label>
                            <input type="number" id="editProductMinStock" min="0"
                                style="width: 100%; max-width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                        </div>
                        <div class="form-group">
                            <label>Purchase Price:</label>
                            <input type="number" id="editProductPurchasePrice" step="0.01" min="0"
                                style="width: 100%; max-width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                        </div>
                        <div class="form-group">
                            <label>Minimum Selling Price:</label>
                            <input type="number" id="editProductMinSellingPrice" step="0.01" min="0"
                                style="width: 100%; max-width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                        </div>
                    </div>
                </div>

                <!-- Additional Information Section -->
                <div style="margin-bottom: 30px;">
                    <h3
                        style="color: #0056b3; margin-bottom: 15px; font-size: 1.2em; border-left: 4px solid #0056b3; padding-left: 10px;">
                        📝 Additional Information</h3>
                    <div class="form-group">
                        <label>Description:</label>
                        <textarea id="editProductDescription" rows="3"
                            style="width: 100%; max-width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px; resize: vertical;"></textarea>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div
                    style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px; padding-top: 20px; border-top: 2px solid #f0f0f0;">
                    <button type="button" onclick="closeEditProductModal()" class="btn btn-secondary"
                        style="padding: 12px 25px; font-size: 16px;">Cancel</button>
                    <button type="submit" class="btn" style="padding: 12px 25px; font-size: 16px;">💾 Save
                        Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Receipt Details Modal -->
    <div id="receiptDetailsModal"
        style="display: none; position: fixed; z-index: 99999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); overflow-y: auto;">
        <div
            style="background-color: white; margin: 2% auto; padding: 40px; border-radius: 15px; width: 90%; max-width: 800px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); min-height: auto;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px;">
                <h2 style="margin: 0; color: #333; font-size: 1.8em;" data-translate="sales.receiptDetails">🧾 Receipt Details</h2>
                <button onclick="closeReceiptDetailsModal()"
                    style="background: none; border: none; font-size: 28px; cursor: pointer; color: #666; padding: 5px; border-radius: 50%; transition: background 0.3s;"
                    onmouseover="this.style.background='#f0f0f0'"
                    onmouseout="this.style.background='none'">&times;</button>
            </div>

            <!-- Receipt Header Information -->
            <div style="margin-bottom: 25px;">
                <h3
                    style="color: #0056b3; margin-bottom: 15px; font-size: 1.2em; border-left: 4px solid #0056b3; padding-left: 10px;"
                    data-translate="sales.receiptInformation">📋 Receipt Information</h3>
                <div
                    style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; background: #f8f9fa; padding: 20px; border-radius: 10px;">
                    <div>
                        <strong data-translate="sales.receiptNumberLabel">Receipt Number:</strong> <span id="detailReceiptNumber"></span><br>
                        <strong data-translate="sales.date">Date:</strong> <span id="detailDate"></span><br>
                        <strong data-translate="sales.staff">Staff:</strong> <span id="detailStaff"></span>
                    </div>
                    <div>
                        <strong data-translate="sales.customer">Customer:</strong> <span id="detailCustomer"></span><br>
                        <strong data-translate="sales.paymentMethod">Payment Method:</strong> <span id="detailPaymentMethod"></span><br>
                        <strong data-translate="sales.totalAmount">Total Amount:</strong> <span id="detailTotalAmount"
                            style="color: #28a745; font-weight: bold;"></span>
                    </div>
                </div>
            </div>

            <!-- Items List Layout (Original, Returns, Adjusted) -->
            <div id="receiptSectionsContainer">
                <!-- Section 1: Original Purchase -->
                <div id="originalPurchaseSection" style="margin-bottom: 25px;">
                    <h3 style="color: #64748b; margin-bottom: 15px; font-size: 1.1em; border-left: 4px solid #64748b; padding-left: 10px;"
                        data-translate="sales.originalPurchase">Original Purchase</h3>
                    <div style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; margin-bottom: 10px;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead style="background: #f8fafc;">
                                <tr>
                                    <th style="padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0;">Product</th>
                                    <th style="padding: 12px; text-align: center; border-bottom: 1px solid #e2e8f0;">Qty</th>
                                    <th style="padding: 12px; text-align: right; border-bottom: 1px solid #e2e8f0;">Price</th>
                                    <th style="padding: 12px; text-align: right; border-bottom: 1px solid #e2e8f0;">Total</th>
                                </tr>
                            </thead>
                            <tbody id="originalPurchaseTable"></tbody>
                        </table>
                    </div>
                    <div style="text-align: right; padding: 10px; font-weight: bold; background: #f8fafc; border-radius: 8px;">
                        <span data-translate="sales.originalTotal">Original Total:</span> <span id="originalTotalAmount"></span>
                    </div>
                </div>

                <!-- Section 2: Returned Items -->
                <div id="returnedItemsSection" style="margin-bottom: 25px; display: none;">
                    <h3 style="color: #ef4444; margin-bottom: 15px; font-size: 1.1em; border-left: 4px solid #ef4444; padding-left: 10px;"
                        data-translate="sales.returnedItems">Returned Items</h3>
                    <div style="border: 1px solid #fecaca; border-radius: 8px; overflow: hidden; margin-bottom: 10px;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead style="background: #fff5f5;">
                                <tr>
                                    <th style="padding: 12px; text-align: left; border-bottom: 1px solid #fecaca;">Product</th>
                                    <th style="padding: 12px; text-align: center; border-bottom: 1px solid #fecaca;">Returned Qty</th>
                                    <th style="padding: 12px; text-align: right; border-bottom: 1px solid #fecaca;">Price</th>
                                    <th style="padding: 12px; text-align: right; border-bottom: 1px solid #fecaca;">Credit</th>
                                </tr>
                            </thead>
                            <tbody id="returnedItemsTable"></tbody>
                        </table>
                    </div>
                    <div style="text-align: right; padding: 10px; font-weight: bold; background: #fff5f5; border-radius: 8px; color: #ef4444;">
                        <span data-translate="sales.returnCredit">Return Credit:</span> <span id="returnCreditAmount"></span>
                    </div>
                </div>

                <!-- Section 3: Final Adjusted Receipt -->
                <div id="adjustedReceiptSection" style="margin-bottom: 25px; display: none;">
                    <h3 style="color: #22c55e; margin-bottom: 15px; font-size: 1.1em; border-left: 4px solid #22c55e; padding-left: 10px;"
                        data-translate="sales.adjustedReceipt">Final Adjusted Receipt</h3>
                    <div style="border: 1px solid #bbf7d0; border-radius: 8px; overflow: hidden; margin-bottom: 10px;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead style="background: #f0fdf4;">
                                <tr>
                                    <th style="padding: 12px; text-align: left; border-bottom: 1px solid #bbf7d0;">Product</th>
                                    <th style="padding: 12px; text-align: center; border-bottom: 1px solid #bbf7d0;">Current Qty</th>
                                    <th style="padding: 12px; text-align: right; border-bottom: 1px solid #bbf7d0;">Price</th>
                                    <th style="padding: 12px; text-align: right; border-bottom: 1px solid #bbf7d0;">Current Total</th>
                                </tr>
                            </thead>
                            <tbody id="adjustedReceiptTable"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Receipt Summary (Final Net Total) -->
            <div style="margin-bottom: 30px;">
                <h3 style="color: #0056b3; margin-bottom: 15px; font-size: 1.2em; border-left: 4px solid #0056b3; padding-left: 10px;"
                    data-translate="sales.paymentSummary">💰 Payment Summary</h3>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; border: 2px solid #22c55e;">
                    <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 1.3em;">
                        <span id="finalTotalLabel" data-translate="sales.netTotal">Net Total:</span>
                        <span id="detailGrandTotal" style="color: #166534;"></span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 30px; padding-top: 20px; border-top: 2px solid #f1f5f9;">
                <button type="button" onclick="closeReceiptDetailsModal()" 
                    data-translate="sales.close"
                    style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 28px; font-size: 15px; font-weight: 700; border-radius: 10px; border: 2px solid #e2e8f0; background: white; color: #64748b; cursor: pointer; transition: all 0.2s;"
                    onmouseover="this.style.background='#f8fafc'; this.style.borderColor='#cbd5e1';"
                    onmouseout="this.style.background='white'; this.style.borderColor='#e2e8f0';">
                    ✕ Close
                </button>
                <button type="button" onclick="printReceiptFromModal()" 
                    data-translate="sales.printReceipt"
                    style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 28px; font-size: 15px; font-weight: 700; border-radius: 10px; border: none; background: linear-gradient(135deg, #1e293b 0%, #334155 100%); color: white; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 12px rgba(30,41,59,0.3);"
                    onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 16px rgba(30,41,59,0.4)';"
                    onmouseout="this.style.transform=''; this.style.boxShadow='0 4px 12px rgba(30,41,59,0.3)';">
                    🖨️ Print Receipt
                </button>
            </div>
        </div>
    </div>

    <!-- Return Items Modal -->
    <div id="returnItemsModal"
        style="display: none; position: fixed; z-index: 99999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); overflow-y: auto;">
        <div
            style="background-color: white; margin: 2% auto; padding: 40px; border-radius: 15px; width: 90%; max-width: 800px; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px;">
                <h2 style="margin: 0; color: #333; font-size: 1.8em;" data-translate="sales.returnItemsTitle">🔄 Return Items Selection</h2>
                <button onclick="closeReturnItemsModal()"
                    style="background: none; border: none; font-size: 28px; cursor: pointer; color: #666; padding: 5px; border-radius: 50%; transition: background 0.3s;"
                    onmouseover="this.style.background='#f0f0f0'"
                    onmouseout="this.style.background='none'">&times;</button>
            </div>

            <div style="margin-bottom: 25px; background: #fff8e1; padding: 15px; border-radius: 8px; border-left: 5px solid #ffc107;">
                <p style="margin: 0; font-weight: 600; color: #856404;">
                    Select the quantity of each item you wish to return. Restored items will be marked as "available" in inventory.
                </p>
            </div>

            <!-- Receipt Header Info Summary -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 25px; font-size: 0.9em;">
                <div>
                    <strong>Receipt:</strong> <span id="returnReceiptNumber"></span><br>
                    <strong>Customer:</strong> <span id="returnCustomer"></span>
                </div>
                <div style="text-align: right;">
                    <strong>Date:</strong> <span id="returnDate"></span><br>
                    <strong>Total Sale:</strong> <span id="returnTotalAmount"></span>
                </div>
            </div>

            <div style="margin-bottom: 25px;">
                <div style="border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead style="background: #f1f5f9;">
                            <tr>
                                <th style="padding: 12px; text-align: left; font-size: 0.85em; text-transform: uppercase; color: #64748b;">Product</th>
                                <th style="padding: 12px; text-align: center; font-size: 0.85em; text-transform: uppercase; color: #64748b;">Sold</th>
                                <th style="padding: 12px; text-align: center; font-size: 0.85em; text-transform: uppercase; color: #64748b;">Returned</th>
                                <th style="padding: 12px; text-align: center; font-size: 0.85em; text-transform: uppercase; color: #64748b; width: 120px;">Return Qty</th>
                            </tr>
                        </thead>
                        <tbody id="returnItemsTableBody">
                            <!-- Populated via JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 30px; padding-top: 20px; border-top: 2px solid #f1f5f9;">
                <button type="button" onclick="closeReturnItemsModal()"
                    style="padding: 12px 25px; font-weight: 700; border-radius: 10px; border: 2px solid #e2e8f0; background: white; color: #64748b; cursor: pointer;">
                    Cancel
                </button>
                <button type="button" onclick="submitReturnItems()" id="submitReturnBtn"
                    style="padding: 12px 25px; font-weight: 700; border-radius: 10px; border: none; background: #e11d48; color: white; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                    🔄 Confirm Return
                </button>
            </div>
        </div>
    </div>

    <!-- Confirm Return Styled Modal -->
    <div id="confirmReturnModal"
        style="display: none; position: fixed; z-index: 999999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.7); overflow-y: auto;">
        <div style="background-color: white; margin: 10% auto; padding: 30px; border-radius: 12px; width: 90%; max-width: 450px; box-shadow: 0 10px 40px rgba(0,0,0,0.4); text-align: center;">
            <div style="font-size: 50px; margin-bottom: 20px;">⚠️</div>
            <h2 id="confirmReturnModalTitle" style="margin: 0 0 15px 0; color: #1e293b; font-size: 1.5em;" data-translate="sales.confirmReturnTitle">Confirm Return</h2>
            <p id="confirmReturnModalMessage" style="color: #64748b; font-size: 1.1em; line-height: 1.5; margin-bottom: 30px;" data-translate="sales.confirmReturnMessage">
                Are you sure you want to process this return? Selected items will be restored to stock.
            </p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button type="button" onclick="closeConfirmReturnModal()"
                    style="flex: 1; padding: 12px; font-weight: 700; border-radius: 8px; border: 2px solid #e2e8f0; background: white; color: #64748b; cursor: pointer;"
                    data-translate="sales.cancelReturnBtn">
                    Cancel
                </button>
                <button type="button" id="executeReturnBtn"
                    style="flex: 1; padding: 12px; font-weight: 700; border-radius: 8px; border: none; background: #e11d48; color: white; cursor: pointer;"
                    data-translate="sales.confirmReturnBtn">
                    Confirm Return
                </button>
            </div>
        </div>
    </div>

    <!-- Return Success Styled Modal -->
    <div id="returnSuccessModal"
        style="display: none; position: fixed; z-index: 999999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.7); overflow-y: auto;">
        <div style="background-color: white; margin: 15% auto; padding: 30px; border-radius: 12px; width: 90%; max-width: 400px; box-shadow: 0 10px 40px rgba(0,0,0,0.4); text-align: center;">
            <div style="font-size: 50px; margin-bottom: 20px;">✅</div>
            <h2 id="returnSuccessModalTitle" style="margin: 0 0 15px 0; color: #059669; font-size: 1.5em;" data-translate="sales.returnSuccessTitle">Return Processed</h2>
            <p id="returnSuccessModalMessage" style="color: #64748b; font-size: 1.1em; line-height: 1.5; margin-bottom: 30px;" data-translate="sales.returnSuccessMessage">
                Return processed successfully! Inventory and statistics have been updated.
            </p>
            <button type="button" onclick="closeReturnSuccessModal()"
                style="width: 100%; padding: 12px; font-weight: 700; border-radius: 8px; border: none; background: #059669; color: white; cursor: pointer;"
                data-translate="sales.closeBtn">
                Close
            </button>
        </div>
    </div>

    <!-- Additional CSS for better modal styling -->
    <style>
        @media (max-width: 768px) {
            #editUserModal>div {
                margin: 5% auto !important;
                width: 95% !important;
                padding: 20px !important;
            }

            #editUserModal div[style*="grid-template-columns"] {
                grid-template-columns: 1fr !important;
                gap: 15px !important;
            }

            #editUserModal h2 {
                font-size: 1.4em !important;
            }

            #editUserModal h3 {
                font-size: 1.1em !important;
            }

            #editUserModal .form-group input,
            #editUserModal .form-group select {
                max-width: 100% !important;
            }
        }

        #editUserModal>div {
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Improve form field styling */
        #editUserModal .form-group {
            margin-bottom: 15px;
        }

        #editUserModal .form-group input,
        #editUserModal .form-group select {
            width: 100%;
            max-width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        #editUserModal .form-group input:focus,
        #editUserModal .form-group select:focus {
            outline: none;
            border-color: #0056b3;
            box-shadow: 0 0 0 3px rgba(0, 86, 179, 0.1);
        }

        #editUserModal .form-group label {
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
            display: block;
        }
    </style>

    <script>
        // Simple test to verify JavaScript is working
        console.log('=== MAIN SCRIPT START ===');
        
        let currentReceipt = {
            items: [],
            total: 0
        };
        let products = [];
        let allInventoryProducts = []; // Store all products for search filtering
        let allStaffMembers = <?php 
            if (isset($staffMembers) && is_array($staffMembers)) {
                echo json_encode($staffMembers, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
            } else {
                echo '[]';
            }
        ?>; // Store all staff for search filtering
        let allSuppliers = []; // Store all suppliers for dropdown

        // Global showTab function - must be outside DOMContentLoaded
        function showTab(tabName) {
            console.log('Switching to tab:', tabName);
            
            // Hide all tab contents
            const allTabContents = document.querySelectorAll('.tab-content');
            allTabContents.forEach(tab => {
                tab.classList.remove('active');
                tab.style.display = 'none';
            });
            
            // Remove active class from all nav tabs
            const allNavTabs = document.querySelectorAll('.nav-tab');
            allNavTabs.forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show the selected tab content
            const targetTabContent = document.getElementById(tabName);
            if (targetTabContent) {
                targetTabContent.classList.add('active');
                targetTabContent.style.display = 'block';
                console.log('Tab content found and activated:', tabName);
                
                // Trigger sales data loading if sales tab is activated
                if (tabName === 'sales') {
                    setTimeout(() => {
                        console.log('Triggering sales/stats data load...');
                        loadSales();
                        loadSalesStats();
                    }, 200);
                }

                // Trigger customer data loading if customers tab is activated
            } else {
                console.error('Tab content not found:', tabName);
            }
            
            // Activate the corresponding nav tab
            const targetNavTab = Array.from(allNavTabs).find(tab => {
                const onclick = tab.getAttribute('onclick');
                return onclick && onclick.includes("'" + tabName + "'");
            });
            
            if (targetNavTab) {
                targetNavTab.classList.add('active');
                console.log('Nav tab activated:', targetNavTab.textContent);
            } else {
                console.error('Nav tab not found for:', tabName);
            }
        }
        
        // Ensure showTab is globally accessible
        window.showTab = showTab;
        
        // Test showTab function
        console.log('showTab function defined:', typeof showTab);
        console.log('window.showTab function defined:', typeof window.showTab);

        document.addEventListener('DOMContentLoaded', function () {
            // Add event listeners for product search functionality
            const firstRow = document.querySelector('.payment-method-row');
            if (firstRow) {
                firstRow.querySelector('.payment-method-select').addEventListener('change', updatePaymentTotals);
                firstRow.querySelector('.payment-amount').addEventListener('input', updatePaymentTotals);
            }
            
            // Add event listeners for barcode scanning
            const productSearchInput = document.getElementById('product-search');
            const productSearchEditInput = document.getElementById('product-search-edit');
            
            if (productSearchInput) {
                productSearchInput.addEventListener('input', handleProductInput);
            }
            if (productSearchEditInput) {
                productSearchEditInput.addEventListener('input', handleProductInput);
            }

            // Add dynamic label update for item selection modal
            const modalQuantity = document.getElementById('modal-quantity');
            if (modalQuantity) {
                modalQuantity.addEventListener('input', function() {
                    const quantity = parseInt(this.value) || 0;
                    const itemsLabel = document.getElementById('modal-selection-count');
                    if (itemsLabel) {
                        const totalAvailable = document.querySelectorAll('.item-checkbox').length;
                        itemsLabel.textContent = `Select First ${quantity} items (of ${totalAvailable} available)`;
                    }
                    updateItemSelection();
                });
            }
            
            // Handle URL hash for receipt scanner
            if (window.location.hash && window.location.hash.startsWith('#receipt-details-')) {
                const receiptId = window.location.hash.replace('#receipt-details-', '');
                console.log('Opening receipt details from scanner:', receiptId);
                
                // Wait for DOM to be fully loaded, then open receipt details
                const openReceiptDetails = () => {
                    // Check if required elements exist
                    const modal = document.getElementById('receiptDetailsModal');
                    const itemsTable = document.getElementById('receiptItemsTable');
                    
                    if (modal && itemsTable) {
                        console.log('DOM elements found, opening receipt details');
                        viewReceiptDetails(parseInt(receiptId));
                    } else {
                        console.log('DOM elements not ready, retrying...');
                        setTimeout(openReceiptDetails, 500);
                    }
                };
                
                // Start checking after a short delay
                setTimeout(openReceiptDetails, 1000);
            }
            
            loadSuppliers();
            loadProducts();
            loadInventory();
            loadSales();
            loadStaff();
            loadReports();
            loadIncome();
            loadPayment();

            // Add payment row event listeners
            const paymentRows = document.querySelectorAll('.payment-method-row');
            paymentRows.forEach(row => {
                row.querySelector('.payment-method-select').addEventListener('change', updatePaymentTotals);
                row.querySelector('.payment-amount').addEventListener('input', updatePaymentTotals);
            });

            // Check if product was just added and refresh inventory
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('success')) {
                // Clear any search filters
                const searchInput = document.getElementById('inventory-search');
                if (searchInput) {
                    searchInput.value = '';
                }
                // Refresh inventory after a short delay to ensure database is updated
                setTimeout(function () {
                    loadProducts();
                }, 500);
            }

            // Add search functionality
            const searchInput = document.getElementById('inventory-search');
            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    filterInventory(this.value);
                });
            }

            // Add staff search functionality
            const staffSearchInput = document.getElementById('staff-search');
            if (staffSearchInput) {
                staffSearchInput.addEventListener('input', function () {
                    filterStaff(this.value);
                });
            }

            // Handle edit form submission
            const editForm = document.getElementById('editUserForm');
            if (editForm) {
                editForm.addEventListener('submit', async function (e) {
                    e.preventDefault();

                    const userId = document.getElementById('editUserId').value;
                    const password = document.getElementById('editPassword').value;

                    const userData = {
                        id: parseInt(userId),
                        name: document.getElementById('editName').value,
                        role: document.getElementById('editRole').value,
                        phone: document.getElementById('editPhone').value,
                        email: document.getElementById('editEmail').value,
                        is_active: parseInt(document.getElementById('editStatus').value)
                    };

                    // Only include password if it's not empty
                    if (password.trim()) {
                        if (password.length < 4) {
                            alert('Password must be at least 4 characters long');
                            return;
                        }
                        userData.password = password;
                    }
                    
                    // Validate phone format (optional but if provided, should be valid)
                    const phone = document.getElementById('editPhone').value.trim();
                    if (phone && !/^[\d\s\-\+\(\)]*$/.test(phone)) {
                        alert('Please enter a valid phone number (digits, spaces, hyphens, plus, parentheses only)');
                        return;
                    }
                    
                    // Validate email format (optional but if provided, should be valid)
                    const email = document.getElementById('editEmail').value.trim();
                    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                        alert('Please enter a valid email address');
                        return;
                    }

                    try {
                        const response = await fetch('api/users.php', {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(userData)
                        });

                        const result = await response.json();

                        if (result.success) {
                            const message = password.trim() ?
                                'User updated successfully! Password has been changed.' :
                                'User updated successfully!';
                            alert(message);
                            closeEditModal();

                            // Update the local data
                            const userIndex = allStaffMembers.findIndex(member => member.id === parseInt(userId));
                            if (userIndex !== -1) {
                                allStaffMembers[userIndex] = {
                                    ...allStaffMembers[userIndex],
                                    ...userData,
                                    status: userData.is_active ? 'Active' : 'Inactive'
                                };
                                displayStaff(allStaffMembers);

                                // Update search results count
                                const resultsCountDiv = document.getElementById('staff-search-results-count');
                                if (resultsCountDiv) {
                                    resultsCountDiv.textContent = `Showing all ${allStaffMembers.length} staff members`;
                                    resultsCountDiv.style.color = '#666';
                                }
                            }
                        } else {
                            alert('Failed to update user: ' + (result.message || 'Unknown error'));
                        }
                    } catch (error) {
                        console.error('Error updating user:', error);
                        alert('Error updating user. Please try again.');
                    }
                });
            }

            // Handle edit product form submission
            const editProductForm = document.getElementById('editProductForm');
            if (editProductForm) {
                editProductForm.addEventListener('submit', async function (e) {
                    e.preventDefault();

                    const productId = document.getElementById('editProductId').value;
                    const price = parseFloat(document.getElementById('editProductPrice').value);
                    const stock = parseInt(document.getElementById('editProductStock').value);
                    const minStock = parseInt(document.getElementById('editProductMinStock').value || '0');
                    const categoryId = parseInt(document.getElementById('editProductCategoryId').value || '0');
                    const isActive = parseInt(document.getElementById('editProductIsActive').value || '1');
                    const imageUrl = document.getElementById('editProductImageUrl').value || '';
                    const purchasePrice = parseFloat(document.getElementById('editProductPurchasePrice').value || '0');
                    const minSellingPrice = parseFloat(document.getElementById('editProductMinSellingPrice').value || '0');

                    // Validate input
                    if (price <= 0) {
                        alert('Price must be greater than 0');
                        return;
                    }

                    if (stock < 0) {
                        alert('Stock quantity cannot be negative');
                        return;
                    }
                    if (minStock < 0) {
                        alert('Minimum stock cannot be negative');
                        return;
                    }
                    if (categoryId < 0) {
                        alert('Category ID cannot be negative');
                        return;
                    }
                    if (!isNaN(purchasePrice) && purchasePrice < 0) {
                        alert('Purchase price cannot be negative');
                        return;
                    }
                    if (!isNaN(minSellingPrice) && minSellingPrice < 0) {
                        alert('Minimum selling price cannot be negative');
                        return;
                    }
                    if (!isNaN(purchasePrice) && !isNaN(minSellingPrice) && minSellingPrice > 0 && purchasePrice > 0 && minSellingPrice < purchasePrice) {
                        alert('Minimum selling price cannot be less than purchase price');
                        return;
                    }
                    if (!isNaN(minSellingPrice) && minSellingPrice > 0 && price < minSellingPrice) {
                        alert('Suggested price cannot be less than minimum selling price');
                        return;
                    }

                    const productData = {
                        id: parseInt(productId),
                        brand: document.getElementById('editProductBrand').value,
                        model: document.getElementById('editProductModel').value,
                        suggested_price: price,
                        quantity: stock,
                        min_stock: minStock,
                        category_id: categoryId,
                        is_active: isActive,
                        image_url: imageUrl,
                        purchase_price: purchasePrice,
                        min_selling_price: minSellingPrice,
                        description: document.getElementById('editProductDescription').value
                    };

                    try {
                        const response = await fetch('api/products.php', {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(productData)
                        });

                        const result = await response.json();
                        console.log('API Response:', result);
                        console.log('Response status:', response.status);

                        if (result.success) {
                            alert('Product updated successfully!');
                            closeEditProductModal();
                            loadInventory();
                            return;
                        } else {
                            alert('Error: ' + (result.message || 'Unknown error'));
                        }
                    } catch (error) {
                        console.error('Error updating product:', error);
                        console.error('Full error details:', {
                            message: error.message,
                            stack: error.stack,
                            productData: productData
                        });
                        alert('Error updating product: ' + error.message + '. Check console for details.');
                    }
                });
            }

            // Handle add income form submission
            const addIncomeForm = document.getElementById('addIncomeForm');
            if (addIncomeForm) {
                addIncomeForm.addEventListener('submit', async function (e) {
                    e.preventDefault();

                    const price = parseFloat(document.getElementById('income-price').value);
                    const description = document.getElementById('income-description').value.trim();

                    // Validate input
                    if (price <= 0) {
                        alert('Price must be greater than 0');
                        return;
                    }

                    if (!description) {
                        alert('Description is required');
                        return;
                    }

                    const incomeData = {
                        price: price,
                        description: description
                    };

                    try {
                        const response = await fetch('api/income.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(incomeData)
                        });

                        const result = await response.json();

                        if (result.success) {
                            alert('Income entry added successfully!');
                            addIncomeForm.reset();
                            loadIncome(); // Refresh the income list
                        } else {
                            alert('Failed to add income entry: ' + (result.message || 'Unknown error'));
                        }
                    } catch (error) {
                        console.error('Error adding income entry:', error);
                        alert('Error adding income entry. Please try again.');
                    }
                });
            }

            // Handle add payment form submission
            const addPaymentForm = document.getElementById('addPaymentForm');
            if (addPaymentForm) {
                addPaymentForm.addEventListener('submit', async function (e) {
                    e.preventDefault();

                    const price = parseFloat(document.getElementById('payment-price').value);
                    const description = document.getElementById('payment-description').value.trim();

                    // Validate input
                    if (price <= 0) {
                        alert('Amount must be greater than 0');
                        return;
                    }

                    if (!description) {
                        alert('Description is required');
                        return;
                    }

                    const paymentData = {
                        price: price,
                        description: description
                    };

                    try {
                        const response = await fetch('api/payment.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(paymentData)
                        });

                        const result = await response.json();

                        if (result.success) {
                            alert('Payment entry added successfully!');
                            addPaymentForm.reset();
                            loadPayment(); // Refresh the payment list
                            loadReports(); // Refresh profit calculations
                        } else {
                            alert('Failed to add payment entry: ' + (result.message || 'Unknown error'));
                        }
                    } catch (error) {
                        console.error('Error adding payment entry:', error);
                        alert('Error adding payment entry. Please try again.');
                    }
                });
            }
        });

        async function loadProducts() {
            try {
                const response = await fetch('api/products.php');
                const result = await response.json();
                if (result.success) {
                    products = result.data;
                    allProducts = result.data; // Include all products for search functionality
                    console.log('Products loaded successfully:', products.length, 'products');
                }
            } catch (error) {
                console.error('Error loading products:', error);
            }
        }

        // Load suppliers for dropdown
        async function loadSuppliers() {
            try {
                const response = await fetch('api/suppliers.php');
                const result = await response.json();
                if (result.success) {
                    allSuppliers = result.suppliers;
                    populateSupplierSelect();
                }
            } catch (error) {
                console.error('Error loading suppliers:', error);
            }
        }

        function populateSupplierSelect() {
            const select = document.getElementById('supplier-select');
            if (select) {
                select.innerHTML = '<option value="">Select Supplier</option>';
                allSuppliers.forEach(supplier => {
                    select.innerHTML += `<option value="${supplier.id}">${supplier.name}</option>`;
                });
            }
        }

        // Global variables for product search
        let selectedProduct = null;
        let allProducts = [];
        let paymentRowCount = 1;

        // Format currency to hide .00 for whole numbers
        function formatCurrency(amount) {
            const num = parseFloat(amount || 0);
            return num.toLocaleString('en-US', {
                minimumFractionDigits: num % 1 === 0 ? 0 : 2,
                maximumFractionDigits: 2
            });
        }

        function addPaymentRow() {
            const container = document.getElementById('splitPaymentContainer');
            const newRow = document.createElement('div');
            newRow.className = 'payment-row-modern';
            newRow.setAttribute('data-payment-row', paymentRowCount);
            
            const methodText = typeof langManager !== 'undefined' ? langManager.translate('sales', 'method') : 'Method';
            const cashText = typeof langManager !== 'undefined' ? langManager.translate('sales', 'cash') : 'Cash';
            const cardText = typeof langManager !== 'undefined' ? langManager.translate('sales', 'card') : 'Visa';
            const amountPlaceholder = typeof langManager !== 'undefined' ? langManager.translate('sales', 'amount') : 'Amount';
            const refPlaceholder = typeof langManager !== 'undefined' ? langManager.translate('sales', 'reference') : 'Ref (optional)';

            newRow.innerHTML = `
                <select class="modern-input payment-method-select" onchange="updatePaymentTotals()">
                    <option value="" data-translate="sales.method">${methodText}</option>
                    <option value="Cash" data-translate="sales.cash">${cashText}</option>
                    <option value="Visa" data-translate="sales.card">${cardText}</option>
                    <option value="Instapay">Instapay</option>
                    <option value="Installment">Installment</option>
                </select>
                <input type="number" class="modern-input payment-amount" data-translate-placeholder="sales.amount" placeholder="${amountPlaceholder}" step="0.01" min="0" oninput="updatePaymentTotals()">
                <input type="text" class="modern-input payment-reference" data-translate-placeholder="sales.reference" placeholder="${refPlaceholder}">
                <button class="btn-modern btn-danger-modern" onclick="removePaymentRow(${paymentRowCount})" style="padding: 8px 12px;">×</button>
            `;
            
            container.appendChild(newRow);
            
            // Re-apply translations for the new row
            if (typeof langManager !== 'undefined') {
                langManager.applyLanguage(langManager.currentLang);
            }
            
            paymentRowCount++;
            updatePaymentTotals();
        }

        function removePaymentRow(rowId) {
            const row = document.querySelector(`[data-payment-row="${rowId}"]`);
            if (row) {
                row.remove();
                updatePaymentTotals();
            }
        }

// Price Formatting with Comma Separators
function formatPriceInput(input) {
    let value = input.value.replace(/,/g, '');
    if (isNaN(value) || value === '') {
        input.value = '';
        document.getElementById('selling-price').value = '';
        return;
    }
    
    // Store raw numeric value
    document.getElementById('selling-price').value = value;
    
    // Format display value
    let parts = value.split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    input.value = parts.join('.');
}

// Focus barcode scanner input
function startBarcodeScan() {
    const input = document.getElementById('product-search');
    if (input) {
        input.focus();
        input.value = '';
        // Show subtle feedback that scanner is ready
        input.style.boxShadow = '0 0 0 4px rgba(0, 86, 179, 0.2)';
        setTimeout(() => input.style.boxShadow = '', 1000);
    }
}

        function updatePaymentTotals() {
            const totalAmount = parseFloat(currentReceipt.total) || 0;
            let totalPaid = 0;
            
            // Look for the new modern class name
            document.querySelectorAll('.payment-row-modern').forEach(row => {
                const amountInput = row.querySelector('.payment-amount');
                const amount = parseFloat(amountInput.value) || 0;
                const method = row.querySelector('.payment-method-select').value;
                
                if (method && amount > 0) {
                    totalPaid += amount;
                }
            });
            
            const remaining = totalAmount - totalPaid;
            
            document.getElementById('totalPaid').textContent = formatCurrency(totalPaid);
            document.getElementById('remainingAmount').textContent = formatCurrency(remaining);
            
            // Update remaining amount color
            const remainingElement = document.getElementById('remainingAmount');
            if (Math.abs(remaining) < 0.01) {
                remainingElement.style.color = 'var(--primary-green)';
            } else if (remaining > 0) {
                remainingElement.style.color = 'var(--primary-red)';
            } else {
                remainingElement.style.color = 'var(--secondary-yellow)';
            }
            
            // Enable complete button ONLY if remaining balance is exactly 0
            const completeBtn = document.getElementById('complete-btn');
            if (completeBtn) {
                const isBalanced = Math.abs(remaining) < 0.01;
                const hasItems = currentReceipt.items.length > 0;
                completeBtn.disabled = !hasItems || !isBalanced;
                
                // Visual feedback for the button state
                if (completeBtn.disabled) {
                    completeBtn.style.opacity = '0.5';
                    completeBtn.style.cursor = 'not-allowed';
                    completeBtn.title = !hasItems ? 'Add items first' : 'Remaining balance must be 0.00';
                } else {
                    completeBtn.style.opacity = '1';
                    completeBtn.style.cursor = 'pointer';
                    completeBtn.title = 'Complete Sale';
                }
            }
        }

        function getPaymentSplits() {
            const splits = [];
            
            document.querySelectorAll('.payment-row-modern').forEach(row => {
                const method = row.querySelector('.payment-method-select').value;
                const amount = parseFloat(row.querySelector('.payment-amount').value) || 0;
                const reference = row.querySelector('.payment-reference').value || null;
                
                if (method && amount > 0) {
                    splits.push({
                        payment_method: method,
                        amount: amount,
                        reference_number: reference
                    });
                }
            });
            
            return splits;
        }

        // Product search functions with barcode scanning support
        let scanTimeout = null;
        let isScanning = false;

        function handleProductInput(value) {
            // Detect potential barcode scanning (rapid input of numbers)
            if (!allProducts || allProducts.length === 0) {
                return; // Exit if products not loaded yet
            }
            
            if (/^\d+$/.test(value) && value.length >= 8) {
                if (!isScanning) {
                    isScanning = true;
                    const feedback = document.getElementById('scan-feedback');
                    const indicator = document.getElementById('scan-indicator');
                    if (feedback) feedback.style.display = 'block';
                    if (indicator) indicator.textContent = '🔄';
                }
                
                // Clear existing timeout
                if (scanTimeout) {
                    clearTimeout(scanTimeout);
                }
                
                // Set timeout to process barcode after scanning stops
                scanTimeout = setTimeout(() => {
                    processBarcode(value);
                    isScanning = false;
                    document.getElementById('scan-feedback').style.display = 'none';
                    document.getElementById('scan-indicator').textContent = '📷';
                }, 500);
            } else {
                // Regular typing search
                if (scanTimeout) {
                    clearTimeout(scanTimeout);
                }
                isScanning = false;
                const feedback = document.getElementById('scan-feedback');
                const indicator = document.getElementById('scan-indicator');
                if (feedback) feedback.style.display = 'none';
                if (indicator) indicator.textContent = '📷';
            }
        }

        function processBarcode(barcode) {
            // First try exact barcode match
            let product = allProducts.find(p => p.barcode === barcode || p.imei === barcode);
            
            // If no exact match, try product code match
            if (!product) {
                product = allProducts.find(p => p.code === barcode);
            }
            
            // If still no match, try partial match
            if (!product) {
                product = allProducts.find(p => 
                    p.code.includes(barcode) || 
                    (p.barcode && p.barcode.includes(barcode)) ||
                    (p.imei && p.imei.includes(barcode))
                );
            }
            
            if (product) {
                selectProduct(product);
                // Clear both search inputs after successful scan
                const productSearchInput = document.getElementById('product-search');
                const productSearchEditInput = document.getElementById('product-search-edit');
                if (productSearchInput) {
                    productSearchInput.value = '';
                }
                if (productSearchEditInput) {
                    productSearchEditInput.value = '';
                }
                // Show success feedback
                showScanFeedback('✅ Product found: ' + product.brand + ' ' + product.model, 'success');
            } else {
                // Show error feedback
                showScanFeedback('❌ No product found for barcode: ' + barcode, 'error');
                // Clear both search inputs for next scan
                setTimeout(() => {
                    const productSearchInput = document.getElementById('product-search');
                    const productSearchEditInput = document.getElementById('product-search-edit');
                    if (productSearchInput) {
                        productSearchInput.value = '';
                    }
                    if (productSearchEditInput) {
                        productSearchEditInput.value = '';
                    }
                }, 2000);
            }
        }

        function showScanFeedback(message, type) {
            // Show feedback in both Create Receipt and Add Product tabs
            const feedback = document.getElementById('scan-feedback');
            const feedbackEdit = document.getElementById('scan-feedback-edit');
            
            if (feedback) {
                feedback.textContent = message;
                feedback.style.display = 'block';
                feedback.style.color = type === 'success' ? '#28a745' : '#dc3545';
            }
            if (feedbackEdit) {
                feedbackEdit.textContent = message;
                feedbackEdit.style.display = 'block';
                feedbackEdit.style.color = type === 'success' ? '#28a745' : '#dc3545';
            }
            
            setTimeout(() => {
                if (feedback) {
                    feedback.style.display = 'none';
                }
                if (feedbackEdit) {
                    feedbackEdit.style.display = 'none';
                }
            }, 3000);
        }

        function searchProducts(searchTerm) {
            console.log('Search called with:', searchTerm);
            console.log('All products count:', allProducts.length);
            
            // Get the appropriate results div based on which input is being used
            const productSearchInput = document.getElementById('product-search');
            const productSearchEditInput = document.getElementById('product-search-edit');
            
            // Determine which results div to use. 
            // In the redesign, we might have duplicate IDs if we're not careful, 
            // so we look for the one inside the active element's container if possible.
            let resultsDiv = document.getElementById('product-search-results');
            
            // If we are in the main receipt search and there's a specific results div for it
            if (document.activeElement === productSearchInput) {
                // If we have a specific receipt results div, use it
                const receiptResults = document.getElementById('receipt-product-search-results');
                if (receiptResults) resultsDiv = receiptResults;
            }

            if (!resultsDiv) {
                console.error('Results container not found');
                return;
            }

            const isMainSearch = productSearchInput && productSearchInput.value === searchTerm;
            const isEditSearch = productSearchEditInput && productSearchEditInput.value === searchTerm;

            if (!searchTerm.trim()) {
                resultsDiv.style.display = 'none';
                return;
            }

            const searchLower = searchTerm.toLowerCase();
            const filteredProducts = allProducts.filter(product => {
                return product.code.toLowerCase().includes(searchLower) ||
                    product.brand.toLowerCase().includes(searchLower) ||
                    product.model.toLowerCase().includes(searchLower) ||
                    `${product.brand} ${product.model}`.toLowerCase().includes(searchLower) ||
                    (product.barcode && product.barcode.includes(searchLower)) ||
                    (product.imei && product.imei.includes(searchLower));
            });

            if (filteredProducts.length === 0) {
                resultsDiv.innerHTML = '<div style="padding: 15px; color: var(--gray-500); text-align: center;">No products found</div>';
            } else {
                resultsDiv.innerHTML = filteredProducts.map(product => {
                    const stock = product.available_stock || 0;
                    let stockClass = 'stock-in';
                    if (stock <= 0) stockClass = 'stock-out';
                    else if (stock <= 5) stockClass = 'stock-low';
                    
                    const imgUrl = product.image_url || 'components/css/logo.jpeg';
                    
                    return `
                        <div class="product-item-modern" onclick="selectProduct(${JSON.stringify(product).replace(/"/g, '&quot;')})">
                            <img src="${imgUrl}" class="product-img" onerror="this.src='components/css/logo.jpeg'">
                            <div class="info">
                                <span class="name">${product.brand} ${product.model}</span>
                                <span class="code">${product.code}</span>
                            </div>
                            <div style="text-align: right;">
                                <div class="stock-badge ${stockClass}">${stock} IN STOCK</div>
                                <div class="price-tag">${formatCurrency(product.suggested_price || product.price || 0)} EGP</div>
                            </div>
                        </div>
                    `;
                }).join('');
            }
            
            resultsDiv.style.display = 'block';
        }

        function selectProduct(productOrId) {
            // Handle both product object and product ID
            if (typeof productOrId === 'object') {
                selectedProduct = productOrId;
            } else {
                selectedProduct = allProducts.find(p => p.id === productOrId);
            }
            
            if (selectedProduct) {
                document.getElementById('selected-product').innerHTML = `
                    <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                        <span><strong>${selectedProduct.brand} ${selectedProduct.model}</strong> (${selectedProduct.code})</span>
                        <span style="color: var(--primary-green); font-weight: 700;">Stock: ${selectedProduct.available_stock || 0}</span>
                    </div>
                `;

                // Set focus back to search input for next potential item
                document.getElementById('product-search').focus();

                // Set the suggested price in the price input
                const priceValue = (selectedProduct.suggested_price || selectedProduct.price || selectedProduct.min_selling_price || 0).toFixed(2);
                document.getElementById('selling-price').value = priceValue;
                const displayInput = document.getElementById('selling-price-display');
                displayInput.value = priceValue;
                formatPriceInput(displayInput); // Trigger comma formatting

                document.getElementById('add-product-btn').disabled = false;
                
                // Hide search results (check both IDs to be safe)
                const resultsDiv = document.getElementById('receipt-product-search-results');
                const resultsEditDiv = document.getElementById('product-search-results');
                if (resultsDiv) resultsDiv.style.display = 'none';
                if (resultsEditDiv) resultsEditDiv.style.display = 'none';
                
                // Clear search input text but keep focus
                document.getElementById('product-search').value = '';
            }
        }

        function selectFirstProduct() {
            // Check both potential results containers
            let resultsDiv = document.getElementById('receipt-product-search-results');
            // Use the one that is currently visible
            if (!resultsDiv || resultsDiv.style.display === 'none') {
                resultsDiv = document.getElementById('product-search-results');
            }
            
            if (resultsDiv) {
                const firstResult = resultsDiv.querySelector('.product-item-modern, div[onclick]');
                if (firstResult) {
                    firstResult.click();
                }
            }
        }

        // Test search function for admin dashboard
        function testSearch() {
            console.log('=== SEARCH TEST ===');
            console.log('All products:', allProducts);
            console.log('Products length:', allProducts.length);
            console.log('Product search input:', document.getElementById('product-search'));
            
            if (allProducts && allProducts.length > 0) {
                console.log('Products loaded, testing search...');
                searchProducts('test');
            } else {
                console.log('Products not loaded yet, calling loadProducts...');
                loadProducts().then(() => {
                    console.log('Products loaded after manual call:', allProducts.length);
                });
            }
        }

        function showItemSelectionModal() {
            if (!selectedProduct) {
                alert('Please select a product first');
                return;
            }

            const quantityInput = document.getElementById('quantity');
            const sellingPriceInput = document.getElementById('selling-price');
            
            if (!sellingPriceInput.value) {
                alert('Please enter a selling price');
                return;
            }

            // Show product info
            document.getElementById('selected-product-info').innerHTML = `
                <strong>${selectedProduct.brand} ${selectedProduct.model}</strong><br>
                Code: ${selectedProduct.code}<br>
                Available Stock: ${selectedProduct.available_stock}
            `;

            // Set selling price and quantity
            document.getElementById('modal-selling-price').value = sellingPriceInput.value;
            document.getElementById('modal-quantity').value = quantityInput.value;
            document.getElementById('modal-quantity').max = selectedProduct.available_stock;

            // Load available items
            loadAvailableItems();

            // Show modal
            document.getElementById('item-selection-modal').style.display = 'block';
        }

        function closeItemSelectionModal() {
            document.getElementById('item-selection-modal').style.display = 'none';
        }

        function updateItemSelection() {
            const quantity = parseInt(document.getElementById('modal-quantity').value);
            const checkboxes = document.querySelectorAll('.item-checkbox');
            const checkedBoxes = document.querySelectorAll('.item-checkbox:checked');
            
            // Block selection if trying to exceed quantity
            if (checkedBoxes.length > quantity) {
                // Find the last checked box and uncheck it
                const allChecked = Array.from(checkboxes).filter(cb => cb.checked);
                const lastChecked = allChecked[allChecked.length - 1];
                if (lastChecked) {
                    lastChecked.checked = false;
                    
                    // Show warning
                    const addButton = document.querySelector('button[onclick="addSelectedItemsToReceipt()"]');
                    const originalText = addButton.textContent;
                    addButton.textContent = `Cannot select more than ${quantity} items!`;
                    addButton.style.background = 'var(--danger-color)';
                    
                    setTimeout(() => {
                        addButton.textContent = originalText;
                        addButton.style.background = '';
                    }, 2000);
                }
            }
            
            // Update "Select All" checkbox state
            const selectAllCheckbox = document.getElementById('select-all-items');
            selectAllCheckbox.checked = (checkedBoxes.length === quantity && quantity === checkboxes.length);
            
            // Update button text
            const addButton = document.getElementById('modal-add-btn');
            if (addButton) {
                const currentChecked = document.querySelectorAll('.item-checkbox:checked').length;
                addButton.textContent = `Add Selected Items (${currentChecked}/${quantity})`;
                
                // Enable/disable button based on exact match
                if (currentChecked === quantity) {
                    addButton.disabled = false;
                    addButton.style.opacity = '1';
                } else {
                    addButton.disabled = true;
                    addButton.style.opacity = '0.6';
                }
            }
        }

        async function loadAvailableItems() {
            try {
                console.log('Loading items for product:', selectedProduct.id);
                const response = await fetch(`api/stock_items.php?product_id=${selectedProduct.id}`);
                const result = await response.json();
                
                console.log('API response:', result);
                
                if (result.success) {
            displayAvailableItems(result.data);
        } else {
            document.getElementById('available-items-list').innerHTML = 
                '<p style="color: red;">Error loading items: ' + result.message + '</p>';
        }
    } catch (error) {
        console.error('Error loading items:', error);
        document.getElementById('available-items-list').innerHTML = 
            '<p style="color: red;">Error loading items</p>';
    }
}

        function displayAvailableItems(items) {
            console.log('Displaying items:', items);
            const container = document.getElementById('available-items-list');
            const quantity = parseInt(document.getElementById('modal-quantity').value);
            
            // Update available stock display in product info
            document.getElementById('selected-product-info').innerHTML = `
                <div>
                    <div style="font-weight: 800; font-size: 1.1em; color: var(--dark-blue);">${selectedProduct.brand} ${selectedProduct.model}</div>
                    <div style="font-size: 0.9em; color: var(--gray-600); font-family: var(--font-family-mono); margin-top: 4px;">Code: ${selectedProduct.code}</div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 0.8em; color: var(--gray-500); text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Available Stock</div>
                    <div style="font-size: 1.5em; font-weight: 800; color: var(--primary-blue); line-height: 1;">${items.length} <span style="font-size: 0.5em; font-weight: 600;">UNITS</span></div>
                </div>
            `;

            if (quantity > items.length) {
                container.innerHTML = `<div style="padding: 20px; text-align: center; background: #fff5f5; border-radius: 10px; color: #e53e3e; border: 1px solid #feb2b2;">
                    <span style="font-size: 2em; display: block; margin-bottom: 10px;">⚠️</span>
                    Only <strong>${items.length}</strong> items available, but you requested <strong>${quantity}</strong>.
                </div>`;
                return;
            }

            // Display items with dynamic selection label
            container.innerHTML = `
                <div style="margin-bottom: 15px; padding: 12px 15px; background: rgba(0, 86, 179, 0.05); border-radius: 10px; color: var(--primary-blue); font-size: 0.9em; display: flex; justify-content: space-between; align-items: center; border: 1px solid rgba(0, 86, 179, 0.1);">
                    <span id="modal-selection-count" style="font-weight: 600;">Select First ${quantity} items (of ${items.length} available)</span>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 700; background: white; padding: 5px 12px; border-radius: 8px; box-shadow: var(--shadow-xs);">
                        <input type="checkbox" id="select-all-items" onchange="toggleSelectAllItems(this)" style="width: 16px; height: 16px; accent-color: var(--primary-blue);"> 
                        ${typeof langManager !== 'undefined' ? langManager.translate('common', 'selectAll') : 'Select All'}
                    </label>
                </div>
                <div style="max-height: 300px; overflow-y: auto; padding-right: 5px; margin-right: -5px;">
                    ${items.map((item, index) => `
                        <div class="item-checkbox-row" style="${index < quantity ? 'background: rgba(0, 86, 179, 0.03); border-color: var(--primary-blue);' : ''}">
                            <label style="display: flex; align-items: center; cursor: pointer; position: relative;">
                                <input type="checkbox" class="item-checkbox" value="${item.id}" data-item='${JSON.stringify(item)}' 
                                       ${index < quantity ? 'checked' : ''} onchange="updateItemSelection()" 
                                       style="width: 20px; height: 20px; accent-color: var(--primary-blue); margin-right: 15px;">
                                <div style="flex-grow: 1;">
                                    <div style="font-weight: 700; color: var(--dark-blue); font-size: 1.1em;">${item.item_code || 'UNIT-' + item.id}</div>
                                    <div style="display: flex; gap: 15px; margin-top: 4px;">
                                        ${item.imei ? `<span style="font-size: 11px; color: var(--gray-600); background: var(--gray-100); padding: 2px 6px; border-radius: 4px;">IMEI: ${item.imei}</span>` : ''}
                                        ${item.serial_number ? `<span style="font-size: 11px; color: var(--gray-600); background: var(--gray-100); padding: 2px 6px; border-radius: 4px;">SN: ${item.serial_number}</span>` : ''}
                                        ${item.color ? `<span style="font-size: 11px; color: var(--gray-600); background: var(--gray-100); padding: 2px 6px; border-radius: 4px; border-left: 3px solid ${item.color.toLowerCase()};">Color: ${item.color}</span>` : ''}
                                    </div>
                                </div>
                            </label>
                        </div>
                    `).join('')}
                </div>
            `;
            
            updateItemSelection();
        }

        function toggleAllItems() {
            const selectAll = document.getElementById('select-all-items');
            const quantity = parseInt(document.getElementById('modal-quantity').value);
            const checkboxes = document.querySelectorAll('.item-checkbox');
            
            checkboxes.forEach((checkbox, index) => {
                checkbox.checked = selectAll.checked && index < quantity;
            });
            
            updateItemSelection();
        }

        function addSelectedItemsToReceipt() {
            const selectedCheckboxes = document.querySelectorAll('.item-checkbox:checked');
            const quantity = parseInt(document.getElementById('modal-quantity').value);
            
            if (selectedCheckboxes.length !== quantity) {
                alert(`Please select exactly ${quantity} items (currently selected: ${selectedCheckboxes.length})`);
                return;
            }

            const sellingPrice = parseFloat(document.getElementById('modal-selling-price').value);
            
            selectedCheckboxes.forEach(checkbox => {
                const item = JSON.parse(checkbox.dataset.item);
                
                currentReceipt.items.push({
                    productId: selectedProduct.id,
                    productItemId: item.id,
                    itemCode: item.item_code,
                    imei: item.imei,
                    serialNumber: item.serial_number,
                    name: `${selectedProduct.brand} ${selectedProduct.model}`,
                    price: sellingPrice,
                    quantity: 1,
                    total: sellingPrice
                });
            });

            updateReceiptDisplay();
            closeItemSelectionModal();
            
            // Clear selection
            selectedProduct = null;
            const noProductText = typeof langManager !== 'undefined' ? langManager.translate('sales', 'noProductSelected') : 'No product selected';
            document.getElementById('selected-product').innerHTML = `<div style="color: #666;" data-translate="sales.noProductSelected">${noProductText}</div>`;
            document.getElementById('add-product-btn').disabled = true;
            document.getElementById('product-search').value = '';
            document.getElementById('selling-price').value = '';
            document.getElementById('quantity').value = 1;
        }

        function addToReceipt() {
            console.log('=== ADMIN ADD TO RECEIPT ===');
            console.log('Selected product:', selectedProduct);
            console.log('Current receipt items:', currentReceipt.items);
            
            const quantityInput = document.getElementById('quantity');
            const sellingPriceInput = document.getElementById('selling-price');
            
            console.log('Quantity input:', quantityInput.value);
            console.log('Selling price input:', sellingPriceInput.value);

            if (!selectedProduct || !quantityInput.value || !sellingPriceInput.value) {
                alert('Please select a product, enter selling price and quantity');
                return;
            }

            const quantity = parseInt(quantityInput.value);
            const sellingPrice = parseFloat(sellingPriceInput.value);
            
            console.log('Parsed quantity:', quantity);
            console.log('Parsed selling price:', sellingPrice);

            if (sellingPrice < selectedProduct.min_selling_price) {
                alert(`Selling price cannot be less than minimum price: ${selectedProduct.min_selling_price.toFixed(2)} EGP `);
                return;
            }

            if (quantity > selectedProduct.available_stock) {
                alert(`Only ${selectedProduct.available_stock} items available`);
                return;
            }

            console.log('All validations passed, adding to receipt...');

            const existingItem = currentReceipt.items.find(item => item.productId === selectedProduct.id);

            if (existingItem) {
                if (existingItem.quantity + quantity <= selectedProduct.available_stock) {
                    existingItem.quantity += quantity;
                    existingItem.price = sellingPrice; // Update price to current selling price
                    existingItem.total = existingItem.quantity * existingItem.price;
                } else {
                    alert('Cannot exceed available stock');
                    return;
                }
            } else {
                currentReceipt.items.push({
                    productId: selectedProduct.id,
                    code: selectedProduct.code,
                    name: `${selectedProduct.brand} ${selectedProduct.model}`,
                    price: sellingPrice,
                    quantity: quantity,
                    total: sellingPrice * quantity
                });
            }

            console.log('Item added, new receipt items:', currentReceipt.items);
            updateReceiptDisplay();
            quantityInput.value = 1;

            // Clear selection
            // Clear selection
            selectedProduct = null;
            const noProductText = typeof langManager !== 'undefined' ? langManager.translate('sales', 'noProductSelected') : 'No product selected';
            document.getElementById('selected-product').innerHTML = `<div style="color: #666;" data-translate="sales.noProductSelected">${noProductText}</div>`;
            document.getElementById('add-product-btn').disabled = true;
            document.getElementById('product-search').value = '';
            document.getElementById('selling-price').value = '';
            document.getElementById('quantity').value = 1;
        }

        function updateReceiptDisplay() {
            console.log('=== UPDATE RECEIPT DISPLAY ===');
            const itemsBody = document.getElementById('receipt-items-body');

            if (currentReceipt.items.length === 0) {
                const noItemsText = typeof langManager !== 'undefined' ? langManager.translate('sales', 'noItemsAdded') : 'No items added yet';
                itemsBody.innerHTML = `
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--gray-400); padding: 40px;" data-translate="sales.noItemsAdded">
                            ${noItemsText}
                        </td>
                    </tr>
                `;
            } else {
                // Group items by product
                const groupedItems = {};
                currentReceipt.items.forEach(item => {
                    const key = `${item.productId}_${item.price}`;
                    if (!groupedItems[key]) {
                        groupedItems[key] = {
                            productId: item.productId,
                            name: item.name,
                            price: item.price,
                            quantity: 0,
                            total: 0,
                            itemCodes: [],
                    imeis: [],
                    serialNumbers: []
                        };
                    }
                    groupedItems[key].quantity += item.quantity;
                    groupedItems[key].total += item.total;
                    if (item.itemCode) groupedItems[key].itemCodes.push(item.itemCode);
                    if (item.imei) groupedItems[key].imeis.push(item.imei);
                    if (item.serialNumber) groupedItems[key].serialNumbers.push(item.serialNumber);
                });

                itemsBody.innerHTML = Object.values(groupedItems).map((group, index) => `
                    <tr>
                        <td>
                            <div style="font-weight: 600;">${group.name}</div>
                            ${group.itemCodes.length > 0 ? `
                                <div style="font-size: 11px; color: var(--gray-500);">
                                    ${group.itemCodes.map(code => `Code: ${code}`).join('<br>')}
                                </div>
                            ` : ''}
                            ${group.imeis.length > 0 ? `
                                <div style="font-size: 11px; color: var(--blue-600);">
                                    ${group.imeis.map(imei => `IMEI: ${imei}`).join('<br>')}
                                </div>
                            ` : ''}
                            ${group.serialNumbers.length > 0 ? `
                                <div style="font-size: 11px; color: var(--blue-600);">
                                    ${group.serialNumbers.map(serial => `Serial: ${serial}`).join('<br>')}
                                </div>
                            ` : ''}
                        </td>
                        <td class="text-right">${formatCurrency(group.price)}</td>
                        <td class="text-right">
                            <span style="font-weight: 600; padding: 5px 10px; background: var(--gray-100); border-radius: 3px;">${group.quantity}</span>
                        </td>
                        <td class="text-right" style="font-weight: 700;">${formatCurrency(group.total)}</td>
                        <td class="text-right">
                            <button onclick="removeFromReceipt(${group.productId})" class="btn-modern btn-danger-modern" style="padding: 4px 8px; font-size: 12px;">×</button>
                        </td>
                    </tr>
                `).join('');
            }

            currentReceipt.total = currentReceipt.items.reduce((sum, item) => sum + item.total, 0);

            document.getElementById('total').textContent = formatCurrency(currentReceipt.total) + ' EGP';
            
            const completeBtn = document.getElementById('complete-btn');
            if (completeBtn) {
                completeBtn.disabled = currentReceipt.items.length === 0;
            }
            
            // Update payment totals
            updatePaymentTotals();
        }

        function updateQuantity(productId, change) {
            const item = currentReceipt.items.find(item => item.productId === productId);
            if (!item) return;

            const newQuantity = item.quantity + change;
            
            // Don't allow quantity less than 1
            if (newQuantity < 1) {
                alert('Quantity cannot be less than 1');
                return;
            }

            // Check if we have enough stock (we need to get the product info)
            const product = allProducts.find(p => p.id === productId);
            if (product && newQuantity > product.available_stock) {
                alert(`Only ${product.available_stock} items available in stock`);
                return;
            }

            // Update the quantity and total
            item.quantity = newQuantity;
            item.total = item.price * item.quantity;
            
            // Refresh the display
            updateReceiptDisplay();
        }

        function clearReceipt() {
            currentReceipt = { items: [], total: 0 };
            selectedProduct = null;
            updateReceiptDisplay();
            document.getElementById('customer-name').value = '';
            document.getElementById('customer-phone').value = '';
            
            // Clear both search inputs
            const productSearchInput = document.getElementById('product-search');
            const productSearchEditInput = document.getElementById('product-search-edit');
            if (productSearchInput) {
                productSearchInput.value = '';
            }
            if (productSearchEditInput) {
                productSearchEditInput.value = '';
            }
            
            const noProductText = typeof langManager !== 'undefined' ? langManager.translate('sales', 'noProductSelected') : 'No product selected';
            document.getElementById('selected-product').innerHTML = `<div style="color: #666;" data-translate="sales.noProductSelected">${noProductText}</div>`;
            document.getElementById('add-product-btn').disabled = true;
            document.getElementById('product-search-results').style.display = 'none';
            document.getElementById('selling-price').value = '';
            document.getElementById('quantity').value = '1';
            
            // Clear payment splits and reset to modern design
            const container = document.getElementById('splitPaymentContainer');
            const methodText = typeof langManager !== 'undefined' ? langManager.translate('sales', 'method') : 'Method';
            const cashText = typeof langManager !== 'undefined' ? langManager.translate('sales', 'cash') : 'Cash';
            const cardText = typeof langManager !== 'undefined' ? langManager.translate('sales', 'card') : 'Visa';
            const amountPlaceholder = typeof langManager !== 'undefined' ? langManager.translate('sales', 'amount') : 'Amount';
            const refPlaceholder = typeof langManager !== 'undefined' ? langManager.translate('sales', 'reference') : 'Ref (optional)';

            container.innerHTML = `
                <div class="payment-row-modern" data-payment-row="0">
                    <select class="modern-input payment-method-select" onchange="updatePaymentTotals()">
                        <option value="" data-translate="sales.method">${methodText}</option>
                        <option value="Cash" data-translate="sales.cash">${cashText}</option>
                        <option value="Visa" data-translate="sales.card">${cardText}</option>
                        <option value="Instapay">Instapay</option>
                        <option value="Installment">Installment</option>
                    </select>
                    <input type="number" class="modern-input payment-amount" data-translate-placeholder="sales.amount" placeholder="${amountPlaceholder}" step="0.01" min="0" oninput="updatePaymentTotals()">
                    <input type="text" class="modern-input payment-reference" data-translate-placeholder="sales.reference" placeholder="${refPlaceholder}">
                    <button class="btn-modern btn-danger-modern" onclick="removePaymentRow(0)" style="display: none; padding: 8px 12px;">×</button>
                </div>
            `;
            
            paymentRowCount = 1;

            // Final sync of translations for safety
            if (typeof langManager !== 'undefined') {
                langManager.applyLanguage(langManager.currentLang);
            }

            updatePaymentTotals();
        }

        function removeFromReceipt(productId) {
            currentReceipt.items = currentReceipt.items.filter(item => item.productId !== productId);
            updateReceiptDisplay();
        }

        // Complete receipt
        async function completeReceipt() {
            const completeBtn = document.getElementById('complete-btn');
            completeBtn.innerHTML = 'Processing...';
            completeBtn.disabled = true;

            try {
                let customerId = null;
                const customerName = document.getElementById('customer-name').value.trim();
                const customerPhone = document.getElementById('customer-phone').value.trim();

                // Validate customer information
                if (!customerName) {
                    alert('Please enter customer name');
                    completeBtn.innerHTML = 'Complete Sale';
                    completeBtn.disabled = false;
                    return;
                }

                if (!customerPhone) {
                    alert('Customer phone is missing');
                    completeBtn.innerHTML = 'Complete Sale';
                    completeBtn.disabled = false;
                    return;
                }

                // Validate phone number format (Egyptian format: 11 digits starting with 01)
                const phoneRegex = /^01[0-2,5]{1}[0-9]{8}$/;
                if (!phoneRegex.test(customerPhone)) {
                    const errorMsg = typeof langManager !== 'undefined' ? 
                        langManager.translate('common', 'invalidPhone') : 
                        'Please enter a valid 11-digit phone number (e.g., 01xxxxxxxxx)';
                    alert(errorMsg);
                    completeBtn.innerHTML = 'Complete Sale';
                    completeBtn.disabled = false;
                    return;
                }

                // Try to add customer, but don't fail if it doesn't work
                if (customerPhone) {
                    try {
                        const customerResponse = await fetch('api/customers.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ name: customerName, phone: customerPhone })
                        });
                        const customerResult = await customerResponse.json();
                        if (customerResult.success) customerId = customerResult.customer_id;
                    } catch (customerError) {
                        console.warn('Customer creation failed, continuing without customer ID:', customerError);
                        // Continue with sale even if customer creation fails
                    }
                }

                // Create sale
                const paymentSplits = getPaymentSplits();
                
                if (paymentSplits.length === 0) {
                    alert('Please add at least one payment method');
                    completeBtn.innerHTML = 'Complete Sale';
                    completeBtn.disabled = false;
                    return;
                }
                
                const saleData = {
                    customer_id: customerId,
                    customer_name: customerName,
                    customer_phone: customerPhone,
                    staff_id: <?php echo isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 'null'; ?>,
                    total_amount: currentReceipt.total,
                    payment_splits: paymentSplits,
                    is_split_payment: paymentSplits.length > 1,
                    staff_name: <?php echo json_encode(isset($_SESSION['name']) ? $_SESSION['name'] : 'Admin'); ?>,
                    staff_username: <?php echo json_encode(isset($_SESSION['username']) ? $_SESSION['username'] : 'admin'); ?>,
                    items: currentReceipt.items.map(item => {
                        const saleItem = {
                            product_id: item.productId,
                            quantity: item.quantity,
                            unit_price: item.price,
                            total_price: item.price * item.quantity
                        };
                        
                        // Add specific item identifiers if available
                        if (item.productItemId) {
                            saleItem.product_item_ids = [item.productItemId];
                        } else if (item.itemCode) {
                            saleItem.item_codes = [item.itemCode];
                        }
                        
                        return saleItem;
                    })
                };

                // Create the sale
                console.log('Sending sale data:', saleData);

                const saleResponse = await fetch('api/sales.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(saleData)
                });

                console.log('Sale response status:', saleResponse.status);

                if (!saleResponse.ok) {
                    let errorText = '';
                    try {
                        errorText = await saleResponse.text();
                        // Try to parse as JSON
                        try {
                            const errorJson = JSON.parse(errorText);
                            alert('Server error: ' + (errorJson.message || errorText));
                        } catch (parseError) {
                            alert('Server error: ' + saleResponse.status + ' - ' + (errorText.substring(0, 200) || 'Unknown error'));
                        }
                    } catch (e) {
                        alert('Server error: ' + saleResponse.status + ' - Failed to read error message');
                    }
                    return;
                }

                const responseText = await saleResponse.text();
                console.log('Raw response:', responseText);

                let saleResult;
                try {
                    // Strip any PHP notices/warnings before the JSON
                    const jsonStart = responseText.indexOf('{');
                    const cleanText = jsonStart >= 0 ? responseText.substring(jsonStart) : responseText;
                    saleResult = JSON.parse(cleanText);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response text:', responseText);
                    // If a receipt number is in the response, the sale was saved — show success
                    const receiptMatch = responseText.match(/RCP-\d{4}-\d+/);
                    if (receiptMatch) {
                        showSuccessModal(receiptMatch[0], currentReceipt.total);
                        clearReceipt();
                        loadProducts();
                    } else {
                        alert('Sale was likely saved. Please refresh to confirm.');
                        clearReceipt();
                        loadProducts();
                    }
                    return;
                }
                console.log('Sale result:', saleResult);

                if (saleResult.success) {
                    showSuccessModal(saleResult.receipt_number, currentReceipt.total);
                    
                    // Auto-print the receipt
                    setTimeout(async () => {
                        try {
                            console.log('Auto-printing receipt for sale:', saleResult.receipt_number);
                            
                            const receiptData = {
                                id: saleResult.sale_id,
                                receipt_number: saleResult.receipt_number,
                                sale_date: new Date().toISOString(),
                                staff_name: '<?php echo isset($_SESSION['name']) ? $_SESSION['name'] : 'Staff'; ?>',
                                customer_name: document.getElementById('customer-name').value || 'Walk-in Customer',
                                payment_method: 'Cash', // Will be updated from API
                                total_amount: currentReceipt.total,
                                items: currentReceipt.items.map(item => ({
                                    product_brand: item.name.split(' ')[0],
                                    product_model: item.name.split(' ').slice(1).join(' '),
                                    product_code: item.code,
                                    quantity: item.quantity,
                                    unit_price: item.price,
                                    total_price: item.price * item.quantity
                                }))
                            };
                            
                            const printContent = generatePrintableReceipt(receiptData);
                            
                            // Create print dialog with receipt preview
                            const printWindow = window.open('', '_blank', 'width=400,height=600');
                            if (printWindow) {
                                printWindow.document.write(`
                                    <html>
                                        <head>
                                            <title>🧾 Receipt Preview - ${saleResult.receipt_number}</title>
                                            <style>
                                                body { font-family: Arial, sans-serif; padding: 20px; }
                                                .preview-header { text-align: center; margin-bottom: 20px; }
                                                .receipt-preview { 
                                                    border: 2px solid #ddd; 
                                                    padding: 10px; 
                                                    margin: 20px 0; 
                                                    background: white;
                                                    transform: scale(0.8);
                                                    transform-origin: top center;
                                                }
                                                .print-btn { 
                                                    background: #28a745; color: white; padding: 12px 24px; 
                                                    border: none; border-radius: 5px; cursor: pointer; 
                                                    margin: 10px; font-size: 16px; font-weight: bold;
                                                }
                                                .cancel-btn { 
                                                    background: #6c757d; color: white; padding: 12px 24px; 
                                                    border: none; border-radius: 5px; cursor: pointer; 
                                                    margin: 10px; font-size: 16px;
                                                }
                                                .button-group { text-align: center; margin: 20px 0; }
                                            </style>
                                        </head>
                                        <body>
                                            <div class="preview-header">
                                                <h2>🧾 Receipt Ready to Print</h2>
                                                <p><strong>Receipt Number:</strong> ${saleResult.receipt_number}</p>
                                                <p><strong>Total Amount:</strong> ${currentReceipt.total.toFixed(2)} EGP</p>
                                            </div>
                                            
                                            <div class="receipt-preview">
                                                ${printContent}
                                            </div>
                                            
                                            <div class="button-group">
                                                <button class="print-btn" onclick="window.print(); window.close();">🖨️ Print Receipt</button>
                                                <button class="cancel-btn" onclick="window.close();">❌ Cancel</button>
                                            </div>
                                        </body>
                                    </html>
                                `);
                                printWindow.document.close();
                            } else {
                                await printReceipt(saleResult.sale_id);
                            }
                            
                            // Show success message and reset form after printing
                            setTimeout(() => {
                                showSaleCompleteMessage();
                            }, 1000);
                            
                        } catch (printError) {
                            console.warn('Auto-print failed:', printError);
                            await printReceipt(saleResult.sale_id);
                        }
                    }, 500);
                    
                    // DO NOT clearReceipt here! Let the modal close button handle it.
                } else {
                    alert('Failed to complete sale: ' + saleResult.message);
                }
            } catch (error) {
                console.error('Error completing sale:', error);
                alert('Error completing sale: ' + error.message);
            } finally {
                const completeBtn = document.getElementById('complete-btn');
                if (completeBtn) {
                    const completeText = typeof langManager !== 'undefined' ? langManager.translate('sales', 'completeSale') : 'Complete Sale';
                    completeBtn.innerHTML = `✅ ${completeText}`;
                    updatePaymentTotals(); // Re-validate button state
                }
            }
        }

        // Modern Success Modal Logic
        function showSuccessModal(receiptNumber, total) {
            const overlay = document.getElementById('successModalOverlay');
            const receiptEl = document.getElementById('successReceiptNumber');
            const totalEl = document.getElementById('successTotalAmount');
            
            if (receiptEl) receiptEl.textContent = receiptNumber;
            if (totalEl) totalEl.textContent = formatCurrency(total) + ' EGP';
            
            // Sync translations for the modal
            if (typeof langManager !== 'undefined') {
                langManager.applyLanguage(langManager.currentLang);
            }

            if (overlay) overlay.style.display = 'flex';
        }

        function closeSuccessModal() {
            const overlay = document.getElementById('successModalOverlay');
            if (overlay) overlay.style.display = 'none';
            
            // Clear the receipt and reload data ONLY when modal is CLOSED
            clearReceipt();
            loadProducts();
            loadInventory();
            loadSales();
            loadReports();
        }

        function showSaleCompleteMessage() {
            // Show success notification
            const successDiv = document.createElement('div');
            successDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #28a745;
                color: white;
                padding: 15px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 10000;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 10px;
                animation: slideIn 0.3s ease-out;
            `;
            successDiv.innerHTML = `
                <span style="font-size: 20px;">✅</span>
                <span>Sale completed successfully! Ready for next sale.</span>
            `;
            
            // Add animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOut {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
            
            document.body.appendChild(successDiv);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                successDiv.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => {
                    if (successDiv.parentNode) {
                        successDiv.parentNode.removeChild(successDiv);
                    }
                }, 300);
            }, 3000);
            
            // Reset the form for next sale
            setTimeout(() => {
                clearReceipt();
                loadProducts();
                loadInventory();
                loadSales();
                loadReports();
                
                // Focus on product search for next sale
                const productSearch = document.getElementById('product-search');
                if (productSearch) {
                    productSearch.focus();
                }
            }, 1000);
        }

        async function loadInventory() {
            try {
                const response = await fetch('api/products.php');
                const result = await response.json();
                if (result.success) {
                    allInventoryProducts = result.data; // Store all products for filtering

                    // Clear search filter to show all products
                    const searchInput = document.getElementById('inventory-search');
                    if (searchInput) {
                        searchInput.value = '';
                    }

                    displayInventory(result.data);
                    displayInventoryStats(result.data);

                    // Initialize search results count
                    const resultsCountDiv = document.getElementById('search-results-count');
                    if (resultsCountDiv) {
                        resultsCountDiv.textContent = `Showing all ${result.data.length} products`;
                        resultsCountDiv.style.color = '#666';
                    }
                }
            } catch (error) {
                console.error('Error loading inventory:', error);
            }
        }

        async function displayInventory(products) {
            const tbody = document.getElementById('inventory-tbody');
            if (!tbody) return;
            
            tbody.innerHTML = products.map(product => {
                const isOutOfStock = product.available_stock === 0;
                const isLowStock = product.min_stock !== null && product.available_stock > 0 && product.available_stock <= product.min_stock;
                
                let stockStatusHtml = '';
                if (isOutOfStock) {
                    stockStatusHtml = `<span class="inv-badge badge-danger">🚫 Out of Stock (0)</span>`;
                } else if (isLowStock) {
                    stockStatusHtml = `<span class="inv-badge badge-warning">⚠️ Low Stock (${product.available_stock})</span>`;
                } else {
                    stockStatusHtml = `<span class="inv-badge badge-success">✅ In Stock (${product.available_stock})</span>`;
                }
                
                const serializationBadge = `
                    <div class="inv-badge ${product.serialized_count === product.total_stock ? 'badge-success' : 'badge-warning'}" 
                         style="font-size: 11px; margin-top: 5px;">
                        🔢 ${product.serialized_count} / ${product.total_stock} Serialized
                    </div>
                `;

                // Product Image Logic
                const productImage = product.image_url ? 
                    `<img src="${product.image_url}" style="width: 40px; height: 40px; border-radius: 8px; object-fit: cover; border: 1px solid var(--inv-border); margin-right: 10px;">` :
                    `<div style="width: 40px; height: 40px; border-radius: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 20px; border: 1px solid var(--inv-border); margin-right: 10px;">📦</div>`;

                // Color Badge Logic
                const colorHex = product.color_hex || '#e2e8f0';
                const colorBadge = `
                    <div class="inv-color-badge" title="Color: ${product.color || 'N/A'}">
                        <span class="color-dot" style="background-color: ${product.color || 'transparent'};"></span>
                        <span>${product.color || 'N/A'}</span>
                    </div>
                `;

                return `
                <tr>
                    <td class="num-col" style="font-weight: 700; color: var(--inv-primary); border-left: 3px solid var(--inv-primary); padding-left: 15px;">
                        <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 4px;">
                            <span>${product.code}</span>
                            ${serializationBadge}
                        </div>
                    </td>
                    <td style="font-weight: 600; color: #0f172a;">
                        <div style="display: flex; align-items: center;">
                            ${productImage}
                            <span>${product.brand} ${product.model}</span>
                        </div>
                    </td>
                    <td><span class="inv-badge badge-info" style="font-size: 10px;">${product.category_name}</span></td>
                    <td style="font-size: 13px; color: #475569;">${product.brand}</td>
                    <td style="font-size: 13px; color: #475569;">${product.model}</td>
                    <td>${colorBadge}</td>
                    <td style="font-size: 12px; font-weight: 500; color: #64748b;">${product.supplier_name || '<span style="color: #cbd5e1;">N/A</span>'}</td>
                    <td class="num-col" style="color: #64748b; font-size: 12px;">${(product.purchase_price || 0).toLocaleString()}</td>
                    <td class="num-col" style="font-weight: 700; color: #0f172a;">${(product.suggested_price || 0).toLocaleString()} <span style="font-size: 10px; font-weight: 400;">EGP</span></td>
                    <td class="num-col" style="color: #ef4444; font-size: 12px;">${(product.min_selling_price || 0).toLocaleString()}</td>
                    <td class="num-col" style="font-weight: 700; color: #0f172a;">${product.available_stock}</td>
                    <td class="num-col" style="color: #94a3b8; font-size: 12px;">${product.min_stock || 0}</td>
                    <td>
                        <div style="margin-bottom: 4px;">${stockStatusHtml}</div>
                    </td>
                    <td>
                        <div class="inv-actions" style="justify-content: flex-end;">
                            <button class="inv-btn inv-btn-primary" onclick="viewUnits(${product.id})" title="View Items">👁️ View</button>
                            <button class="inv-btn" onclick="editProduct(${product.id})" title="Edit Basic Info">✏️ Edit</button>
                            <button class="inv-btn" onclick="printProductLabelEnterprise(${product.id})" title="Print Label">🏷️ Label</button>
                        </div>
                    </td>
                </tr>
                `;
            }).join('');
        }

        function displayInventoryStats(products) {
            const totalProducts = products.length;
            const inventoryValue = products.reduce((sum, p) => sum + ((p.purchase_price || 0) * p.available_stock), 0);
            const lowStock = products.filter(p => p.min_stock !== null && p.available_stock > 0 && p.available_stock <= p.min_stock).length;
            const outOfStock = products.filter(p => p.available_stock === 0).length;

            if (document.getElementById('stat-total-products')) {
                document.getElementById('stat-total-products').innerText = totalProducts.toLocaleString();
                document.getElementById('stat-inventory-value').innerText = inventoryValue.toLocaleString() + ' EGP';
                document.getElementById('stat-low-stock').innerText = lowStock.toLocaleString();
                document.getElementById('stat-out-of-stock').innerText = outOfStock.toLocaleString();
            }
        }

        function filterInventoryEnterprise() {
            const searchTerm = document.getElementById('inventory-search').value.toLowerCase();
            const categoryFilter = document.getElementById('filter-category').value;
            const brandFilter = document.getElementById('filter-brand').value;
            const resultsCountDiv = document.getElementById('search-results-count');

            const filtered = allInventoryProducts.filter(product => {
                const matchesSearch = !searchTerm || 
                    product.code.toLowerCase().includes(searchTerm) ||
                    product.brand.toLowerCase().includes(searchTerm) ||
                    product.model.toLowerCase().includes(searchTerm) ||
                    (product.description && product.description.toLowerCase().includes(searchTerm));
                
                const matchesCategory = !categoryFilter || product.category_name === categoryFilter;
                const matchesBrand = !brandFilter || product.brand === brandFilter;

                return matchesSearch && matchesCategory && matchesBrand;
            });

            displayInventory(filtered);
            displayInventoryStats(filtered);

            if (resultsCountDiv) {
                if (filtered.length === 0) {
                    resultsCountDiv.innerHTML = '<span style="color: var(--inv-danger);">❌ No products found matching your criteria.</span>';
                } else {
                    resultsCountDiv.innerHTML = `✅ Found <b>${filtered.length}</b> products.`;
                }
            }
        }

        async function viewUnits(productId) {
            const modal = document.getElementById('unitDetailsModal');
            const tbody = document.getElementById('unit-details-tbody');
            if (!modal || !tbody) return;

            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 40px;">⏳ Loading unit details...</td></tr>';
            modal.style.display = 'flex';

            try {
                const response = await fetch(`api/stock_items.php?product_id=${productId}`);
                const res = await response.json();
                
                if (res.success && res.data.length > 0) {
                    tbody.innerHTML = res.data.map(unit => `
                        <tr>
                            <td class="num-col" style="font-weight: 700;">#${unit.id}</td>
                            <td>
                                <input type="text" id="unit-imei-${unit.id}" class="inv-search-input" style="padding: 6px; font-size: 13px; width: 140px;" value="${unit.imei || ''}" placeholder="Enter IMEI">
                            </td>
                            <td>
                                <input type="text" id="unit-serial-${unit.id}" class="inv-search-input" style="padding: 6px; font-size: 13px; width: 140px;" value="${unit.serial_number || ''}" placeholder="Enter Serial">
                            </td>
                            <td style="text-align: center;">
                                <div style="font-family: monospace; font-size: 10px; margin-bottom: 4px;">${unit.item_code}</div>
                                <svg id="unit-barcode-${unit.id}" style="width: 100px; height: 30px;"></svg>
                            </td>
                            <td><span class="inv-badge badge-success">${unit.status}</span></td>
                            <td style="font-size: 12px; color: var(--inv-text-muted);">${new Date(unit.created_at).toLocaleDateString()}</td>
                            <td style="text-align: center;">
                                <button class="inv-btn inv-btn-primary" onclick="saveUnitDetails(${unit.id})" style="padding: 6px 10px;">💾 Save</button>
                            </td>
                        </tr>
                    `).join('');

                    // Generate barcodes for each unit
                    res.data.forEach(unit => {
                        JsBarcode(`#unit-barcode-${unit.id}`, unit.item_code, {
                            format: "CODE128",
                            width: 1.5,
                            height: 30,
                            displayValue: false
                        });
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 40px; color: var(--inv-text-muted);">No units found for this product.</td></tr>';
                }
            } catch (e) {
                console.error(e);
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 40px; color: var(--inv-danger);">Failed to load unit details.</td></tr>';
            }
        }

        async function saveUnitDetails(unitId) {
            const imei = document.getElementById(`unit-imei-${unitId}`).value.trim();
            const serial = document.getElementById(`unit-serial-${unitId}`).value.trim();
            
            try {
                const response = await fetch('api/stock_items.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: unitId,
                        imei: imei,
                        serial_number: serial
                    })
                });

                const res = await response.json();
                if (res.success) {
                    alert('Unit details updated successfully!');
                } else {
                    alert('Failed to update: ' + res.message);
                }
            } catch (e) {
                console.error(e);
                alert('An error occurred while saving.');
            }
        }

        function printProductLabelEnterprise(productId) {
            const product = allInventoryProducts.find(p => p.id === productId);
            if (!product) return;

            const printWindow = window.open('', '_blank', 'width=400,height=600');
            const barcodeValue = product.code || product.id.toString();

            printWindow.document.write(`
                <html>
                <head>
                    <title>Print Label - ${product.brand} ${product.model}</title>
                    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"><\/script>
                    <style>
                        @page { size: auto; margin: 0; }
                        body { 
                            font-family: 'Inter', sans-serif; 
                            margin: 0; 
                            padding: 20px; 
                            width: 80mm; 
                            text-align: center;
                        }
                        .label-card {
                            border: 1px dashed #ccc;
                            padding: 15px;
                            border-radius: 8px;
                        }
                        .store-name { font-size: 12px; font-weight: 800; text-transform: uppercase; margin-bottom: 10px; color: #64748b; }
                        .product-code { font-size: 14px; color: #3b82f6; font-weight: 700; margin-bottom: 5px; }
                        .product-name { font-size: 18px; font-weight: 800; margin-bottom: 15px; line-height: 1.2; }
                        .price-box {
                            background: #000;
                            color: #fff;
                            display: inline-block;
                            padding: 8px 16px;
                            border-radius: 6px;
                            font-size: 24px;
                            font-weight: 900;
                            margin-bottom: 15px;
                        }
                        .price-box span { font-size: 14px; font-weight: 400; margin-left: 4px; }
                        svg#barcode { width: 100%; max-height: 80px; }
                    </style>
                </head>
                <body>
                    <div class="label-card">
                        <div class="store-name">IBS SMART SOLUTIONS</div>
                        <div class="product-code">${product.code}</div>
                        <div class="product-name">${product.brand} ${product.model}</div>
                        <div><svg id="barcode"></svg></div>
                    </div>
                    <script>
                        JsBarcode("#barcode", "${barcodeValue}", {
                            format: "CODE128",
                            width: 2,
                            height: 60,
                            displayValue: true,
                            fontSize: 14,
                            margin: 10
                        });
                        setTimeout(() => {
                            window.print();
                            window.close();
                        }, 500);
                    <\/script>
                </body>
                </html>
            `);
            printWindow.document.close();
        }

        // Global variable to store all sales data
        let allSalesData = [];

        async function loadSales() {
            try {
                console.log('=== LOADING SALES DATA ===');
                const response = await fetch('api/sales.php');
                const result = await response.json();
                
                if (result.success) {
                    allSalesData = result.data;
                    displaySales(result.data);
                    
                    const countDiv = document.getElementById('sales-search-results-count');
                    if (countDiv) {
                        countDiv.textContent = `Showing all ${result.data.length} recent sales`;
                        countDiv.style.color = '#64748b';
                    }
                }
            } catch (error) {
                console.error('Error loading sales:', error);
            }
        }

        async function loadSalesStats() {
            try {
                const response = await fetch('api/sales.php?stats=true');
                const result = await response.json();
                
                if (result.success) {
                    const stats = result.data;
                    document.getElementById('stat-sales-today').textContent = stats.sales_today.toLocaleString();
                    document.getElementById('stat-revenue-today').innerHTML = stats.revenue_today.toLocaleString() + ' <small>EGP</small>';
                    document.getElementById('stat-sales-month').textContent = stats.sales_month.toLocaleString();
                    
                    // Unified monthly revenue IDs
                    const revMonthTotal = document.getElementById('stat-revenue-month-total');
                    const revMonthMini = document.getElementById('stat-revenue-month-mini');
                    const formattedRevenue = stats.revenue_month.toLocaleString() + ' <small>EGP</small>';
                    
                    if (revMonthTotal) revMonthTotal.innerHTML = formattedRevenue;
                    if (revMonthMini) revMonthMini.innerHTML = formattedRevenue;

                    document.getElementById('stat-total-items').textContent = stats.total_units.toLocaleString();
                    document.getElementById('stat-returns').textContent = stats.returned_items.toLocaleString();
                    
                    // Payment breakdown
                    if (stats.payment_breakdown) {
                        document.getElementById('stat-pay-cash').textContent = stats.payment_breakdown.Cash.toLocaleString() + ' EGP';
                        document.getElementById('stat-pay-visa').textContent = stats.payment_breakdown.Visa.toLocaleString() + ' EGP';
                        document.getElementById('stat-pay-instapay').textContent = stats.payment_breakdown.Instapay.toLocaleString() + ' EGP';
                        document.getElementById('stat-pay-installment').textContent = stats.payment_breakdown.Installment.toLocaleString() + ' EGP';
                    }
                }
            } catch (error) {
                console.error('Error loading sales stats:', error);
            }
        }

        function displaySales(sales) {
            const tbody = document.getElementById('sales-tbody');
            if (!tbody) return;
            
            if (!sales || sales.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 50px; color: #64748b;">No sales records found matching your criteria.</td></tr>';
                return;
            }

            tbody.innerHTML = sales.map(sale => {
                const date = new Date(sale.sale_date);
                const formattedDate = date.toLocaleDateString();
                const formattedTime = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                
                // Determine status badge
                let statusClass = 'status-completed';
                let statusTotalKey = 'sales.completed'; // Default key
                let statusKey = 'completed'; // For translation extraction
                
                if (sale.status === 'returned') {
                    statusClass = 'status-returned';
                    statusTotalKey = 'sales.returned';
                    statusKey = 'returned';
                } else if (sale.status === 'partially_returned') {
                    statusClass = 'status-partial';
                    statusTotalKey = 'sales.partiallyReturned';
                    statusKey = 'partiallyReturned';
                }

                const statusText = typeof langManager !== 'undefined' ? langManager.translate('sales', statusKey) : statusKey.charAt(0).toUpperCase() + statusKey.slice(1);

                return `
                <tr>
                    <td style="text-align: center;"><span style="font-weight: 700; color: #64748b;">#${sale.id}</span></td>
                    <td>
                        <div style="font-weight: 800; color: #1e293b;">${sale.receipt_number}</div>
                        <div style="font-size: 11px; color: #64748b; font-weight: 600;">${sale.staff_name}</div>
                    </td>
                    <td>
                        <div style="font-weight: 700; color: #334155; display: flex; align-items: center; gap: 5px;">
                            ${sale.customer_name || 'Walk-in Customer'}
                            ${sale.is_walk_in ? `<span class="badge" data-translate="inventory.walkIn" style="background: #e0f2fe; color: #0369a1; padding: 1px 6px; border-radius: 10px; font-size: 9px; font-weight: 800; border: 1px solid #bae6fd;">${typeof langManager !== 'undefined' ? langManager.translate('inventory', 'walkIn') : 'WALK-IN'}</span>` : ''}
                        </div>
                        <div style="font-size: 11px; color: #4f46e5; font-weight: 700;">${sale.customer_phone || 'N/A'}</div>
                    </td>
                    <td>
                        <div style="font-weight: 600;">${formattedDate}</div>
                        <div style="font-size: 11px; color: #64748b;">${formattedTime}</div>
                    </td>
                    <td style="text-align: center;">
                        <span style="background: #f1f5f9; color: #334155; padding: 2px 8px; border-radius: 4px; font-weight: 800; font-size: 11px; border: 1px solid #e2e8f0;">${sale.item_count}</span>
                    </td>
                    <td style="text-align: right; font-weight: 800; color: #1e293b;">
                        ${parseFloat(sale.total_amount).toLocaleString()} <span style="font-size: 10px; font-weight: 400; color: #64748b;">EGP</span>
                    </td>
                    <td style="text-align: center;">
                        <span class="status-badge-prof ${statusClass}">${statusText}</span>
                    </td>
                    <td style="text-align: center;">
                        <div style="display: flex; gap: 5px; justify-content: center;">
                            <button class="action-btn-prof" data-action="view" data-id="${sale.id}" title="View Details">👁️</button>
                            <button class="action-btn-prof" data-action="print" data-id="${sale.id}" title="Print Receipt">🧾</button>
                            <button class="action-btn-prof" data-action="return" data-id="${sale.id}" title="Return Items" ${sale.status === 'returned' ? 'disabled' : ''}>🔄</button>
                        </div>
                    </td>
                </tr>
                `;
            }).join('');

            // Attach event delegation for action buttons
            tbody.removeEventListener('click', salesTableClickHandler);
            tbody.addEventListener('click', salesTableClickHandler);
        }

        function salesTableClickHandler(e) {
            const btn = e.target.closest('[data-action]');
            if (!btn || btn.disabled) return;
            const id = parseInt(btn.dataset.id);
            const action = btn.dataset.action;
            if (action === 'view') viewReceiptDetails(id);
            else if (action === 'print') printReceipt(id);
            else if (action === 'return') confirmReturnSale(id);
        }

        async function performAdvancedSearch() {
            const barcode = document.getElementById('search-barcode').value.trim();
            const phone = document.getElementById('search-phone').value.trim();
            const dateFrom = document.getElementById('search-date-from').value;
            const dateTo = document.getElementById('search-date-to').value;
            const search = document.getElementById('search-general').value.trim();

            const tbody = document.getElementById('sales-tbody');
            tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px;"><div class="skeleton-row"></div></td></tr>';

            let queryParams = new URLSearchParams();
            if (barcode) queryParams.append('barcode', barcode);
            if (phone) queryParams.append('phone', phone);
            if (dateFrom) queryParams.append('date_from', dateFrom);
            if (dateTo) queryParams.append('date_to', dateTo);
            if (search) queryParams.append('search', search);
            
            try {
                const response = await fetch(`api/sales.php?${queryParams.toString()}`);
                const result = await response.json();
                if (result.success) {
                    displaySales(result.data);
                    
                    const countDiv = document.getElementById('sales-search-results-count');
                    if (countDiv) {
                        countDiv.textContent = `Found ${result.data.length} records matching criteria`;
                    }
                }
            } catch (error) {
                console.error('Error searching sales:', error);
            }
        }

        function clearAdvancedSearch() {
            document.getElementById('search-barcode').value = '';
            document.getElementById('search-phone').value = '';
            document.getElementById('search-date-from').value = '';
            document.getElementById('search-date-to').value = '';
            document.getElementById('search-general').value = '';
            loadSales();
        }

        async function confirmReturnSale(saleId) {
            // Professional Security Prompt for Returns
            const overlay = document.createElement('div');
            overlay.id = 'security-overlay';
            overlay.style.cssText = `
                position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px);
                display: flex; align-items: center; justify-content: center; z-index: 1000000;
                animation: fadeIn 0.3s ease;
            `;

            overlay.innerHTML = `
                <div style="background: white; width: 400px; padding: 30px; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.1); text-align: center;">
                    <div style="width: 60px; height: 60px; background: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 24px;">🔒</div>
                    <h3 style="margin: 0 0 10px; color: #1e293b; font-size: 20px; font-weight: 800;">Security Required</h3>
                    <p style="margin: 0 0 25px; color: #64748b; font-size: 14px; line-height: 1.5;">Please enter the management password to authorize return for Sale <b>#${saleId}</b></p>
                    
                    <input type="password" id="return-security-pass" placeholder="••••••••" style="width: 100%; padding: 12px 15px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 16px; margin-bottom: 20px; text-align: center; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='#ef4444'">
                    
                    <div style="display: flex; gap: 12px;">
                        <button id="cancel-return-btn" style="flex: 1; padding: 12px; background: #f1f5f9; color: #64748b; border: none; border-radius: 12px; font-weight: 700; cursor: pointer;">Cancel</button>
                        <button id="confirm-return-btn" style="flex: 1; padding: 12px; background: #ef4444; color: white; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; box-shadow: 0 10px 15px -3px rgba(239, 68, 68, 0.3);">Verify & Return</button>
                    </div>
                </div>
                <style>
                    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
                </style>
            `;

            document.body.appendChild(overlay);
            const input = document.getElementById('return-security-pass');
            input.focus();

            // Handle Buttons
            document.getElementById('cancel-return-btn').onclick = () => overlay.remove();
            
            const submitProcess = async () => {
                const password = input.value;
                if (!password) { alert('Password is required'); return; }

                const btn = document.getElementById('confirm-return-btn');
                btn.disabled = true;
                btn.innerText = 'Verifying...';

                try {
                    const response = await fetch('api/sales.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'return',
                            sale_id: saleId,
                            password: password
                        })
                    });

                    const result = await response.json();
                    if (result.success) {
                        overlay.remove();
                        alert('✅ Return processed successfully!');
                        loadSales(); 
                        if (typeof loadReports === 'function') loadReports();
                    } else {
                        alert('❌ ' + result.message);
                        btn.disabled = false;
                        btn.innerText = 'Verify & Return';
                        input.value = '';
                        input.focus();
                    }
                } catch (error) {
                    console.error('Return error:', error);
                    alert('An error occurred during verification.');
                    btn.disabled = false;
                    btn.innerText = 'Verify & Return';
                }
            };

            document.getElementById('confirm-return-btn').onclick = submitProcess;
            input.onkeypress = (e) => { if (e.key === 'Enter') submitProcess(); };
        }

        async function displaySalesStats(sales) {
            try {
                const today = new Date().toDateString();
                const todaySales = sales.filter(s => new Date(s.sale_date).toDateString() === today);
                const thisMonth = new Date().getMonth();
                const monthSales = sales.filter(s => new Date(s.sale_date).getMonth() === thisMonth);

                // Simple profit calculation
                const todayProfit = await calculateProfit(todaySales, true);
                const monthProfit = await calculateProfit(monthSales, false);

                // Calculate total income (revenue) from sales
                const todaySalesIncome = todaySales.reduce((sum, sale) => sum + sale.total_amount, 0);
                const monthSalesIncome = monthSales.reduce((sum, sale) => sum + sale.total_amount, 0);

                // Simple display without complex calculations
                document.getElementById('sales-stats').innerHTML = `
                    <div class="stat-card"><h3>${todaySales.length}</h3><p>Today's Orders</p></div>
                    <div class="stat-card"><h3>${todayProfit.toFixed(0)} EGP </h3><p>Today's Profit</p></div>
                    <div class="stat-card"><h3>${monthSales.length}</h3><p>This Month Orders</p></div>
                    <div class="stat-card"><h3>${monthProfit.toFixed(0)} EGP </h3><p>Month Profit</p></div>
                    <div class="stat-card total-income-card"><h3>${todaySalesIncome.toFixed(2)} EGP</h3><p>💰 Today's Total Income</p></div>
                    <div class="stat-card monthly-income-card"><h3>${monthSalesIncome.toFixed(2)} EGP</h3><p>📅 This Month's Total Income</p></div>
                `;
            } catch (error) {
                console.error('Error displaying sales stats:', error);
            }
        }

        async function calculateProfit(sales, isToday = false) {
            let totalProfit = 0;

            // Simple fallback calculation for now
            try {
                totalProfit = sales.reduce((sum, s) => sum + s.total_amount, 0);
            } catch (error) {
                console.error('Error calculating profit:', error);
                totalProfit = 0;
            }

            return totalProfit;
        }

        async function loadStaff() {
            try {
                // Use PHP-loaded data directly
                if (allStaffMembers && allStaffMembers.length > 0) {
                    console.log('Loading staff from PHP data:', allStaffMembers);
                    
                    // Add small delay to ensure DOM is ready
                    setTimeout(() => {
                        displayStaff(allStaffMembers);
                        
                        // Initialize staff search results count
                        const resultsCountDiv = document.getElementById('staff-search-results-count');
                        if (resultsCountDiv) {
                            resultsCountDiv.textContent = `Showing all ${allStaffMembers.length} staff members`;
                            resultsCountDiv.style.color = '#666';
                        }
                    }, 100);
                    
                    return;
                }

                // If no PHP data, show message instead of calling API
                console.log('No staff data available from PHP');
                const tbody = document.getElementById('staff-tbody');
                if (tbody) {
                    tbody.innerHTML = '<tr><td colspan="5">No staff data available</td></tr>';
                }
                
            } catch (error) {
                console.error('Error loading staff:', error);
                const tbody = document.getElementById('staff-tbody');
                if (tbody) {
                    tbody.innerHTML = `<tr><td colspan="5">Error loading staff: ${error.message}</td></tr>`;
                }
            }
        }

        function displayStaff(staff) {
            console.log('displayStaff called with data:', staff);
            const tbody = document.getElementById('staff-tbody');
            if (!tbody) {
                console.error('staff-tbody element not found!');
                return;
            }
            
            tbody.innerHTML = staff.map(member => {
                const isActive = parseInt(member.is_active) === 1;
                const roleColor = member.role === 'admin' ? '#4f46e5' : '#64748b';
                const roleBg = member.role === 'admin' ? '#eef2ff' : '#f8fafc';
                const statusColor = isActive ? '#10b981' : '#f43f5e';
                const statusBg = isActive ? '#ecfdf5' : '#fff1f2';
                const statusText = isActive ? 'Active' : 'Inactive';

                return `
                <tr style="border-bottom: 1.5px solid #f1f5f9; transition: background 0.2s; cursor: default;" onmouseover="this.style.background='#fdfdfd'" onmouseout="this.style.background='transparent'">
                    <td style="padding: 16px 25px; font-weight: 600; color: #1e293b;">${member.username}</td>
                    <td style="padding: 16px 25px;">
                        <div style="font-weight: 700; color: #0f172a;">${member.name}</div>
                        <div style="font-size: 11px; color: #94a3b8;">${member.email || ''}</div>
                    </td>
                    <td style="padding: 16px 25px;">
                        <span style="padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 800; background: ${roleBg}; color: ${roleColor};">
                            ${member.role.toUpperCase()}
                        </span>
                    </td>
                    <td style="padding: 16px 25px; color: #475569; font-weight: 500;">${member.phone || '—'}</td>
                    <td style="padding: 16px 25px; text-align: center;">
                        <span style="padding: 6px 14px; border-radius: 20px; font-size: 11px; font-weight: 700; background: ${statusBg}; color: ${statusColor};">
                            ● ${statusText}
                        </span>
                    </td>
                    <td style="padding: 16px 25px; text-align: center;">
                        <div style="display: flex; gap: 10px; justify-content: center;">
                            <button class="cust-action-btn edit" onclick="editUser(${member.id})" 
                                    style="padding: 6px; background: #eff6ff; color: #2563eb; border: 1px solid #dbeafe; border-radius: 8px; cursor: pointer; transition: all 0.2s;" 
                                    onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                                ✏️
                            </button>
                            <button class="cust-action-btn delete" onclick="deleteUser(${member.id})" 
                                    style="padding: 6px; background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; border-radius: 8px; cursor: pointer; transition: all 0.2s;"
                                    onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                                🗑️
                            </button>
                        </div>
                    </td>
                </tr>`;
            }).join('');
        }

        function filterStaff(searchTerm) {
            const resultsCountDiv = document.getElementById('staff-search-results-count');

            if (!searchTerm.trim()) {
                // If search is empty, show all staff
                displayStaff(allStaffMembers);
                resultsCountDiv.textContent = `Showing all ${allStaffMembers.length} staff members`;
                resultsCountDiv.style.color = '#666';
                return;
            }

            const searchLower = searchTerm.toLowerCase();
            const filteredStaff = allStaffMembers.filter(member => {
                return (
                    member.username.toLowerCase().includes(searchLower) ||
                    member.name.toLowerCase().includes(searchLower) ||
                    member.role.toLowerCase().includes(searchLower) ||
                    (member.phone && member.phone.toLowerCase().includes(searchLower)) ||
                    (member.email && member.email.toLowerCase().includes(searchLower))
                );
            });

            displayStaff(filteredStaff);

            // Update results count
            if (filteredStaff.length === 0) {
                resultsCountDiv.textContent = 'No staff members found matching your search';
                resultsCountDiv.style.color = '#dc3545';
            } else {
                resultsCountDiv.textContent = `Found ${filteredStaff.length} staff member${filteredStaff.length === 1 ? '' : 's'} matching "${searchTerm}"`;
                resultsCountDiv.style.color = '#28a745';
            }
        }

        function editUser(userId) {
            const user = allStaffMembers.find(member => member.id === userId);
            if (!user) {
                alert('User not found');
                return;
            }

            // Helper function to safely set element value
            const safeSetValue = (elementId, value) => {
                const element = document.getElementById(elementId);
                if (element) {
                    element.value = value;
                } else {
                    console.warn(`Edit user element not found: ${elementId}`);
                }
            };

            // Populate the edit form with null checks
            safeSetValue('editUserId', user.id);
            safeSetValue('editUsername', user.username);
            safeSetValue('editPassword', ''); // Always start with empty password
            safeSetValue('editName', user.name);
            safeSetValue('editRole', user.role);
            safeSetValue('editPhone', user.phone || '');
            safeSetValue('editEmail', user.email || '');
            safeSetValue('editStatus', user.is_active ? '1' : '0');

            // Show the modal
            const modal = document.getElementById('editUserModal');
            if (modal) {
                modal.style.display = 'block';
            } else {
                console.error('Edit user modal not found');
                alert('Edit user modal not found. Please refresh the page.');
            }
        }

        function closeEditModal() {
            const modal = document.getElementById('editUserModal');
            if (modal) {
                modal.style.display = 'none';
            } else {
                console.warn('Edit user modal not found for closing');
            }
        }

        // Close modal when clicking outside
        window.onclick = function (event) {
            const userModal = document.getElementById('editUserModal');
            const productModal = document.getElementById('editProductModal');
            const receiptModal = document.getElementById('receiptDetailsModal');

            if (event.target === userModal && userModal) {
                closeEditModal();
            }
            if (event.target === productModal && productModal) {
                closeEditProductModal();
            }
            if (event.target === receiptModal && receiptModal) {
                closeReceiptDetailsModal();
            }
        }

        function editProduct(productId) {
            console.log('editProduct called with productId:', productId);
            const product = allInventoryProducts.find(p => p.id === productId);
            
            if (!product) {
                alert('Product not found in current inventory list');
                return;
            }

            // Populate the edit form
            document.getElementById('editProductId').value = product.id;
            document.getElementById('editProductCode').value = product.code;
            document.getElementById('editProductBrand').value = product.brand;
            document.getElementById('editProductModel').value = product.model;
            document.getElementById('editProductPrice').value = product.suggested_price || 0;
            document.getElementById('editProductStock').value = product.available_stock || 0;
            document.getElementById('editProductMinStock').value = (product.min_stock !== null ? product.min_stock : '');
            document.getElementById('editProductCategoryId').value = (product.category_id || 0);
            document.getElementById('editProductIsActive').value = (product.is_active ? "1" : "0");
            document.getElementById('editProductPurchasePrice').value = product.purchase_price || 0;
            document.getElementById('editProductMinSellingPrice').value = product.min_selling_price || 0;
            document.getElementById('editProductImageUrl').value = product.image_url || '';
            document.getElementById('editProductDescription').value = product.description || '';
            
            // Ensure all fields are visible
            const allFields = ['editProductPurchasePrice', 'editProductMinSellingPrice', 'editProductImageUrl', 'editProductDescription'];
            allFields.forEach(id => {
                const el = document.getElementById(id);
                if (el && el.closest('.form-group')) {
                    el.closest('.form-group').style.opacity = '1';
                }
            });

            // Show the modal
            document.getElementById('editProductModal').style.display = 'flex';
        }

        function closeEditProductModal() {
            console.log('closeEditProductModal called');
            const modal = document.getElementById('editProductModal');
            console.log('Modal element:', modal);
            if (modal) {
                modal.style.display = 'none';
                console.log('Modal hidden');
            } else {
                console.log('Modal not found!');
            }
        }

        // Toggle Product Status (Activate/Deactivate)
        async function toggleProductStatus(productId, activate) {
            const product = allInventoryProducts.find(p => p.id === productId);
            if (!product) {
                alert('Product not found');
                return;
            }

            const action = activate ? 'activate' : 'deactivate';
            const confirmMessage = `Are you sure you want to ${action} "${product.brand} ${product.model}" (${product.code})?`;
            if (!confirm(confirmMessage)) {
                return;
            }

            try {
                const response = await fetch('api/products.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: productId,
                        is_active: activate ? 1 : 0
                    })
                });

                console.log('Toggle status response:', response.status);

                if (!response.ok) {
                    let errorText = '';
                    try {
                        errorText = await response.text();
                        try {
                            const errorJson = JSON.parse(errorText);
                            alert('Server error: ' + (errorJson.message || errorText));
                        } catch (parseError) {
                            alert('Server error: ' + response.status + ' - ' + (errorText.substring(0, 200) || 'Unknown error'));
                        }
                    } catch (e) {
                        alert('Server error: ' + response.status + ' - Failed to read error message');
                    }
                    return;
                }

                const responseText = await response.text();
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    alert('Error: Invalid JSON response from server.');
                    return;
                }

                if (result.success) {
                    alert(`Product ${activate ? 'activated' : 'deactivated'} successfully!`);

                    // Update local data
                    const productIndex = allInventoryProducts.findIndex(p => p.id === productId);
                    if (productIndex !== -1) {
                        allInventoryProducts[productIndex].is_active = activate;
                    }

                    // Refresh inventory display
                    loadInventory();
                    loadProducts(); // Refresh products for POS system
                } else {
                    alert('Failed to ' + action + ' product: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error toggling product status:', error);
                alert('Error: ' + error.message);
            }
        }

        // Receipt Details Functions
        async function viewReceiptDetails(saleId) {
            try {
                console.log('=== VIEWING RECEIPT DETAILS ===');
                console.log('Sale ID:', saleId);
                
                const response = await fetch(`api/sales.php?id=${saleId}`);
                console.log('API Response status:', response.status);
                
                const result = await response.json();
                console.log('API Response result:', result);

                if (result.success) {
                    const sale = result.data;
                    console.log('Sale data:', sale);

                    // Helper function to safely set element text
                    const safeSetText = (elementId, text) => {
                        const element = document.getElementById(elementId);
                        if (element) {
                            element.textContent = text;
                        } else {
                            console.warn(`Element not found: ${elementId}`);
                        }
                    };

                    // Populate receipt information with null checks
                    safeSetText('detailReceiptNumber', sale.receipt_number);
                    safeSetText('detailDate', new Date(sale.sale_date).toLocaleDateString());
                    safeSetText('detailStaff', sale.staff_name);
                    safeSetText('detailCustomer', sale.customer_name || 'Walk-in Customer');
                    safeSetText('detailPaymentMethod', sale.payment_method || 'Cash');
                    safeSetText('detailTotalAmount', formatCurrency(sale.total_amount) + ' EGP');

                    // Populate payment summary  
                    const totalOriginal = sale.items.reduce((sum, item) => sum + (parseFloat(item.unit_price) * parseInt(item.quantity)), 0);
                    const totalReturned = sale.items.reduce((sum, item) => sum + (parseFloat(item.unit_price) * parseInt(item.returned_quantity || 0)), 0);
                    const netTotal = totalOriginal - totalReturned;

                    safeSetText('detailTotalAmount', formatCurrency(totalOriginal) + ' EGP');
                    safeSetText('originalTotalAmount', formatCurrency(totalOriginal) + ' EGP');
                    safeSetText('returnCreditAmount', formatCurrency(totalReturned) + ' EGP');
                    safeSetText('detailGrandTotal', formatCurrency(netTotal) + ' EGP');

                    // Populate tables
                    const hasReturns = sale.items.some(item => parseInt(item.returned_quantity) > 0);
                    
                    // Toggle sections visibility
                    document.getElementById('returnedItemsSection').style.display = hasReturns ? 'block' : 'none';
                    document.getElementById('adjustedReceiptSection').style.display = hasReturns ? 'block' : 'none';
                    document.getElementById('finalTotalLabel').dataset.translate = hasReturns ? 'sales.netTotal' : 'sales.total';
                    
                    // Section 1: Original Purchase
                    const originalTable = document.getElementById('originalPurchaseTable');
                    if (originalTable) {
                        originalTable.innerHTML = sale.items.map(item => {
                            let identifiers = '';
                            if (item.imei) identifiers += `<div><small>IMEI: ${item.imei}</small></div>`;
                            if (item.serial) identifiers += `<div><small>SN: ${item.serial}</small></div>`;
                            return `
                                <tr style="border-bottom: 1px solid #ddd;">
                                    <td style="padding: 12px;">
                                        <strong>${item.product_name || ''}</strong><br>
                                        <small style="color: #666;">${item.product_code || ''}</small>
                                        ${identifiers}
                                    </td>
                                    <td style="padding: 12px; text-align: center;">${item.quantity}</td>
                                    <td style="padding: 12px; text-align: right;">${formatCurrency(item.unit_price)} EGP</td>
                                    <td style="padding: 12px; text-align: right; font-weight: bold;">${formatCurrency(parseFloat(item.unit_price) * parseInt(item.quantity))} EGP</td>
                                </tr>
                            `;
                        }).join('');
                    }

                    // Section 2: Returned Items
                    if (hasReturns) {
                        const returnedTable = document.getElementById('returnedItemsTable');
                        const itemsWithReturns = sale.items.filter(item => parseInt(item.returned_quantity) > 0);
                        if (returnedTable) {
                            returnedTable.innerHTML = itemsWithReturns.map(item => `
                                <tr style="border-bottom: 1px solid #ddd;">
                                    <td style="padding: 12px;">
                                        <strong>${item.product_name || ''}</strong><br>
                                        <small style="color: #666;">${item.product_code || ''}</small>
                                    </td>
                                    <td style="padding: 12px; text-align: center; color: #ef4444; font-weight: bold;">${item.returned_quantity}</td>
                                    <td style="padding: 12px; text-align: right;">${formatCurrency(item.unit_price)} EGP</td>
                                    <td style="padding: 12px; text-align: right; font-weight: bold; color: #ef4444;">${formatCurrency(parseFloat(item.unit_price) * parseInt(item.returned_quantity))} EGP</td>
                                </tr>
                            `).join('');
                        }

                        // Section 3: Final Adjusted Receipt
                        const adjustedTable = document.getElementById('adjustedReceiptTable');
                        if (adjustedTable) {
                            adjustedTable.innerHTML = sale.items.map(item => {
                                const netQty = parseInt(item.quantity) - parseInt(item.returned_quantity || 0);
                                if (netQty <= 0) return ''; // Skip fully returned items in adjusted view
                                return `
                                    <tr style="border-bottom: 1px solid #ddd;">
                                        <td style="padding: 12px;">
                                            <strong>${item.product_name || ''}</strong><br>
                                            <small style="color: #666;">${item.product_code || ''}</small>
                                        </td>
                                        <td style="padding: 12px; text-align: center; color: #22c55e; font-weight: bold;">${netQty}</td>
                                        <td style="padding: 12px; text-align: right;">${formatCurrency(item.unit_price)} EGP</td>
                                        <td style="padding: 12px; text-align: right; font-weight: bold; color: #22c55e;">${formatCurrency(parseFloat(item.unit_price) * netQty)} EGP</td>
                                    </tr>
                                `;
                            }).join('');
                        }
                    }

                    // Re-apply translations for new headers
                    if (typeof langManager !== 'undefined') {
                        langManager.applyLanguage(langManager.getCurrentLanguage());
                    }

                    // Store sale data for printing
                    window.currentReceiptData = sale;

                    // Show the modal
                    const modal = document.getElementById('receiptDetailsModal');
                    if (modal) {
                        // Move to body to escape any stacking context
                        document.body.appendChild(modal);
                        modal.style.display = 'block';
                        modal.scrollTop = 0;
                        document.body.style.overflow = 'hidden';
                    } else {
                        console.error('Receipt details modal not found');
                        alert('Receipt details modal not found. Please refresh the page.');
                    }
                } else {
                    console.error('Failed to load receipt details:', result.message);
                    alert('Failed to load receipt details: ' + result.message);
                }
            } catch (error) {
                console.error('Error loading receipt details:', error);
                alert('Error loading receipt details. Please try again.');
            }
        }

        function closeReceiptDetailsModal() {
            const modal = document.getElementById('receiptDetailsModal');
            if (modal) modal.style.display = 'none';
            document.body.style.overflow = '';
        }

        let currentReturnSaleId = null;

        function confirmReturnSale(saleId) {
            openReturnItemsModal(saleId);
        }

        async function openReturnItemsModal(saleId) {
            currentReturnSaleId = saleId;
            try {
                const response = await fetch(`api/sales.php?id=${saleId}`);
                const result = await response.json();
                
                if (result.success) {
                    const sale = result.data;
                    document.getElementById('returnReceiptNumber').textContent = sale.receipt_number;
                    document.getElementById('returnCustomer').textContent = sale.customer_name;
                    document.getElementById('returnDate').textContent = new Date(sale.sale_date).toLocaleDateString();
                    document.getElementById('returnTotalAmount').textContent = sale.total_amount.toLocaleString() + ' EGP';
                    
                    const tbody = document.getElementById('returnItemsTableBody');
                    tbody.innerHTML = sale.items.map(item => {
                        const available = item.quantity - item.returned_quantity;
                        return `
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px;">
                                    <div style="font-weight: 700; color: #1e293b;">${item.product_name}</div>
                                    <div style="font-size: 11px; color: #64748b;">${item.product_code}</div>
                                </td>
                                <td style="padding: 12px; text-align: center; font-weight: 600;">${item.quantity}</td>
                                <td style="padding: 12px; text-align: center; color: #ef4444; font-weight: 600;">${item.returned_quantity}</td>
                                <td style="padding: 12px; text-align: center;">
                                    <input type="number" 
                                           class="return-qty-input" 
                                           data-item-id="${item.id}" 
                                           min="0" 
                                           max="${available}" 
                                           value="0"
                                           onchange="validateReturnQty(this, ${available})"
                                           style="width: 70px; padding: 6px; border: 2px solid #e2e8f0; border-radius: 6px; font-weight: 700; text-align: center;">
                                </td>
                            </tr>
                        `;
                    }).join('');
                    
                    const modal = document.getElementById('returnItemsModal');
                    if (modal) {
                        document.body.appendChild(modal);
                        modal.style.display = 'block';
                        document.body.style.overflow = 'hidden';
                    }
                } else {
                    alert('Failed to load sale items: ' + result.message);
                }
            } catch (error) {
                console.error('Error opening return modal:', error);
                alert('Error loading sale details.');
            }
        }

        function validateReturnQty(input, max) {
            const val = parseInt(input.value) || 0;
            if (val < 0) input.value = 0;
            if (val > max) input.value = max;
        }

        function closeReturnItemsModal() {
            const modal = document.getElementById('returnItemsModal');
            if (modal) modal.style.display = 'none';
            document.body.style.overflow = '';
        }

        async function submitReturnItems() {
            const inputs = document.querySelectorAll('.return-qty-input');
            const itemsToReturn = [];
            
            inputs.forEach(input => {
                const qty = parseInt(input.value) || 0;
                if (qty > 0) {
                    itemsToReturn.push({
                        item_id: parseInt(input.dataset.itemId),
                        return_qty: qty
                    });
                }
            });
            
            if (itemsToReturn.length === 0) {
                alert('Please select at least one item and quantity to return.');
                return;
            }
            
            openConfirmReturnModal(() => {
                proceedWithReturn(itemsToReturn);
            });
        }

        function openConfirmReturnModal(onConfirm) {
            const modal = document.getElementById('confirmReturnModal');
            const confirmBtn = document.getElementById('executeReturnBtn');
            
            if (!modal || !confirmBtn) {
                console.error('Confirm return modal or button NOT FOUND in DOM!');
                alert('Internal Error: Confirmation components missing.');
                return;
            }

            // Ensure it's in body to be on top
            document.body.appendChild(modal);

            // Re-apply translations in case lang changed
            if (typeof langManager !== 'undefined') {
                langManager.applyLanguage(langManager.getCurrentLanguage());
            }

            confirmBtn.onclick = () => {
                closeConfirmReturnModal();
                onConfirm();
            };
            
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            console.log('Confirmation modal displayed');
        }

        function closeConfirmReturnModal() {
            const modal = document.getElementById('confirmReturnModal');
            if (modal) modal.style.display = 'none';
            if (!document.getElementById('returnItemsModal').style.display) {
                document.body.style.overflow = '';
            }
        }

        function openReturnSuccessModal() {
            const modal = document.getElementById('returnSuccessModal');
            if (modal) {
                document.body.appendChild(modal);
                
                // Re-apply translations
                if (typeof langManager !== 'undefined') {
                    langManager.applyLanguage(langManager.getCurrentLanguage());
                }
                
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        }

        function closeReturnSuccessModal() {
            const modal = document.getElementById('returnSuccessModal');
            if (modal) modal.style.display = 'none';
            document.body.style.overflow = '';
        }

        async function proceedWithReturn(itemsToReturn) {
            // Professional Security Prompt for Partial Returns
            const overlay = document.createElement('div');
            overlay.id = 'return-security-overlay';
            overlay.style.cssText = `
                position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px);
                display: flex; align-items: center; justify-content: center; z-index: 1000000;
                animation: fadeIn 0.3s ease;
            `;

            overlay.innerHTML = `
                <div style="background: white; width: 400px; padding: 30px; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.1); text-align: center;">
                    <div style="width: 60px; height: 60px; background: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 24px;">🔒</div>
                    <h3 style="margin: 0 0 10px; color: #1e293b; font-size: 20px; font-weight: 800;">Authorize Return</h3>
                    <p style="margin: 0 0 25px; color: #64748b; font-size: 14px; line-height: 1.5;">Management password required to authorize this return.</p>
                    
                    <input type="password" id="partial-return-pass" placeholder="••••••••" style="width: 100%; padding: 12px 15px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 16px; margin-bottom: 20px; text-align: center; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='#ef4444'">
                    
                    <div style="display: flex; gap: 12px;">
                        <button id="cancel-partial-return-btn" style="flex: 1; padding: 12px; background: #f1f5f9; color: #64748b; border: none; border-radius: 12px; font-weight: 700; cursor: pointer;">Cancel</button>
                        <button id="confirm-partial-return-btn" style="flex: 1; padding: 12px; background: #ef4444; color: white; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; box-shadow: 0 10px 15px -3px rgba(239, 68, 68, 0.3);">Confirm Return</button>
                    </div>
                </div>
                <style>
                    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
                </style>
            `;

            document.body.appendChild(overlay);
            const input = document.getElementById('partial-return-pass');
            input.focus();

            document.getElementById('cancel-partial-return-btn').onclick = () => overlay.remove();
            
            const submitProcess = async () => {
                const password = input.value;
                if (!password) { alert('Password is required'); return; }

                const btn = document.getElementById('confirm-partial-return-btn');
                const submitBtn = document.getElementById('submitReturnBtn');
                
                btn.disabled = true;
                btn.innerText = 'Verifying...';

                try {
                    const response = await fetch('api/sales.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'return',
                            sale_id: typeof currentReturnSaleId !== 'undefined' ? currentReturnSaleId : null,
                            items: itemsToReturn,
                            password: password,
                            staff_id: <?php echo isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 1; ?>
                        })
                    });
                    
                    const result = await response.json();
                    if (result.success) {
                        overlay.remove();
                        openReturnSuccessModal();
                        closeReturnItemsModal();
                        loadSales();
                        loadSalesStats();
                    } else {
                        alert('❌ ' + result.message);
                        btn.disabled = false;
                        btn.innerText = 'Confirm Return';
                        input.value = '';
                        input.focus();
                    }
                } catch (error) {
                    console.error('Error submitting return:', error);
                    alert('An error occurred during return processing.');
                    btn.disabled = false;
                    btn.innerText = 'Confirm Return';
                }
            };

            document.getElementById('confirm-partial-return-btn').onclick = submitProcess;
            input.onkeypress = (e) => { if (e.key === 'Enter') submitProcess(); };
        }

        function printReceiptFromModal() {
            if (window.currentReceiptData) {
                printReceipt(window.currentReceiptData.id);
            }
        }

        async function printReceipt(saleId) {
            try {
                // Get receipt data if not already loaded
                let receiptData = window.currentReceiptData;
                if (!receiptData || receiptData.id !== saleId) {
                    const response = await fetch(`api/sales.php?id=${saleId}`);
                    const result = await response.json();
                    if (result.success) {
                        receiptData = result.data;
                    } else {
                        alert('Failed to load receipt data for printing');
                        return;
                    }
                }

                // Create printable receipt
                const printWindow = window.open('', '_blank');
                if (!printWindow) {
                    alert('Pop-up blocked! Please allow pop-ups for this site to print receipts.');
                    return;
                }
                
                const printContent = generatePrintableReceipt(receiptData);

                printWindow.document.write(printContent);
                printWindow.document.close();
                
                // Add a small delay to ensure content is loaded before printing
                setTimeout(() => {
                    printWindow.focus();
                    printWindow.print();
                    printWindow.close();
                }, 500);

            } catch (error) {
                console.error('Error printing receipt:', error);
                alert('Error printing receipt. Please try again.');
            }
        }

        function generatePrintableReceipt(receiptData) {
            const currentDate = new Date(receiptData.sale_date).toLocaleString();
            const receiptNumber = receiptData.receipt_number;
            const receiptId = receiptData.id;
            
            // Generate barcode URL using online barcode generator
            const scannerUrl = `${window.location.origin}/receipt_scanner.php#receipt-details-${receiptId}`;
            const barcodeUrl = `https://barcode.tec-it.com/barcode.ashx?data=${encodeURIComponent(scannerUrl)}&code=Code128&multiplebarcodes=false&translate-esc=false&unit=Fit&dpi=96&imagetype=Gif&rotation=0&color=%23000000&bgcolor=%23ffffff&codepage=&qunit=Mm&text=0`;
            
            return `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Receipt - ${receiptNumber}</title>
                    <style>
                        @page { 
                            size: 80mm auto; 
                            margin: 5mm; 
                        }
                        body { 
                            font-family: 'Courier New', monospace; 
                            width: 80mm; 
                            margin: 0 auto; 
                            padding: 10px; 
                            font-size: 12px;
                            line-height: 1.2;
                        }
                        .header { 
                            text-align: center; 
                            margin-bottom: 15px; 
                            border-bottom: 2px dashed #000; 
                            padding-bottom: 10px; 
                        }
                        .company-name { 
                            font-size: 16px; 
                            font-weight: bold; 
                            margin-bottom: 5px;
                        }
                        .company-address { 
                            font-size: 10px; 
                            margin-bottom: 5px;
                        }
                        .receipt-info { 
                            margin-bottom: 15px; 
                            font-size: 11px;
                        }
                        .receipt-info div { 
                            margin-bottom: 3px;
                        }
                        .items-table { 
                            width: 100%; 
                            border-collapse: collapse; 
                            margin-bottom: 15px; 
                            font-size: 10px;
                        }
                        .items-table th, .items-table td { 
                            text-align: left; 
                            padding: 3px 0;
                            vertical-align: top;
                        }
                        .items-table th { 
                            border-bottom: 1px solid #000; 
                            font-weight: bold;
                            font-size: 9px;
                        }
                        .item-name { 
                            max-width: 45mm;
                            word-wrap: break-word;
                        }
                        .item-qty { 
                            text-align: center;
                            width: 10mm;
                        }
                        .item-price { 
                            text-align: right;
                            width: 15mm;
                        }
                        .item-total { 
                            text-align: right;
                            width: 15mm;
                            font-weight: bold;
                        }
                        .total-section { 
                            border-top: 2px solid #000; 
                            padding-top: 10px;
                            margin-top: 10px;
                        }
                        .total-line { 
                            display: flex; 
                            justify-content: space-between; 
                            margin-bottom: 5px;
                            font-size: 11px;
                        }
                        .final-total { 
                            font-weight: bold; 
                            font-size: 14px; 
                            border-top: 1px solid #000; 
                            padding-top: 5px;
                            margin-top: 8px;
                        }
                        .payment-info {
                            margin-top: 10px;
                            font-size: 10px;
                            border-top: 1px dashed #000;
                            padding-top: 8px;
                        }
                        .barcode-section {
                            text-align: center;
                            margin: 15px 0;
                            padding: 10px;
                            border: 1px dashed #000;
                        }
                        .barcode-image {
                            max-width: 60mm;
                            height: 20mm;
                            margin: 5px auto;
                        }
                        .barcode-text {
                            font-size: 12px;
                            font-weight: bold;
                            margin: 5px 0;
                        }
                        .barcode-instructions {
                            font-size: 8px;
                            color: #666;
                            margin-top: 5px;
                        }
                        .footer { 
                            text-align: center; 
                            margin-top: 15px; 
                            border-top: 1px dashed #000; 
                            padding-top: 10px; 
                            font-size: 10px;
                        }
                        @media print { 
                            body { width: auto; margin: 0; }
                            .no-print { display: none; }
                            .barcode-instructions { display: none; }
                        }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <div class="company-name">📱 IBS MOBILE SHOP</div>
                        <div class="company-address">Mobile & Electronics Store</div>
                        <div class="company-address">📍 Egypt - Cairo</div>
                        <div class="company-address">📞 +20 123 456 7890</div>
                    </div>
                    
                    <div class="receipt-info">
                        <div><strong>🧾 RECEIPT #:</strong> ${receiptNumber}</div>
                        <div><strong>📅 DATE:</strong> ${currentDate}</div>
                        <div><strong>👤 STAFF:</strong> ${receiptData.staff_name || 'N/A'}</div>
                        <div><strong>🧑 CUSTOMER:</strong> ${receiptData.customer_name || 'Walk-in Customer'}</div>
                        <div><strong>💳 PAYMENT:</strong> ${receiptData.payment_method || 'Cash'}</div>
                    </div>

                    ${(() => {
                        const hasReturns = receiptData.items.some(item => (parseInt(item.returned_quantity) || 0) > 0);
                        const totalOriginal = receiptData.items.reduce((sum, item) => sum + (parseFloat(item.unit_price) * parseInt(item.quantity)), 0);
                        const totalReturned = receiptData.items.reduce((sum, item) => sum + (parseFloat(item.unit_price) * (parseInt(item.returned_quantity) || 0)), 0);
                        const netTotal = totalOriginal - totalReturned;

                        if (!hasReturns) {
                            return `
                                <table class="items-table">
                                    <thead>
                                        <tr>
                                            <th class="item-name">ITEM</th>
                                            <th class="item-qty">QTY</th>
                                            <th class="item-price">PRICE</th>
                                            <th class="item-total">TOTAL</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${receiptData.items.map(item => `
                                            <tr>
                                                <td class="item-name">
                                                    <strong>${item.product_name}</strong><br>
                                                    <small style="color: #666;">${item.product_code || item.code || ''}</small>
                                                    ${item.imei ? `<div style="font-size: 8px; color: #666;">IMEI: ${item.imei}</div>` : ''}
                                                    ${item.serial ? `<div style="font-size: 8px; color: #666;">SN: ${item.serial}</div>` : ''}
                                                </td>
                                                <td class="item-qty">${item.quantity}</td>
                                                <td class="item-price">${formatCurrency(item.unit_price)}</td>
                                                <td class="item-total">${formatCurrency(item.total_price)}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                                <div class="total-section">
                                    <div class="total-line final-total">
                                        <span>💰 TOTAL AMOUNT:</span>
                                        <span>${formatCurrency(totalOriginal)} EGP</span>
                                    </div>
                                </div>
                            `;
                        } else {
                            // Detailed 3-table layout for returns
                            return `
                                <!-- Section 1: Original Purchase -->
                                <div style="font-weight: bold; margin-bottom: 5px; font-size: 11px; text-decoration: underline;">ORIGINAL PURCHASE</div>
                                <table class="items-table">
                                    <thead>
                                        <tr>
                                            <th class="item-name">ITEM</th>
                                            <th class="item-qty">QTY</th>
                                            <th class="item-total">TOTAL</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${receiptData.items.map(item => `
                                            <tr>
                                                <td class="item-name">${item.product_name}</td>
                                                <td class="item-qty">${item.quantity}</td>
                                                <td class="item-total">${formatCurrency(parseFloat(item.unit_price) * parseInt(item.quantity))}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                                <div style="text-align: right; font-size: 10px; margin-bottom: 10px;">
                                    Original Total: <strong>${formatCurrency(totalOriginal)} EGP</strong>
                                </div>

                                <!-- Section 2: Returned Items -->
                                <div style="font-weight: bold; margin-bottom: 5px; font-size: 11px; text-decoration: underline; color: #f00;">RETURNED ITEMS</div>
                                <table class="items-table">
                                    <tbody>
                                        ${receiptData.items.filter(i => parseInt(i.returned_quantity) > 0).map(item => `
                                            <tr>
                                                <td class="item-name">${item.product_name}</td>
                                                <td class="item-qty">(-${item.returned_quantity})</td>
                                                <td class="item-total" style="color: #f00;">-${formatCurrency(parseFloat(item.unit_price) * parseInt(item.returned_quantity))}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                                <div style="text-align: right; font-size: 10px; margin-bottom: 10px; color: #f00;">
                                    Return Credit: <strong>-${formatCurrency(totalReturned)} EGP</strong>
                                </div>

                                <!-- Section 3: Final Adjusted Receipt -->
                                <div style="font-weight: bold; margin-bottom: 5px; font-size: 11px; text-decoration: underline; color: #080;">FINAL ADJUSTED RECEIPT</div>
                                <table class="items-table">
                                    <thead>
                                        <tr>
                                            <th class="item-name">ITEM</th>
                                            <th class="item-qty">NET QTY</th>
                                            <th class="item-total">NET TOTAL</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${receiptData.items.map(item => {
                                            const netQty = parseInt(item.quantity) - (parseInt(item.returned_quantity) || 0);
                                            if (netQty <= 0) return '';
                                            return `
                                                <tr>
                                                    <td class="item-name">${item.product_name}</td>
                                                    <td class="item-qty">${netQty}</td>
                                                    <td class="item-total">${formatCurrency(parseFloat(item.unit_price) * netQty)}</td>
                                                </tr>
                                            `;
                                        }).join('')}
                                    </tbody>
                                </table>

                                <div class="total-section">
                                    <div class="total-line final-total" style="color: #166534;">
                                        <span>💰 NET TOTAL:</span>
                                        <span>${formatCurrency(netTotal)} EGP</span>
                                    </div>
                                </div>
                            `;
                        }
                    })()}
                    
                    <div class="payment-info">
                        <div><strong>Payment Method:</strong> ${receiptData.payment_method || 'Cash'}</div>
                        <div><strong>Final Paid:</strong> ${formatCurrency(receiptData.items.reduce((sum, item) => sum + (parseFloat(item.unit_price) * (parseInt(item.quantity) - (parseInt(item.returned_quantity) || 0))), 0))} EGP</div>
                    </div>
                    
                    <div class="barcode-section">
                        <div style="font-size: 10px; margin-bottom: 5px;">📱 Scan for Receipt Details</div>
                        <img src="${barcodeUrl}" alt="Barcode" class="barcode-image" />
                        <div class="barcode-text">ID: ${receiptId}</div>
                    </div>
                    
                    <div class="footer">
                        <div>✨ Thank you for your business! ✨</div>
                        <div>🛍️ Visit us again soon</div>
                    </div>
                </body>
                </html>
            `;
        }

        // Reports Management State
        let salesTrendChart = null;
        let paymentMethodChart = null;
        let allReportSales = [];
        let allReportProducts = [];

        async function loadReports() {
            // Default range: Current Month
            const now = new Date();
            const firstDay = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
            const lastDay = now.toISOString().split('T')[0];

            document.getElementById('report-date-from').value = firstDay;
            document.getElementById('report-date-to').value = lastDay;

            await applyReportFilter();
        }

        async function resetReportFilter() {
            const now = new Date();
            const firstDay = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
            const lastDay = now.toISOString().split('T')[0];

            document.getElementById('report-date-from').value = firstDay;
            document.getElementById('report-date-to').value = lastDay;

            await applyReportFilter();
        }

        async function applyReportFilter() {
            const fromDate = document.getElementById('report-date-from').value;
            const toDate = document.getElementById('report-date-to').value;

            try {
                // Fetch all required data from enhanced endpoints
                const [salesRes, statsRes, topRes] = await Promise.all([
                    fetch(`api/sales.php?date_from=${fromDate}&date_to=${toDate}`),
                    fetch(`api/sales.php?stats=1&date_from=${fromDate}&date_to=${toDate}`),
                    fetch(`api/sales.php?top_products=1&date_from=${fromDate}&date_to=${toDate}`)
                ]);

                const salesResult = await salesRes.json();
                const statsResult = await statsRes.json();
                const topResult = await topRes.json();

                if (salesResult.success && statsResult.success && topResult.success) {
                    const filteredSales = salesResult.data;
                    const stats = statsResult.data;
                    const topProducts = topResult.data;

                    updateReportMetrics(filteredSales, stats);
                    updateReportsCharts(filteredSales, stats, fromDate, toDate);
                    updateTopProducts(topProducts);
                }
            } catch (error) {
                console.error('Error applying report filter:', error);
            }
        }

        function updateReportMetrics(sales, stats) {
            const totalTransactions = stats.total_transactions || 0;
            const totalRevenue = stats.total_revenue || 0;
            
            let totalSoldUnits = 0;
            let cashCollected = stats.payment_breakdown ? (stats.payment_breakdown['Cash'] || 0) : 0;
            let returnsCount = 0;
            let returnedAmount = 0;
            let returnedUnits = 0;

            sales.forEach(sale => {
                totalSoldUnits += parseInt(sale.item_count || 0);
                returnedUnits += parseInt(sale.returned_units || 0);
                returnedAmount += parseFloat(sale.returned_amount || 0);
                
                if (sale.status === 'returned' || sale.status === 'partially_returned' || sale.returned_units > 0) {
                    returnsCount++;
                }
            });

            const avgInvoice = totalTransactions > 0 ? (totalRevenue / totalTransactions) : 0;

            // Update UI
            document.getElementById('rep-total-transactions').innerText = totalTransactions.toLocaleString();
            document.getElementById('rep-total-revenue').innerText = formatCurrency(totalRevenue) + ' EGP';
            document.getElementById('rep-sold-units').innerText = totalSoldUnits.toLocaleString();
            document.getElementById('rep-cash-collected').innerText = formatCurrency(cashCollected) + ' EGP';
            document.getElementById('rep-returns-count').innerText = returnsCount.toLocaleString();
            document.getElementById('rep-avg-invoice').innerText = formatCurrency(avgInvoice) + ' EGP';

            // Update Returns Summary Card
            document.getElementById('rep-ret-sales').innerText = returnsCount.toLocaleString();
            document.getElementById('rep-ret-units').innerText = returnedUnits.toLocaleString();
            document.getElementById('rep-ret-amount').innerText = formatCurrency(returnedAmount) + ' EGP';
        }

        function updateReportsCharts(sales, stats, fromDate, toDate) {
            // 1. Sales Trend (Daily Revenue)
            const dailyData = {};
            const startDate = new Date(fromDate);
            const endDate = new Date(toDate);
            
            // Initialize every day in range
            for (let d = new Date(startDate); d <= endDate; d.setDate(d.getDate() + 1)) {
                dailyData[d.toISOString().split('T')[0]] = 0;
            }

            sales.forEach(s => {
                if (!s.sale_date) return;
                const date = s.sale_date.split(' ')[0];
                if (dailyData[date] !== undefined) {
                    dailyData[date] += parseFloat(s.total_amount || 0);
                }
            });

            const trendLabels = Object.keys(dailyData);
            const trendValues = Object.values(dailyData);

            if (salesTrendChart) salesTrendChart.destroy();
            const trendCtx = document.getElementById('salesTrendChart').getContext('2d');
            
            // Create Gradient
            const gradient = trendCtx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, 'rgba(59, 130, 246, 0.4)');
            gradient.addColorStop(1, 'rgba(59, 130, 246, 0)');

            salesTrendChart = new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: trendLabels,
                    datasets: [{
                        label: 'Revenue (EGP)',
                        data: trendValues,
                        borderColor: '#3b82f6',
                        backgroundColor: gradient,
                        fill: true,
                        tension: 0.4,
                        borderWidth: 4,
                        pointRadius: 0,
                        pointHoverRadius: 6,
                        pointHoverBackgroundColor: '#3b82f6',
                        pointHoverBorderColor: '#fff',
                        pointHoverBorderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { intersect: false, mode: 'index' },
                    plugins: { 
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(255, 255, 255, 0.9)',
                            titleColor: '#1e293b',
                            bodyColor: '#1e293b',
                            borderColor: '#e2e8f0',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return formatCurrency(context.parsed.y) + ' EGP';
                                }
                            }
                        }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            grid: { color: '#f1f5f9', drawBorder: false },
                            ticks: { font: { size: 11 }, color: '#64748b' }
                        },
                        x: { 
                            grid: { display: false },
                            ticks: { font: { size: 11 }, color: '#64748b' }
                        }
                    }
                }
            });

            // 2. Payment Method Breakdown (Using accurate stats from server)
            const paymentData = stats.payment_breakdown || {};
            const totalAmount = stats.total_revenue || 0;
            const pieLabels = Object.keys(paymentData);
            const pieValues = pieLabels.map(l => paymentData[l]);

            if (paymentMethodChart) paymentMethodChart.destroy();
            const pieCtx = document.getElementById('paymentMethodChart').getContext('2d');
            paymentMethodChart = new Chart(pieCtx, {
                type: 'doughnut',
                data: {
                    labels: pieLabels,
                    datasets: [{
                        data: pieValues,
                        backgroundColor: ['#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#64748b']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 15 } } }
                }
            });

            // Populate Breakdown Table
            const tbody = document.getElementById('payment-breakdown-body');
            tbody.innerHTML = pieLabels.map(label => {
                const amount = paymentData[label];
                const pct = totalAmount > 0 ? ((amount / totalAmount) * 100).toFixed(1) : 0;
                return `
                    <tr>
                        <td><strong>${label}</strong></td>
                        <td>-</td>
                        <td>${formatCurrency(amount)}</td>
                        <td><span class="badge-status info">${pct}%</span></td>
                    </tr>
                `;
            }).join('');
        }


        function updateTopProducts(topProducts) {
            const tbody = document.getElementById('top-selling-products-body');
            if (!topProducts || topProducts.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: #94a3b8; padding: 40px;">No product data available for this period.</td></tr>`;
                return;
            }

            const totalRevenue = topProducts.reduce((sum, p) => sum + parseFloat(p.total_revenue || 0), 0);
            
            const avatarColors = ['#eff6ff', '#f0fdf4', '#faf5ff', '#fefce8', '#fef2f2', '#f0f9ff'];
            const textColors = ['#2563eb', '#16a34a', '#9333ea', '#ca8a04', '#dc2626', '#0891b2'];

            tbody.innerHTML = topProducts.map((p, idx) => {
                const share = totalRevenue > 0 ? ((parseFloat(p.total_revenue) / totalRevenue) * 100).toFixed(1) : 0;
                const colorIdx = idx % avatarColors.length;
                const initials = (p.product_name || 'P').split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
                
                return `
                    <tr>
                        <td style="width: 120px;"><code style="background: #f1f5f9; padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; color: #475569;">${p.product_code || 'N/A'}</code></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 36px; height: 36px; border-radius: 10px; background: ${p.image_url ? 'none' : avatarColors[colorIdx]}; color: ${textColors[colorIdx]}; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 12px; border: 1px solid rgba(0,0,0,0.03); overflow: hidden;">
                                    ${p.image_url ? `<img src="${p.image_url}" style="width: 100%; height: 100%; object-fit: cover;">` : initials}
                                </div>
                                <div style="font-weight: 700; color: #1e293b;">${p.product_name}</div>
                            </div>
                        </td>
                        <td class="text-right" style="font-weight: 600;">${parseInt(p.total_units).toLocaleString()} <span style="font-size: 11px; color: #94a3b8; font-weight: 400;">units</span></td>
                        <td class="text-right" style="font-weight: 800; color: #16a34a; font-size: 15px;">${formatCurrency(p.total_revenue)}</td>
                        <td class="text-right">
                            <div style="display: inline-flex; flex-direction: column; align-items: flex-end; gap: 4px;">
                                <span class="badge-status success" style="font-weight: 700;">${share}%</span>
                                <div style="width: 60px; height: 4px; background: #f1f5f9; border-radius: 2px; overflow: hidden;">
                                    <div style="width: ${share}%; height: 100%; background: #16a34a;"></div>
                                </div>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function printBranchReport() {
            const fromDate = document.getElementById('report-date-from').value;
            const toDate = document.getElementById('report-date-to').value;
            
            const metrics = {
                transactions: document.getElementById('rep-total-transactions').innerText,
                revenue: document.getElementById('rep-total-revenue').innerText,
                units: document.getElementById('rep-sold-units').innerText,
                cash: document.getElementById('rep-cash-collected').innerText,
                returns: document.getElementById('rep-returns-count').innerText,
                avg: document.getElementById('rep-avg-invoice').innerText
            };

            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Branch Report: ${fromDate} - ${toDate}</title>
                    <style>
                        @page { size: A4; margin: 15mm; }
                        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; line-height: 1.4; color: #333; }
                        .header { text-align: center; border-bottom: 3px solid #1e40af; padding-bottom: 20px; margin-bottom: 30px; }
                        .company { font-size: 28px; font-weight: bold; color: #1e40af; margin-bottom: 5px; }
                        .report-title { font-size: 20px; color: #64748b; font-weight: 600; }
                        .period { font-size: 14px; color: #94a3b8; margin-top: 5px; }
                        
                        .section-title { font-size: 16px; font-weight: bold; border-left: 5px solid #1e40af; padding-left: 10px; margin: 30px 0 15px 0; color: #1e293b; background: #f8fafc; padding: 8px 10px; }
                        
                        .metrics-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 30px; }
                        .metric-box { border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px; text-align: center; }
                        .metric-label { font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase; }
                        .metric-value { font-size: 20px; font-weight: 800; color: #1e40af; margin-top: 5px; }
                        
                        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px; }
                        th { background: #f8fafc; text-align: left; padding: 12px 10px; border-bottom: 2px solid #e2e8f0; color: #475569; }
                        td { padding: 10px; border-bottom: 1px solid #f1f5f9; }
                        .text-right { text-align: right; }
                        
                        .footer { margin-top: 80px; display: flex; justify-content: space-between; }
                        .signature { border-top: 1px solid #000; width: 220px; text-align: center; padding-top: 8px; font-weight: bold; }
                        
                        @media print {
                            .no-print { display: none; }
                            body { padding: 0; }
                        }
                    </style>
                </head>
                <body onload="window.print();">
                    <div class="header">
                        <div class="company">📱 IBS MOBILE SHOP</div>
                        <div class="report-title">Branch Operational Report</div>
                        <div class="period">Authorized Report for Period: ${fromDate} TO ${toDate}</div>
                    </div>

                    <div class="section-title">📊 EXECUTIVE SUMMARY</div>
                    <div class="metrics-grid">
                        <div class="metric-box"><div class="metric-label">Transactions</div><div class="metric-value">${metrics.transactions}</div></div>
                        <div class="metric-box"><div class="metric-label">Total Revenue</div><div class="metric-value">${metrics.revenue}</div></div>
                        <div class="metric-box"><div class="metric-label">Sold Units</div><div class="metric-value">${metrics.units}</div></div>
                        <div class="metric-box"><div class="metric-label">Cash Collected</div><div class="metric-value">${metrics.cash}</div></div>
                        <div class="metric-box"><div class="metric-label">Returns</div><div class="metric-value">${metrics.returns}</div></div>
                        <div class="metric-box"><div class="metric-label">Avg. Invoice</div><div class="metric-value">${metrics.avg}</div></div>
                    </div>

                    <div class="section-title">🏆 TOP SELLING PRODUCTS</div>
                    <table>
                        <thead>
                            <tr>
                                <th>CODE</th>
                                <th>PRODUCT NAME</th>
                                <th class="text-right">UNITS</th>
                                <th class="text-right">REVENUE</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${document.getElementById('top-selling-products-body').innerHTML.replace(/badge-status success/g, '').replace(/<code.*?>/g, '').replace(/<\/code>/g, '')}
                        </tbody>
                    </table>

                    <div class="section-title">💳 PAYMENT BREAKDOWN</div>
                    <table>
                        <thead>
                            <tr>
                                <th>METHOD</th>
                                <th>COUNT</th>
                                <th class="text-right">TOTAL AMOUNT</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${document.getElementById('payment-breakdown-body').innerHTML.replace(/<span.*?>.*?<\/span>/g, '')}
                        </tbody>
                    </table>

                    <div class="footer">
                        <div class="signature">Branch Manager Signature</div>
                        <div class="signature">System Administrator</div>
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
        }

        function generateSalesReportHTML(sales, title, period) {
            const totalAmount = sales.reduce((sum, sale) => sum + sale.total_amount, 0);
            const totalTransactions = sales.length;

            return `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>${title}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #0056b3; padding-bottom: 20px; }
                        .company-name { font-size: 24px; font-weight: bold; color: #0056b3; margin-bottom: 5px; }
                        .report-title { font-size: 20px; color: #333; margin-bottom: 5px; }
                        .report-period { font-size: 16px; color: #666; }
                        .summary { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
                        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
                        .summary-item { text-align: center; }
                        .summary-value { font-size: 24px; font-weight: bold; color: #0056b3; }
                        .summary-label { color: #666; margin-top: 5px; }
                        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
                        .table th { background: #0056b3; color: white; font-weight: bold; }
                        .table tr:nth-child(even) { background: #f8f9fa; }
                        .total-row { font-weight: bold; background: #e3f2fd !important; }
                        .footer { margin-top: 40px; text-align: center; color: #666; border-top: 1px solid #ddd; padding-top: 20px; }
                        @media print { body { margin: 0; } .no-print { display: none; } }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <div class="company-name">IBS MOBILE SHOP</div>
                        <div class="report-title">${title}</div>
                        <div class="report-period">Period: ${period}</div>
                    </div>
                    
                    <div class="summary">
                        <div class="summary-grid">
                            <div class="summary-item">
                                <div class="summary-value">${totalTransactions}</div>
                                <div class="summary-label">Total Transactions</div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-value">$${totalAmount.toFixed(2)}</div>
                                <div class="summary-label">Total Sales Amount</div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-value">$${totalTransactions > 0 ? (totalAmount / totalTransactions).toFixed(2) : '0.00'}</div>
                                <div class="summary-label">Average Transaction</div>
                            </div>
                        </div>
                    </div>
                    
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Receipt #</th>
                                <th>Date & Time</th>
                                <th>Staff</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${sales.map(sale => `
                                <tr>
                                    <td>${sale.receipt_number}</td>
                                    <td>${new Date(sale.sale_date).toLocaleString()}</td>
                                    <td>${sale.staff_name}</td>
                                    <td>${sale.customer_name || 'Walk-in Customer'}</td>
                                    <td>${sale.items ? sale.items.length : 0}</td>
                                    <td>$${sale.total_amount.toFixed(2)}</td>
                                </tr>
                            `).join('')}
                            <tr class="total-row">
                                <td colspan="5"><strong>TOTAL</strong></td>
                                <td><strong>$${totalAmount.toFixed(2)}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="footer">
                        <div>Report generated on ${new Date().toLocaleString()}</div>
                        <div>IBS Mobile Shop - Sales Management System</div>
                    </div>
                </body>
                </html>
            `;
        }

        function generateProductsReportHTML(products) {
            const totalValue = products.reduce((sum, product) => sum + ((product.purchase_price || product.price || 0) * product.stock), 0);
            const totalItems = products.reduce((sum, product) => sum + product.stock, 0);

            return `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Products Inventory Report</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #4facfe; padding-bottom: 20px; }
                        .company-name { font-size: 24px; font-weight: bold; color: #4facfe; margin-bottom: 5px; }
                        .report-title { font-size: 20px; color: #333; margin-bottom: 5px; }
                        .summary { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
                        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
                        .summary-item { text-align: center; }
                        .summary-value { font-size: 24px; font-weight: bold; color: #4facfe; }
                        .summary-label { color: #666; margin-top: 5px; }
                        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        .table th, .table td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; font-size: 14px; }
                        .table th { background: #4facfe; color: white; font-weight: bold; }
                        .table tr:nth-child(even) { background: #f8f9fa; }
                        .low-stock { background: #ffebee !important; color: #c62828; }
                        .footer { margin-top: 40px; text-align: center; color: #666; border-top: 1px solid #ddd; padding-top: 20px; }
                        @media print { body { margin: 0; } }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <div class="company-name">IBS MOBILE SHOP</div>
                        <div class="report-title">Complete Products Inventory Report</div>
                        <div class="report-period">Generated on ${new Date().toLocaleDateString()}</div>
                    </div>
                    
                    <div class="summary">
                        <div class="summary-grid">
                            <div class="summary-item">
                                <div class="summary-value">${products.length}</div>
                                <div class="summary-label">Total Products</div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-value">${totalItems}</div>
                                <div class="summary-label">Total Items in Stock</div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-value">$${totalValue.toFixed(2)}</div>
                                <div class="summary-label">Total Inventory Value</div>
                            </div>
                        </div>
                    </div>
                    
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Brand</th>
                                <th>Model</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Min Stock</th>
                                <th>Value</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${products.map(product => `
                                <tr ${product.min_stock !== null && product.stock <= product.min_stock ? 'class="low-stock"' : ''}>
                                    <td>${product.code}</td>
                                    <td>${product.brand}</td>
                                    <td>${product.model}</td>
                                    <td>$${product.price.toFixed(2)}</td>
                                    <td>${product.stock}</td>
                                    <td>${product.min_stock || 'N/A'}</td>
                                    <td>$${((product.purchase_price || product.price || 0) * product.stock).toFixed(2)}</td>
                                    <td>${product.min_stock !== null && product.stock <= product.min_stock ? '⚠️ Low Stock' : '✅ In Stock'}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                    
                    <div class="footer">
                        <div>Report generated on ${new Date().toLocaleString()}</div>
                        <div>IBS Mobile Shop - Inventory Management System</div>
                    </div>
                </body>
                </html>
            `;
        }

        function generateLowStockReportHTML(lowStockItems) {
            return `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Low Stock Alert Report</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #fa709a; padding-bottom: 20px; }
                        .company-name { font-size: 24px; font-weight: bold; color: #fa709a; margin-bottom: 5px; }
                        .report-title { font-size: 20px; color: #333; margin-bottom: 5px; }
                        .alert-badge { background: #ffebee; color: #c62828; padding: 8px 16px; border-radius: 20px; font-weight: bold; display: inline-block; margin-top: 10px; }
                        .summary { background: #fff3e0; padding: 20px; border-radius: 8px; margin-bottom: 30px; border-left: 5px solid #ff9800; }
                        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
                        .table th { background: #fa709a; color: white; font-weight: bold; }
                        .table tr:nth-child(even) { background: #ffebee; }
                        .urgent { background: #ffcdd2 !important; font-weight: bold; }
                        .footer { margin-top: 40px; text-align: center; color: #666; border-top: 1px solid #ddd; padding-top: 20px; }
                        @media print { body { margin: 0; } }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <div class="company-name">IBS MOBILE SHOP</div>
                        <div class="report-title">⚠️ Low Stock Alert Report</div>
                        <div class="alert-badge">${lowStockItems.length} Items Require Attention</div>
                    </div>
                    
                    <div class="summary">
                        <h3 style="margin-top: 0; color: #e65100;">⚠️ URGENT: Items Below Minimum Stock Level</h3>
                        <p>The following items are at or below their minimum stock levels and require immediate restocking to avoid stockouts.</p>
                        <p><strong>Total Items Requiring Attention: ${lowStockItems.length}</strong></p>
                    </div>
                    
                    ${lowStockItems.length > 0 ? `
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Priority</th>
                                    <th>Code</th>
                                    <th>Product</th>
                                    <th>Current Stock</th>
                                    <th>Min Stock</th>
                                    <th>Shortage</th>
                                    <th>Unit Price</th>
                                    <th>Reorder Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${lowStockItems.map(product => {
                const shortage = Math.max(0, product.min_stock - product.stock);
                const reorderQty = product.min_stock + 10; // Suggest reordering to min + buffer
                const reorderValue = reorderQty * product.price;
                const isUrgent = product.stock === 0;

                return `
                                        <tr ${isUrgent ? 'class="urgent"' : ''}>
                                            <td>${isUrgent ? '🔴 OUT OF STOCK' : '🟡 LOW STOCK'}</td>
                                            <td>${product.code}</td>
                                            <td>${product.brand} ${product.model}</td>
                                            <td>${product.stock}</td>
                                            <td>${product.min_stock}</td>
                                            <td>${shortage}</td>
                                            <td>$${product.price.toFixed(2)}</td>
                                            <td>$${reorderValue.toFixed(2)} (${reorderQty} units)</td>
                                        </tr>
                                    `;
            }).join('')}
                            </tbody>
                        </table>
                    ` : `
                        <div style="text-align: center; padding: 40px; background: #e8f5e8; border-radius: 8px; color: #2e7d32;">
                            <h3>✅ All Products Are Adequately Stocked</h3>
                            <p>No items are currently below their minimum stock levels.</p>
                        </div>
                    `}
                    
                    <div class="footer">
                        <div>Report generated on ${new Date().toLocaleString()}</div>
                        <div>IBS Mobile Shop - Stock Management System</div>
                        <div style="margin-top: 10px; color: #e65100; font-weight: bold;">⚠️ Please review and restock items marked as urgent</div>
                    </div>
                </body>
                </html>
            `;
        }

        function openPrintWindow(content, title) {
            const printWindow = window.open('', '_blank', 'width=800,height=600');
            printWindow.document.write(content);
            printWindow.document.close();
            printWindow.focus();

            // Auto-print after a short delay to ensure content is loaded
            setTimeout(() => {
                printWindow.print();
            }, 500);
        }

        // Print Product Label Function
        function printProductLabel(productId) {
            const product = allInventoryProducts.find(p => p.id === productId);
            if (!product) {
                alert('Product not found');
                return;
            }

            const labelContent = generateProductLabelHTML(product);
            const printWindow = window.open('', '_blank', 'width=400,height=300');
            printWindow.document.write(labelContent);
            printWindow.document.close();
            printWindow.focus();

            // Auto-print after a short delay
            setTimeout(() => {
                printWindow.print();
            }, 500);
        }

        function generateProductLabelHTML(product) {
            const barcode = product.barcode || 'No Barcode';
            const barcodeImage = product.barcode ? 
                `<img src="https://barcode.tec-it.com/barcode.ashx?data=${product.barcode}&code=Code128&multiplebarcodes=false&translate-esc=false&unit=Fit&dpi=96&imagetype=Gif&rotation=0&color=%23000000&bgcolor=%23ffffff&qunit=Mm&quiet=0" 
                     alt="Barcode" style="width: 200px; height: 60px; margin: 8px 0;" />` :
                `<div style="font-family: 'Courier New', monospace; font-size: 18px; font-weight: bold; margin: 8px 0; letter-spacing: 2px;">${product.code}</div>`;
            
            return `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Product Label - ${product.code}</title>
                    <style>
                        @page {
                            size: 4in 3in;
                            margin: 0.1in;
                        }

                        body {
                            font-family: Arial, sans-serif;
                            margin: 0;
                            padding: 15px;
                            width: 3.8in;
                            height: 2.8in;
                            border: 2px solid #000;
                            box-sizing: border-box;
                            display: flex;
                            flex-direction: column;
                            justify-content: center;
                            align-items: center;
                            text-align: center;
                        }

                        .company-name {
                            font-size: 16px;
                            font-weight: bold;
                            color: #333;
                            margin-bottom: 10px;
                            border-bottom: 1px solid #ccc;
                            padding-bottom: 6px;
                            width: 100%;
                        }

                        .product-code {
                            font-size: 20px;
                            font-weight: bold;
                            color: #000;
                            margin-bottom: 6px;
                            letter-spacing: 1px;
                        }

                        .product-name {
                            font-size: 18px;
                            font-weight: bold;
                            color: #333;
                            margin-bottom: 6px;
                            line-height: 1.2;
                        }

                        .price {
                            font-size: 16px;
                            font-weight: bold;
                            color: #28a745;
                            margin-bottom: 8px;
                        }

                        .barcode-container {
                            margin: 10px 0;
                            padding: 8px;
                            border: 1px dashed #ccc;
                            background: #f9f9f9;
                            border-radius: 4px;
                        }

                        .barcode-text {
                            font-family: 'Courier New', monospace;
                            font-size: 14px;
                            font-weight: bold;
                            color: #000;
                            margin-top: 4px;
                            letter-spacing: 1px;
                        }

                        .no-barcode {
                            color: #999;
                            font-style: italic;
                        }

                        @media print {
                            body {
                                width: auto;
                                height: auto;
                            }
                            
                            .barcode-container {
                                background: white;
                                border-color: #000;
                            }
                        }
                    </style>
                </head>
                <body>
                    <div class="company-name">IBS MOBILE SHOP</div>

                    <div class="product-code">${product.code}</div>

                    <div class="product-name">${product.brand} ${product.model}</div>

                    <div class="price">${formatCurrency(product.suggested_price || product.price || 0)} EGP</div>

                    <div class="barcode-container">
                        ${barcodeImage}
                        <div class="barcode-text ${!product.barcode ? 'no-barcode' : ''}">${barcode}</div>
                    </div>
                </body>
                </html>
            `;
        }

        // Income Management Functions
        let allIncomeEntries = [];

        async function loadIncome() {
            try {
                console.log('=== LOADING INCOME DATA ===');
                const response = await fetch('api/income.php');
                console.log('Income API Response status:', response.status);
                
                if (response.status === 403) {
                    console.warn('Access denied to income API - insufficient permissions');
                    const tbody = document.getElementById('income-tbody');
                    if (tbody) {
                        tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: orange;">Access denied: Only Admin and Owner can view income entries</td></tr>`;
                    }
                    return;
                }
                
                const result = await response.json();
                console.log('Income API Response result:', result);
                
                if (result.success) {
                    allIncomeEntries = result.data || [];
                    console.log('Income data loaded successfully:', allIncomeEntries.length, 'entries');
                    if (allIncomeEntries.length > 0) {
                        console.log('Sample income entry:', allIncomeEntries[0]);
                    }
                    displayIncome(allIncomeEntries);
                    displayIncomeStats(allIncomeEntries);
                } else {
                    console.error('Failed to load income entries:', result.message);
                    const tbody = document.getElementById('income-tbody');
                    if (tbody) {
                        tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: red;">Failed to load income: ${result.message}</td></tr>`;
                    }
                }
            } catch (error) {
                console.error('Error loading income entries:', error);
                const tbody = document.getElementById('income-tbody');
                if (tbody) {
                    tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: red;">Error loading income data</td></tr>`;
                }
            }
        }

        function displayIncome(incomeEntries) {
            const tbody = document.getElementById('income-tbody');
            if (!tbody) {
                console.warn('Income tbody element not found');
                return;
            }
            
            if (!incomeEntries || incomeEntries.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No income entries found</td></tr>';
                return;
            }
            
            tbody.innerHTML = incomeEntries.map(entry => `
                <tr>
                    <td>${new Date(entry.entry_date).toLocaleDateString()}</td>
                    <td>${entry.created_by_name || 'Unknown'}</td>
                    <td>${entry.description || 'No description'}</td>
                    <td>${formatCurrency(entry.price || 0)} EGP</td>
                    <td>
                        <button class="btn btn-sm" onclick="printFinancialEntry('income', ${entry.id})" style="padding: 3px 8px; font-size: 12px; margin-right: 5px; background: #64748b; color: white;">
                            🖨️ Print
                        </button>
                        <button class="btn btn-sm" onclick="editIncomeEntry(${entry.id})" style="padding: 3px 8px; font-size: 12px; margin-right: 5px;">
                            ✏️ Edit
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="deleteIncomeEntry(${entry.id})" style="padding: 3px 8px; font-size: 12px;">
                            🗑️ Delete
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        function displayIncomeStats(incomeEntries) {
            const totalIncome = (incomeEntries || []).reduce((sum, entry) => sum + (entry.price || 0), 0);
            
            // Update the unified summary card
            const revStatTotal = document.getElementById('stat-total-revenue');
            if (revStatTotal) revStatTotal.innerText = formatCurrency(totalIncome) + ' EGP';
            
            updateUnifiedFinancialStats();
        }

        function updateUnifiedFinancialStats() {
            const totalRevenue = (allIncomeEntries || []).reduce((sum, entry) => sum + (entry.price || 0), 0);
            const totalExpenses = (allPaymentEntries || []).reduce((sum, entry) => sum + (entry.price || 0), 0);
            const netBalance = totalRevenue - totalExpenses;
            const totalEntries = (allIncomeEntries || []).length + (allPaymentEntries || []).length;

            const balStat = document.getElementById('stat-net-balance');
            if (balStat) {
                balStat.innerText = formatCurrency(netBalance) + ' EGP';
                balStat.style.color = netBalance >= 0 ? 'var(--fin-revenue, #10b981)' : 'var(--fin-expense, #ef4444)';
            }

            const entriesStat = document.getElementById('stat-fin-entries');
            if (entriesStat) entriesStat.innerText = totalEntries;
        }

        async function deleteIncomeEntry(entryId) {
            if (!confirm('Are you sure you want to delete this income entry?')) {
                return;
            }

            try {
                const response = await fetch(`api/income.php?id=${entryId}`, {
                    method: 'DELETE'
                });

                console.log('Delete income response status:', response.status);

                if (!response.ok) {
                    let errorText = '';
                    try {
                        errorText = await response.text();
                        // Try to parse as JSON
                        try {
                            const errorJson = JSON.parse(errorText);
                            alert('Server error: ' + (errorJson.message || errorText));
                        } catch (parseError) {
                            alert('Server error: ' + response.status + ' - ' + (errorText.substring(0, 200) || 'Unknown error'));
                        }
                    } catch (e) {
                        alert('Server error: ' + response.status + ' - Failed to read error message');
                    }
                    return;
                }

                const responseText = await response.text();
                console.log('Raw delete income response:', responseText);

                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response text:', responseText);
                    alert('Error: Invalid JSON response from server. Check console for details.');
                    return;
                }

                if (result.success) {
                    alert('Income entry deleted successfully!');
                    loadIncome(); // Refresh the list
                    loadReports(); // Refresh profit calculations
                } else {
                    alert('Failed to delete income entry: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error deleting income entry:', error);
                alert('Error deleting income entry: ' + error.message);
            }
        }

        function editIncomeEntry(entryId) {
            const entry = allIncomeEntries.find(e => e.id === entryId);
            if (!entry) {
                alert('Income entry not found');
                return;
            }

            // For now, use a simple prompt. In a real application, you'd want a modal
            const newPrice = prompt('Enter new price:', entry.price);
            const newDescription = prompt('Enter new description:', entry.description);

            if (newPrice === null || newDescription === null) {
                return; // User cancelled
            }

            const price = parseFloat(newPrice);
            const description = newDescription.trim();

            if (isNaN(price) || price <= 0) {
                alert('Please enter a valid price greater than 0');
                return;
            }

            if (!description) {
                alert('Description cannot be empty');
                return;
            }

            // Update the entry
            updateIncomeEntry(entryId, price, description);
        }

        async function updateIncomeEntry(entryId, price, description) {
            try {
                const response = await fetch('api/income.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: entryId,
                        price: price,
                        description: description
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alert('Income entry updated successfully!');
                    loadIncome(); // Refresh the list
                } else {
                    alert('Failed to update income entry: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error updating income entry:', error);
                alert('Error updating income entry. Please try again.');
            }
        }

        // Payment Management Functions
        let allPaymentEntries = [];

        async function loadPayment() {
            try {
                console.log('=== LOADING PAYMENT DATA ===');
                const response = await fetch('api/payment.php');
                console.log('Payment API Response status:', response.status);
                
                if (response.status === 403) {
                    console.warn('Access denied to payment API - insufficient permissions');
                    const tbody = document.getElementById('payment-tbody');
                    if (tbody) {
                        tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: orange;">Access denied: Only Admin and Owner can view payment entries</td></tr>`;
                    }
                    return;
                }
                
                const result = await response.json();
                console.log('Payment API Response result:', result);
                console.log('Payment API Data type:', typeof result.data);
                console.log('Payment API Data length:', result.data ? result.data.length : 'undefined');
                
                if (result.success) {
                    allPaymentEntries = result.data || [];
                    console.log('Payment data loaded successfully:', allPaymentEntries.length, 'entries');
                    if (allPaymentEntries.length > 0) {
                        console.log('Sample payment entry:', allPaymentEntries[0]);
                    }
                    displayPayment(allPaymentEntries);
                    displayPaymentStats(allPaymentEntries);
                } else {
                    console.error('Failed to load payment entries:', result.message);
                    const tbody = document.getElementById('payment-tbody');
                    if (tbody) {
                        tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: red;">Failed to load payments: ${result.message}</td></tr>`;
                    }
                }
            } catch (error) {
                console.error('Error loading payment entries:', error);
                const tbody = document.getElementById('payment-tbody');
                if (tbody) {
                    tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: red;">Error loading payment data</td></tr>`;
                }
            }
        }

        function displayPayment(paymentEntries) {
            const tbody = document.getElementById('payment-tbody');
            if (!tbody) {
                console.warn('Payment tbody element not found');
                return;
            }
            
            if (!paymentEntries || paymentEntries.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No payment entries found</td></tr>';
                return;
            }
            
            tbody.innerHTML = paymentEntries.map(entry => `
                <tr>
                    <td>${new Date(entry.entry_date).toLocaleDateString()}</td>
                    <td>${entry.created_by_name || 'Unknown'}</td>
                    <td>${entry.description || 'No description'}</td>
                    <td>${formatCurrency(entry.price || 0)} EGP</td>
                    <td>
                        <button class="btn btn-sm" onclick="printFinancialEntry('payment', ${entry.id})" style="padding: 3px 8px; font-size: 12px; margin-right: 5px; background: #64748b; color: white;">
                            🖨️ Print
                        </button>
                        <button class="btn btn-sm" onclick="editPaymentEntry(${entry.id})" style="padding: 3px 8px; font-size: 12px; margin-right: 5px;">
                            ✏️ Edit
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="deletePaymentEntry(${entry.id})" style="padding: 3px 8px; font-size: 12px;">
                            🗑️ Delete
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        function displayPaymentStats(paymentEntries) {
            const totalPayments = (paymentEntries || []).reduce((sum, entry) => sum + (entry.price || 0), 0);
            
            // Update the unified summary card
            const expStatTotal = document.getElementById('stat-total-expenses');
            if (expStatTotal) expStatTotal.innerText = formatCurrency(totalPayments) + ' EGP';
            
            updateUnifiedFinancialStats();
        }

        async function deletePaymentEntry(entryId) {
            if (!confirm('Are you sure you want to delete this payment entry?')) {
                return;
            }

            try {
                const response = await fetch(`api/payment.php?id=${entryId}`, {
                    method: 'DELETE'
                });

                console.log('Delete payment response status:', response.status);

                if (!response.ok) {
                    let errorText = '';
                    try {
                        errorText = await response.text();
                        // Try to parse as JSON
                        try {
                            const errorJson = JSON.parse(errorText);
                            alert('Server error: ' + (errorJson.message || errorText));
                        } catch (parseError) {
                            alert('Server error: ' + response.status + ' - ' + (errorText.substring(0, 200) || 'Unknown error'));
                        }
                    } catch (e) {
                        alert('Server error: ' + response.status + ' - Failed to read error message');
                    }
                    return;
                }

                const responseText = await response.text();
                console.log('Raw delete payment response:', responseText);

                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response text:', responseText);
                    alert('Error: Invalid JSON response from server. Check console for details.');
                    return;
                }

                if (result.success) {
                    alert('Payment entry deleted successfully!');
                    loadPayment(); // Refresh the list
                    loadReports(); // Refresh profit calculations
                } else {
                    alert('Failed to delete payment entry: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error deleting payment entry:', error);
                alert('Error deleting payment entry: ' + error.message);
            }
        }

        function printFinancialEntry(type, entryId) {
            const entry = type === 'income' 
                ? allIncomeEntries.find(e => e.id === entryId)
                : allPaymentEntries.find(e => e.id === entryId);

            if (!entry) {
                alert('Entry not found');
                return;
            }

            const typeLabel = type === 'income' ? 'Operating Revenue' : 'Operating Expenses';
            const typeLabelAr = type === 'income' ? 'الإيرادات التشغيلية' : 'المصروفات التشغيلية';
            const date = new Date(entry.entry_date).toLocaleString();
            const receiptId = `#FIN-${type.toUpperCase()}-${entry.id}`;

            const printWindow = window.open('', '_blank', 'width=400,height=600');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Financial Receipt - ${entryId}</title>
                    <style>
                        @page { 
                            size: 80mm auto; 
                            margin: 5mm; 
                        }
                        body { 
                            font-family: 'Courier New', monospace; 
                            width: 80mm; 
                            margin: 0 auto; 
                            padding: 10px; 
                            font-size: 12px;
                            line-height: 1.2;
                            color: #000;
                        }
                        .header { 
                            text-align: center; 
                            margin-bottom: 15px; 
                            border-bottom: 2px dashed #000; 
                            padding-bottom: 10px; 
                        }
                        .company-name { 
                            font-size: 16px; 
                            font-weight: bold; 
                            margin-bottom: 5px;
                        }
                        .company-address { 
                            font-size: 10px; 
                            margin-bottom: 5px;
                        }
                        .receipt-info { 
                            margin-bottom: 15px; 
                            font-size: 11px;
                            border-bottom: 1px dashed #000;
                            padding-bottom: 10px;
                        }
                        .receipt-info div { 
                            margin-bottom: 3px;
                            display: flex;
                            justify-content: space-between;
                        }
                        .details-section {
                            margin-bottom: 15px;
                        }
                        .details-label {
                            font-weight: bold;
                            margin-bottom: 5px;
                            text-decoration: underline;
                        }
                        .details-content {
                            font-size: 11px;
                            background: #fff;
                            padding: 5px;
                            border: 1px dotted #000;
                            min-height: 30px;
                            margin-bottom: 10px;
                        }
                        .amount-section { 
                            border-top: 2px solid #000; 
                            padding-top: 10px;
                            margin-top: 10px;
                            text-align: center;
                        }
                        .amount-label {
                            font-weight: bold;
                            font-size: 14px;
                        }
                        .amount-value {
                            font-weight: bold;
                            font-size: 20px;
                            display: block;
                            margin-top: 5px;
                        }
                        .footer { 
                            text-align: center; 
                            margin-top: 15px; 
                            border-top: 1px dashed #000; 
                            padding-top: 10px; 
                            font-size: 10px;
                        }
                        .rtl { direction: rtl; font-family: 'Arial', sans-serif; }
                        @media print { 
                            body { width: auto; margin: 0; }
                        }
                    </style>
                </head>
                <body onload="window.print(); window.close();">
                    <div class="header">
                        <div class="company-name">📱 IBS MOBILE SHOP</div>
                        <div class="company-address">Mobile & Electronics Store</div>
                        <div class="company-address">📍 Egypt - Cairo</div>
                    </div>
                    
                    <div style="text-align: center; font-weight: bold; margin-bottom: 10px; font-size: 14px;">FINANCIAL RECEIPT</div>

                    <div class="receipt-info">
                        <div><span>🧾 ID:</span> <span>${receiptId}</span></div>
                        <div><span>📅 DATE:</span> <span>${date}</span></div>
                        <div><span>👤 STAFF:</span> <span>${entry.created_by_name || 'Admin'}</span></div>
                        <div class="rtl"><span>📌 Category:</span> <span>${typeLabelAr}</span></div>
                        <div><span>📌 Type:</span> <span>${typeLabel}</span></div>
                    </div>

                    <div class="details-section">
                        <div class="details-label">DESCRIPTION / الوصف:</div>
                        <div class="details-content">${entry.description || 'N/A'}</div>
                    </div>

                    <div class="amount-section">
                        <span class="amount-label">TOTAL AMOUNT / الإجمالي:</span>
                        <span class="amount-value">${formatCurrency(entry.price || 0)} EGP</span>
                    </div>

                    <div class="footer">
                        Thank you for using IBS System<br>
                        شكراً لاستخدامكم نظام آي بي إس
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
        }

        function editPaymentEntry(entryId) {
            const entry = allPaymentEntries.find(e => e.id === entryId);
            if (!entry) {
                alert('Payment entry not found');
                return;
            }

            // For now, use a simple prompt. In a real application, you'd want a modal
            const newPrice = prompt('Enter new amount:', entry.price);
            const newDescription = prompt('Enter new description:', entry.description);

            if (newPrice === null || newDescription === null) {
                return; // User cancelled
            }

            const price = parseFloat(newPrice);
            const description = newDescription.trim();

            if (isNaN(price) || price <= 0) {
                alert('Please enter a valid amount greater than 0');
                return;
            }

            if (!description) {
                alert('Description cannot be empty');
                return;
            }

            // Update the entry
            updatePaymentEntry(entryId, price, description);
        }

        async function updatePaymentEntry(entryId, price, description) {
            try {
                const response = await fetch('api/payment.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: entryId,
                        price: price,
                        description: description
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alert('Payment entry updated successfully!');
                    loadPayment(); // Refresh the list
                    loadReports(); // Refresh profit calculations
                } else {
                    alert('Failed to update payment entry: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error updating payment entry:', error);
                alert('Error updating payment entry. Please try again.');
            }
        }

        // IMEI validation and form handling
        document.addEventListener('DOMContentLoaded', function() {
            // IMEI field validation
            const imeiField = document.getElementById('imei-field');
            const hasImeiSelect = document.getElementById('has-imei-select');
            
            if (hasImeiSelect && imeiField) {
                hasImeiSelect.addEventListener('change', function() {
                    if (this.value === '1') {
                        imeiField.required = true;
                        imeiField.placeholder = 'IMEI is required (15 digits)';
                    } else {
                        imeiField.required = false;
                        imeiField.placeholder = 'Enter IMEI number (for mobile devices)';
                        imeiField.value = '';
                    }
                });
                
                // View stock items for a product
        async function viewStockItems(productId) {
            console.log('Viewing stock items for product:', productId);
            try {
                const response = await fetch(`api/stock_items.php?product_id=${productId}`);
                console.log('Response status:', response.status);
                const result = await response.json();
                console.log('API result:', result);
                
                if (result.success) {
                    displayStockItemsModal(result.data);
                } else {
                    alert('Failed to load stock items: ' + result.message);
                }
            } catch (error) {
                console.error('Error loading stock items:', error);
                alert('Error loading stock items: ' + error.message);
            }
        }
        
        function displayStockItemsModal(stockItems) {
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 1000;
            `;
            
            const modalContent = document.createElement('div');
            modalContent.style.cssText = `
                background: white;
                padding: 20px;
                border-radius: 10px;
                max-width: 800px;
                max-height: 80vh;
                overflow-y: auto;
                width: 90%;
            `;
            
            const availableItems = stockItems.filter(item => item.status === 'available');
            const soldItems = stockItems.filter(item => item.status === 'sold');
            const reservedItems = stockItems.filter(item => item.status === 'reserved');
            
            modalContent.innerHTML = `
                <h2>Stock Items Management</h2>
                <div style="margin-bottom: 20px;">
                    <span style="background: #4CAF50; color: white; padding: 5px 10px; border-radius: 5px; margin-right: 10px;">Available: ${availableItems.length}</span>
                    <span style="background: #2196F3; color: white; padding: 5px 10px; border-radius: 5px; margin-right: 10px;">Sold: ${soldItems.length}</span>
                    <span style="background: #FF9800; color: white; padding: 5px 10px; border-radius: 5px; margin-right: 10px;">Reserved: ${reservedItems.length}</span>
                    <span style="background: #9E9E9E; color: white; padding: 5px 10px; border-radius: 5px;">Total: ${stockItems.length}</span>
                </div>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f5f5f5;">
                            <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Serial Number</th>
                            <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">IMEI</th>
                            <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Status</th>
                            <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Added Date</th>
                            <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${stockItems.map(item => `
                            <tr>
                                <td style="padding: 10px; border: 1px solid #ddd;">${item.serial_number}</td>
                                <td style="padding: 10px; border: 1px solid #ddd;">${item.imei || 'N/A'}</td>
                                <td style="padding: 10px; border: 1px solid #ddd;">
                                    <span style="
                                        background: ${item.status === 'available' ? '#4CAF50' : item.status === 'sold' ? '#2196F3' : item.status === 'reserved' ? '#FF9800' : '#F44336'};
                                        color: white;
                                        padding: 3px 8px;
                                        border-radius: 3px;
                                        font-size: 12px;
                                    ">${item.status.toUpperCase()}</span>
                                </td>
                                <td style="padding: 10px; border: 1px solid #ddd;">${new Date(item.created_at).toLocaleDateString()}</td>
                                <td style="padding: 10px; border: 1px solid #ddd;">
                                    ${item.status === 'available' ? `
                                        <button onclick="markAsSold(${item.id})" style="background: #2196F3; color: white; border: none; padding: 5px 10px; border-radius: 3px; margin-right: 5px; cursor: pointer;">Mark Sold</button>
                                        <button onclick="deleteStockItem(${item.id})" style="background: #F44336; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">Delete</button>
                                    ` : '-'}
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
                <div style="margin-top: 20px; text-align: right;">
                    <button onclick="closeStockItemsModal()" style="background: #666; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Close</button>
                </div>
            `;
            
            modal.appendChild(modalContent);
            document.body.appendChild(modal);
            
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }
        
        async function markAsSold(stockItemId) {
            if (!confirm('Mark this item as sold?')) return;
            
            try {
                const response = await fetch('api/stock_items.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: stockItemId,
                        status: 'sold'
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Item marked as sold successfully');
                    location.reload(); // Reload to update the inventory
                } else {
                    alert('Failed to mark item as sold: ' + result.message);
                }
            } catch (error) {
                console.error('Error marking item as sold:', error);
                alert('Error marking item as sold');
            }
        }
        
        async function deleteStockItem(stockItemId) {
            if (!confirm('Delete this stock item? This action cannot be undone.')) return;
            
            try {
                const response = await fetch(`api/stock_items.php?id=${stockItemId}`, {
                    method: 'DELETE'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Stock item deleted successfully');
                    location.reload(); // Reload to update the inventory
                } else {
                    alert('Failed to delete stock item: ' + result.message);
                }
            } catch (error) {
                console.error('Error deleting stock item:', error);
                alert('Error deleting stock item');
            }
        }
        
        function closeStockItemsModal() {
            // Find and remove the stock items modal
            const modals = document.querySelectorAll('div[style*="position: fixed"]');
            modals.forEach(modal => {
                if (modal.style.background && modal.style.background.includes('rgba(0,0,0,0.5)')) {
                    modal.remove();
                }
            });
        }
        
        // IMEI format validation
                imeiField.addEventListener('input', function() {
                    let value = this.value.replace(/\D/g, ''); // Remove non-digits
                    if (value.length > 15) {
                        value = value.substring(0, 15);
                    }
                    this.value = value;
                });
            }
            
            // Form submission handling
            const addProductForm = document.querySelector('form[method="POST"]');
            if (addProductForm) {
                addProductForm.addEventListener('submit', function(e) {
                    const hasImei = document.getElementById('has-imei-select').value;
                    const imeiField = document.getElementById('imei-field');
                    
                    if (hasImei === '1' && (!imeiField.value || imeiField.value.length !== 15)) {
                        e.preventDefault();
                        alert('IMEI is required and must be 15 digits when "Has IMEI" is set to Yes');
                        imeiField.focus();
                        return false;
                    }
                });
            }
        });

        // Barcode Generation Function
        async function generateBarcode(productId) {
            if (!confirm('Generate barcode for this product?')) return;
            
            try {
                const response = await fetch('api/barcode.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        product_id: productId
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Barcode generated successfully: ' + result.barcode);
                    loadInventory(); // Refresh inventory to show the new barcode
                } else {
                    alert('Failed to generate barcode: ' + result.message);
                }
            } catch (error) {
                console.error('Error generating barcode:', error);
                alert('Error generating barcode');
            }
        }

        // Barcode Scanner Function
        function startBarcodeScanner() {
            const input = document.createElement('input');
            input.style.position = 'fixed';
            input.style.top = '0';
            input.style.left = '0';
            input.style.width = '100%';
            input.style.height = '100%';
            input.style.zIndex = '10000';
            input.style.background = 'rgba(0,0,0,0.8)';
            input.style.color = 'white';
            input.style.fontSize = '24px';
            input.style.textAlign = 'center';
            input.style.padding = '20px';
            input.placeholder = 'Scan barcode or type barcode number...';
            
            document.body.appendChild(input);
            input.focus();
            
            input.addEventListener('keypress', async function(e) {
                if (e.key === 'Enter') {
                    const barcode = input.value.trim();
                    document.body.removeChild(input);
                    
                    if (barcode) {
                        await searchProductByBarcode(barcode);
                    }
                }
            });
            
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    document.body.removeChild(input);
                }
            });
        }

        async function searchProductByBarcode(barcode) {
            try {
                const response = await fetch(`api/barcode.php?barcode=${encodeURIComponent(barcode)}`);
                const result = await response.json();
                
                if (result.success) {
                    const product = result.data;
                    alert(`Product Found!\n\nCode: ${product.code}\nProduct: ${product.brand} ${product.model}\nStock: ${product.stock}\nPrice: ${product.suggested_price} EGP`);
                    
                    // Add to receipt if in sales tab
                    if (document.getElementById('receipt').classList.contains('active')) {
                        addToReceiptByBarcode(product);
                    }
                } else {
                    alert('Product not found for barcode: ' + barcode);
                }
            } catch (error) {
                console.error('Error searching by barcode:', error);
                alert('Error searching for product');
            }
        }

        function addToReceiptByBarcode(product) {
            // Add product to receipt
            const existingItem = currentReceipt.items.find(item => item.id === product.id);
            
            if (existingItem) {
                existingItem.quantity++;
            } else {
                currentReceipt.items.push({
                    id: product.id,
                    code: product.code,
                    name: `${product.brand} ${product.model}`,
                    price: product.suggested_price,
                    quantity: 1
                });
            }
            
            updateReceiptDisplay();
        }

        // Barcode generation function (for PHP form submission)
        function generateEAN13Barcode(productCode) {
            // This function is for reference - actual generation happens in PHP
            // Extract numeric part from product code
            const numeric = productCode.replace(/[^0-9]/g, '');
            
            // Pad to 12 digits (EAN-13 without checksum)
            let padded = numeric.padStart(12, '0').substring(0, 12);
            
            // Calculate checksum
            let sum = 0;
            for (let i = 0; i < 12; i++) {
                const digit = parseInt(padded[i]);
                sum += (i % 2 === 0) ? digit : digit * 3;
            }
            const checksum = (10 - (sum % 10)) % 10;
            
            return padded + checksum;
        }

    </script>
    
    <script>
        // Language toggle function using the new translation system
        function toggleLanguage() {
            if (typeof langManager !== 'undefined') {
                langManager.toggleLanguage();
            }
        }
        
        // Handle logout function
        function handleLogout(event) {
            console.log('Logout clicked');
            event.preventDefault();
            
            // Show confirmation
            if (confirm('Are you sure you want to logout?')) {
                console.log('Proceeding with logout');
                window.location.href = '?logout=1';
            } else {
                console.log('Logout cancelled');
            }
        }
        
        // Apply initial language when page loads
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof langManager !== 'undefined') {
                langManager.init();
            }
        });
    </script>
    <!-- Modern Success Modal -->
    <div id="successModalOverlay" class="ibs-modal-overlay">
        <div class="ibs-modal-card">
            <div class="ibs-modal-icon">✓</div>
            <h2 class="ibs-modal-title" data-translate="sales.saleCompleted">Sale Completed!</h2>
            <div class="ibs-modal-body">
                <p><span data-translate="sales.receiptGenerated">Receipt # has been generated successfully.</span></p>
                <p><strong>#<span id="successReceiptNumber">---</span></strong></p>
                <div style="margin-top: 15px; padding: 15px; background: var(--gray-50); border-radius: var(--radius-sm); border: 1px dashed var(--gray-200);">
                    <span style="display: block; font-size: 14px; color: var(--gray-500); margin-bottom: 5px;" data-translate="sales.totalAmountPaid">Total Amount Paid</span>
                    <span id="successTotalAmount" style="font-size: 24px; font-weight: 700; color: var(--primary-green);">0.00 EGP</span>
                </div>
            </div>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button class="btn-modern btn-primary-modern" onclick="closeSuccessModal()">
                    <span data-translate="sales.gotIt">Got it</span>
                </button>
            </div>
        </div>
    </div>
</body>

</html>