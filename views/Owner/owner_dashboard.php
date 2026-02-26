<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header('Location: index.php');
    exit();
}

include_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get settings
$settings = [];
try {
    $stmt = $db->query("SELECT key_name, key_value FROM settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['key_name']] = $row['key_value'];
    }
} catch (Exception $e) {
    error_log("Error getting settings: " . $e->getMessage());
}

$company_name = $settings['company_name'] ?? 'IBS Store';
$theme_accent = $settings['theme_accent'] ?? '#667eea';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard | <?php echo htmlspecialchars($company_name); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: <?php echo $theme_accent; ?>;
            --secondary-color: #764ba2;
            --bg-dark: #0f172a;
            --card-bg: #1e293b;
            --sidebar-bg: #111827;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --accent-success: #10b981;
            --accent-warning: #f59e0b;
            --accent-danger: #ef4444;
            --border-color: rgba(255, 255, 255, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-primary);
            overflow-x: hidden;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 280px;
            background-color: var(--sidebar-bg);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            z-index: 100;
        }

        .sidebar-header {
            padding: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .logo-box {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 20px;
        }

        .sidebar-menu {
            margin-top: 20px;
            flex-grow: 1;
            padding: 0 15px;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 20px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 12px;
            margin-bottom: 5px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .menu-item:hover, .menu-item.active {
            background-color: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
        }

        .menu-item.active {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.1) 0%, transparent 100%);
            border-left: 3px solid var(--primary-color);
        }

        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid var(--border-color);
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            flex-grow: 1;
            padding: 40px;
            width: calc(100% - 280px);
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .welcome-text h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .welcome-text p {
            color: var(--text-secondary);
            font-size: 14px;
        }

        .header-actions {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .branch-selector {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            color: white;
            padding: 10px 15px;
            border-radius: 10px;
            cursor: pointer;
            outline: none;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background-color: var(--card-bg);
            padding: 25px;
            border-radius: 20px;
            border: 1px solid var(--border-color);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 15px;
        }

        .stat-info .stat-label {
            color: var(--text-secondary);
            font-size: 14px;
            margin-bottom: 5px;
            display: block;
        }

        .stat-info .stat-value {
            font-size: 24px;
            font-weight: 700;
        }

        /* Dynamic Colors for Stat Icons */
        .icon-blue { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .icon-green { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .icon-purple { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
        .icon-orange { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .icon-red { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        .icon-cyan { background: rgba(6, 182, 212, 0.1); color: #06b6d4; }
        .icon-pink { background: rgba(236, 72, 153, 0.1); color: #ec4899; }
        .icon-gold { background: rgba(234, 179, 8, 0.1); color: #eab308; }

        /* Tabs Content */
        .tab-content {
            display: none;
            animation: fadeIn 0.5s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Tables */
        .data-table-container {
            background-color: var(--card-bg);
            border-radius: 20px;
            border: 1px solid var(--border-color);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .table-header {
            padding: 25px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 15px 25px;
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 14px;
            background-color: rgba(255, 255, 255, 0.02);
        }

        td {
            padding: 20px 25px;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-success { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .badge-danger { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

        .btn {
            padding: 10px 20px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary { background: var(--primary-color); color: white; }
        .btn-primary:hover { opacity: 0.9; transform: scale(1.02); }

        .btn-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-secondary);
            margin-right: 5px;
        }

        .btn-icon:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
        }

        /* Charts Section */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            margin-bottom: 40px;
        }

        .chart-card {
            background-color: var(--card-bg);
            padding: 25px;
            border-radius: 20px;
            border: 1px solid var(--border-color);
        }

        .chart-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Modal styling */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: var(--card-bg);
            width: 90%;
            max-width: 600px;
            border-radius: 20px;
            border: 1px solid var(--border-color);
            padding: 40px;
            position: relative;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-secondary);
            font-size: 14px;
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            padding: 12px 15px;
            border-radius: 10px;
            color: white;
            outline: none;
        }

        /* Master Data Tabs */
        .nested-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            background: rgba(255, 255, 255, 0.02);
            padding: 10px;
            border-radius: 15px;
        }

        .nested-tab {
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            color: var(--text-secondary);
            transition: all 0.3s ease;
        }

        .nested-tab.active {
            background: var(--primary-color);
            color: white;
        }

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="layout">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="logo-box">G</div>
                <div class="brand-info">
                    <h2 style="font-size: 20px;"><?php echo htmlspecialchars($company_name); ?></h2>
                    <p style="font-size: 12px; color: var(--text-secondary);">Enterprise Control</p>
                </div>
            </div>
            
            <div class="sidebar-menu">
                <div class="menu-item active" onclick="switchMainTab('dashboard', this)">
                    <i class="fas fa-grid-2"></i> Dashboard
                </div>
                <div class="menu-item" onclick="switchMainTab('branches', this)">
                    <i class="fas fa-building"></i> Branches
                </div>
                <div class="menu-item" onclick="switchMainTab('master-data', this)">
                    <i class="fas fa-database"></i> Master Data
                </div>
                <div class="menu-item" onclick="switchMainTab('products', this)">
                    <i class="fas fa-box"></i> Global Products
                </div>
                <div class="menu-item" onclick="switchMainTab('analytics', this)">
                    <i class="fas fa-chart-line"></i> Analytics
                </div>
                <div class="menu-item" onclick="switchMainTab('users', this)">
                    <i class="fas fa-user-shield"></i> Access Control
                </div>
                <div class="menu-item" onclick="switchMainTab('settings', this)">
                    <i class="fas fa-cogs"></i> System Settings
                </div>
            </div>

            <div class="sidebar-footer">
                <a href="index.php?logout=true" class="menu-item" style="color: var(--accent-danger);">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="welcome-text">
                    <h1 id="page-title">👑 Global Overview</h1>
                    <p id="page-subtitle">Real-time performance across all branches</p>
                </div>
                
                <div class="header-actions">
                    <select class="branch-selector" id="global-branch-selector" onchange="loadDashboardData()">
                        <option value="all">All Branches</option>
                        <!-- Branch options will be loaded dynamically -->
                    </select>
                    
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <span style="font-size: 14px;"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                        <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DASHBOARD TAB -->
            <div id="tab-dashboard" class="tab-content active">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon icon-blue"><i class="fas fa-building"></i></div>
                        <div class="stat-info">
                            <span class="stat-label">Total Branches</span>
                            <span class="stat-value" id="stat-total-branches">-</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon icon-green"><i class="fas fa-money-bill-wave"></i></div>
                        <div class="stat-info">
                            <span class="stat-label">Total Revenue (Month)</span>
                            <span class="stat-value" id="stat-total-revenue">- EGP</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon icon-purple"><i class="fas fa-shopping-bag"></i></div>
                        <div class="stat-info">
                            <span class="stat-label">Units Sold (Month)</span>
                            <span class="stat-value" id="stat-units-sold">-</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon icon-gold"><i class="fas fa-chart-pie"></i></div>
                        <div class="stat-info">
                            <span class="stat-label">Net Profit (Month)</span>
                            <span class="stat-value" id="stat-net-profit">- EGP</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon icon-cyan"><i class="fas fa-receipt"></i></div>
                        <div class="stat-info">
                            <span class="stat-label">Total Transactions</span>
                            <span class="stat-value" id="stat-transactions">-</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon icon-red"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="stat-info">
                            <span class="stat-label">Low Stock Items</span>
                            <span class="stat-value" id="stat-low-stock">-</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon icon-orange"><i class="fas fa-undo"></i></div>
                        <div class="stat-info">
                            <span class="stat-label">Total Returns</span>
                            <span class="stat-value" id="stat-returns">-</span>
                        </div>
                    </div>
                    <div class="stat-card" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); border: none;">
                        <div class="stat-icon" style="background: rgba(255,255,255,0.2); color: white;"><i class="fas fa-trophy"></i></div>
                        <div class="stat-info">
                            <span class="stat-label" style="color: rgba(255,255,255,0.8);">Best Branch</span>
                            <span class="stat-value" style="color: white;" id="stat-best-branch">-</span>
                        </div>
                    </div>
                </div>

                <div class="charts-grid">
                    <div class="chart-card">
                        <h3 class="chart-title">Revenue by Branch</h3>
                        <canvas id="revenueBranchChart"></canvas>
                    </div>
                    <div class="chart-card">
                        <h3 class="chart-title">Revenue Trend (6 Months)</h3>
                        <canvas id="revenueTrendChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- BRANCHES TAB -->
            <div id="tab-branches" class="tab-content">
                <div class="data-table-container">
                    <div class="table-header">
                        <h3>Branch Control Panel</h3>
                        <button class="btn btn-primary" onclick="openBranchModal()">+ Add New Branch</button>
                    </div>
                    <table id="branches-table">
                        <thead>
                            <tr>
                                <th>Branch Name</th>
                                <th>Location</th>
                                <th>Total Revenue</th>
                                <th>Units Sold</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="branches-list">
                            <!-- Loaded via API -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- MASTER DATA TAB -->
            <div id="tab-master-data" class="tab-content">
                <div class="nested-tabs">
                    <div class="nested-tab active" onclick="switchNestedTab('categories', this)">Categories</div>
                    <div class="nested-tab" onclick="switchNestedTab('brands', this)">Brands</div>
                    <div class="nested-tab" onclick="switchNestedTab('suppliers', this)">Suppliers</div>
                </div>

                <div id="nested-categories" class="nested-content active">
                    <div class="data-table-container">
                        <div class="table-header">
                            <h3>Product Categories</h3>
                            <button class="btn btn-primary" onclick="openCategoryModal()">+ Add Category</button>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Category Name</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="categories-list"></tbody>
                        </table>
                    </div>
                </div>

                <div id="nested-brands" class="nested-content" style="display: none;">
                    <div class="data-table-container">
                        <div class="table-header">
                            <h3>Product Brands</h3>
                            <button class="btn btn-primary" onclick="openBrandModal()">+ Add Brand</button>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Brand Name</th>
                                    <th>Description</th>
                                    <th>Contact</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="brands-list"></tbody>
                        </table>
                    </div>
                </div>

                <div id="nested-suppliers" class="nested-content" style="display: none;">
                    <div class="data-table-container">
                        <div class="table-header">
                            <h3>Company Suppliers</h3>
                            <button class="btn btn-primary" onclick="openSupplierModal()">+ Add Supplier</button>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Supplier Name</th>
                                    <th>Contact Person</th>
                                    <th>Phone</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="suppliers-list"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- GLOBAL PRODUCTS TAB -->
            <div id="tab-products" class="tab-content">
                <div class="data-table-container">
                    <div class="table-header">
                        <h3>Global Product Master Control</h3>
                    </div>
                    <table id="products-table">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Branch</th>
                                <th>Purchase Price</th>
                                <th>Min Sell Price</th>
                                <th>Market Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="global-products-list"></tbody>
                    </table>
                </div>
            </div>

            <!-- ANALYTICS TAB -->
            <div id="tab-analytics" class="tab-content">
                <div class="charts-grid">
                    <div class="chart-card">
                        <h3 class="chart-title">Monthly Revenue Trend</h3>
                        <canvas id="analyticsTrendChart"></canvas>
                    </div>
                    <div class="chart-card">
                        <h3 class="chart-title">Sales by Category</h3>
                        <canvas id="categoryPieChart"></canvas>
                    </div>
                    <div class="chart-card" style="grid-column: span 2;">
                        <h3 class="chart-title">Top 10 Selling Products</h3>
                        <canvas id="topProductsChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- ACCESS CONTROL TAB -->
            <div id="tab-users" class="tab-content">
                <div class="data-table-container">
                    <div class="table-header">
                        <h3>Admin & Staff Management</h3>
                        <button class="btn btn-primary" onclick="openUserModal()">+ Create Admin Account</button>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="users-list"></tbody>
                    </table>
                </div>
            </div>

            <!-- SYSTEM SETTINGS TAB -->
            <div id="tab-settings" class="tab-content">
                <div class="data-table-container" style="padding: 40px;">
                    <h3>System-Wide Configuration</h3>
                    <form id="settings-form" style="margin-top: 30px; display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                        <div class="form-group">
                            <label>Company Name</label>
                            <input type="text" name="company_name" value="<?php echo htmlspecialchars($company_name); ?>">
                        </div>
                        <div class="form-group">
                            <label>Currency Symbol</label>
                            <input type="text" name="currency" value="<?php echo htmlspecialchars($settings['currency'] ?? 'EGP'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Tax Percentage (%)</label>
                            <input type="number" name="tax_percentage" value="<?php echo htmlspecialchars($settings['tax_percentage'] ?? '0'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Theme Accent Color</label>
                            <input type="color" name="theme_accent" value="<?php echo htmlspecialchars($theme_accent); ?>" style="height: 45px; padding: 5px;">
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label>Invoice Footer Text</label>
                            <textarea name="invoice_footer" rows="3"><?php echo htmlspecialchars($settings['invoice_footer'] ?? ''); ?></textarea>
                        </div>
                        <div style="grid-column: span 2; display: flex; justify-content: flex-end;">
                            <button type="submit" class="btn btn-primary">Save System Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <!-- Modals (Simplified for brevity, but will work) -->
    <div class="modal" id="branch-modal">
        <div class="modal-content">
            <h2 id="modal-title">Branch Details</h2>
            <form id="branch-form" style="margin-top: 25px;">
                <input type="hidden" name="id">
                <div class="form-group">
                    <label>Branch Name</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone">
                </div>
                <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px;">
                    <button type="button" class="btn" onclick="closeModal('branch-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Branch</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Tab Management
        function switchMainTab(tabName, element) {
            document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active'));
            element.classList.add('active');
            
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.getElementById('tab-' + tabName).classList.add('active');
            
            // Update Title
            const titles = {
                'dashboard': '👑 Global Overview',
                'branches': '🏢 Branch Management',
                'master-data': '🏷 Master Data Management',
                'products': '📦 Global Product Control',
                'analytics': '📊 Business Intelligence',
                'users': '🔐 Access Control',
                'settings': '⚙️ System Settings'
            };
            document.getElementById('page-title').innerText = titles[tabName];
            
            // Initialization for specific tabs
            if(tabName === 'dashboard') loadDashboardData();
            if(tabName === 'branches') loadBranches();
            if(tabName === 'products') loadGlobalProducts();
            if(tabName === 'analytics') loadFullAnalytics();
            if(tabName === 'users') loadUsers();
            if(tabName === 'master-data') loadCategories();
        }

        async function loadFullAnalytics() {
            try {
                const response = await fetch('api/owner_analytics.php');
                const result = await response.json();
                if(result.success) {
                    const data = result.data;
                    
                    // Analytics Trend
                    const trendCtx = document.getElementById('analyticsTrendChart').getContext('2d');
                    new Chart(trendCtx, {
                        type: 'line',
                        data: {
                            labels: data.monthly_trend.map(t => t.month),
                            datasets: [{
                                label: 'Revenue',
                                data: data.monthly_trend.map(t => t.revenue),
                                borderColor: 'var(--primary-color)',
                                tension: 0.4,
                                fill: true,
                                backgroundColor: 'rgba(102, 126, 234, 0.1)'
                            }]
                        }
                    });

                    // Category Pie
                    const pieCtx = document.getElementById('categoryPieChart').getContext('2d');
                    new Chart(pieCtx, {
                        type: 'doughnut',
                        data: {
                            labels: data.sales_by_category.map(c => c.name),
                            datasets: [{
                                data: data.sales_by_category.map(c => c.count),
                                backgroundColor: ['#3b82f6', '#10b981', '#8b5cf6', '#f59e0b', '#ef4444', '#06b6d4']
                            }]
                        }
                    });

                    // Top Products
                    const prodCtx = document.getElementById('topProductsChart').getContext('2d');
                    new Chart(prodCtx, {
                        type: 'bar',
                        data: {
                            labels: data.top_products.map(p => p.name),
                            datasets: [{
                                label: 'Units Sold',
                                data: data.top_products.map(p => p.value),
                                backgroundColor: 'rgba(124, 58, 237, 0.6)',
                                borderRadius: 10
                            }]
                        },
                        options: { indexAxis: 'y' }
                    });
                }
            } catch (error) {
                console.error("Full analytics error:", error);
            }
        }

        function switchNestedTab(tabName, element) {
            document.querySelectorAll('.nested-tab').forEach(t => t.classList.remove('active'));
            element.classList.add('active');
            
            // Toggle visibility of nested content
            document.querySelectorAll('.nested-content').forEach(c => c.style.display = 'none');
            const target = document.getElementById('nested-' + tabName);
            if(target) target.style.display = 'block';
            
            if(tabName === 'categories') loadCategories();
            if(tabName === 'brands') loadBrands();
            if(tabName === 'suppliers') loadSuppliers();
        }

        // Master Data CRUD
        async function loadCategories() {
            const response = await fetch('api/categories.php');
            const result = await response.json();
            const list = document.getElementById('categories-list');
            list.innerHTML = '';
            result.data.forEach(cat => {
                list.innerHTML += `
                    <tr>
                        <td><strong>${cat.name}</strong></td>
                        <td>${cat.description || '-'}</td>
                        <td>
                            <button class="btn-icon" onclick='openCategoryModal(${JSON.stringify(cat)})'><i class="fas fa-edit"></i></button>
                            <button class="btn-icon" onclick='deleteCategory(${cat.id})'><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                `;
            });
        }

        async function loadBrands() {
            const response = await fetch('api/brands.php');
            const result = await response.json();
            const list = document.getElementById('brands-list');
            list.innerHTML = '';
            result.data.forEach(brand => {
                list.innerHTML += `
                    <tr>
                        <td><strong>${brand.name}</strong></td>
                        <td>${brand.description || '-'}</td>
                        <td>${brand.contact_email || '-'}</td>
                        <td>
                            <button class="btn-icon" onclick='openBrandModal(${JSON.stringify(brand)})'><i class="fas fa-edit"></i></button>
                            <button class="btn-icon" onclick='deleteBrand(${brand.id})'><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                `;
            });
        }

        async function loadSuppliers() {
            const response = await fetch('api/suppliers.php');
            const result = await response.json();
            const list = document.getElementById('suppliers-list');
            list.innerHTML = '';
            result.data.forEach(sup => {
                list.innerHTML += `
                    <tr>
                        <td><strong>${sup.name}</strong></td>
                        <td>${sup.contact_person || '-'}</td>
                        <td>${sup.phone || '-'}</td>
                        <td>
                            <button class="btn-icon" onclick='openSupplierModal(${JSON.stringify(sup)})'><i class="fas fa-edit"></i></button>
                            <button class="btn-icon" onclick='deleteSupplier(${sup.id})'><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                `;
            });
        }

        // Access Control (Users)
        async function loadUsers() {
            const response = await fetch('api/users.php');
            const result = await response.json();
            const list = document.getElementById('users-list');
            list.innerHTML = '';
            result.data.forEach(user => {
                list.innerHTML += `
                    <tr>
                        <td>
                            <div style="font-weight: 600;">${user.name}</div>
                            <div style="font-size: 12px; color: var(--text-secondary);">@${user.username}</div>
                        </td>
                        <td><span class="badge" style="background: rgba(102, 126, 234, 0.1); color: var(--primary-color);">${user.role.toUpperCase()}</span></td>
                        <td>${user.phone || '-'}</td>
                        <td><span class="badge ${user.is_active ? 'badge-success' : 'badge-danger'}">${user.status}</span></td>
                        <td>
                            <button class="btn-icon" onclick='openUserModal(${JSON.stringify(user)})'><i class="fas fa-edit"></i></button>
                        </td>
                    </tr>
                `;
            });
        }

        // Dashboard Data Loading
        async function loadDashboardData() {
            const branchId = document.getElementById('global-branch-selector').value;
            try {
                const response = await fetch(`api/owner_stats.php?branch_id=${branchId}`);
                const result = await response.json();
                if(result.success) {
                    const data = result.data;
                    document.getElementById('stat-total-branches').innerText = data.total_branches;
                    document.getElementById('stat-total-revenue').innerText = data.total_revenue.toLocaleString() + ' EGP';
                    document.getElementById('stat-units-sold').innerText = data.total_units_sold.toLocaleString();
                    document.getElementById('stat-net-profit').innerText = data.net_profit.toLocaleString() + ' EGP';
                    document.getElementById('stat-transactions').innerText = data.total_transactions;
                    document.getElementById('stat-low-stock').innerText = data.low_stock_products;
                    document.getElementById('stat-returns').innerText = data.total_returns;
                    document.getElementById('stat-best-branch').innerText = data.best_branch;
                }
            } catch (error) {
                console.error("Stats loading error:", error);
            }
            loadCharts();
        }

        // Charts
        let revenueTrendChart = null;
        let revenueBranchChart = null;

        async function loadCharts() {
            try {
                const response = await fetch('api/owner_analytics.php');
                const result = await response.json();
                if(result.success) {
                    const data = result.data;
                    
                    // Kill old charts if they exist
                    if(revenueTrendChart) revenueTrendChart.destroy();
                    if(revenueBranchChart) revenueBranchChart.destroy();

                    // Branch Revenue Chart
                    const branchCtx = document.getElementById('revenueBranchChart').getContext('2d');
                    revenueBranchChart = new Chart(branchCtx, {
                        type: 'bar',
                        data: {
                            labels: data.revenue_by_branch.map(b => b.name),
                            datasets: [{
                                label: 'Revenue (EGP)',
                                data: data.revenue_by_branch.map(b => b.revenue),
                                backgroundColor: 'rgba(102, 126, 234, 0.6)',
                                borderRadius: 10
                            }]
                        },
                        options: {
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { grid: { color: 'rgba(255,255,255,0.05)' }, border: { display: false } },
                                x: { grid: { display: false } }
                            }
                        }
                    });

                    // Monthly Trend Chart
                    const trendCtx = document.getElementById('revenueTrendChart').getContext('2d');
                    revenueTrendChart = new Chart(trendCtx, {
                        type: 'line',
                        data: {
                            labels: data.monthly_trend.map(t => t.month),
                            datasets: [{
                                label: 'Revenue',
                                data: data.monthly_trend.map(t => t.revenue),
                                borderColor: 'var(--primary-color)',
                                tension: 0.4,
                                fill: true,
                                backgroundColor: 'rgba(102, 126, 234, 0.1)'
                            }]
                        },
                        options: {
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { grid: { color: 'rgba(255,255,255,0.05)' } },
                                x: { grid: { display: false } }
                            }
                        }
                    });
                }
            } catch (error) {
                console.error("Charts loading error:", error);
            }
        }

        // Modal Helpers
        function openBranchModal(data = null) {
            const modal = document.getElementById('branch-modal');
            const form = document.getElementById('branch-form');
            form.reset();
            if(data) {
                form.id.value = data.id;
                form.name.value = data.name;
                form.location.value = data.location;
                form.phone.value = data.phone;
            }
            modal.style.display = 'flex';
        }

        function openCategoryModal(data = null) { alert('Category Management Modal - Coming Soon'); }
        function openBrandModal(data = null) { alert('Brand Management Modal - Coming Soon'); }
        function openSupplierModal(data = null) { alert('Supplier Management Modal - Coming Soon'); }
        function openUserModal(data = null) { alert('User Management Modal - Coming Soon'); }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        // Branch Management
        async function loadBranches() {
            const response = await fetch('api/owner_branches.php');
            const result = await response.json();
            const list = document.getElementById('branches-list');
            list.innerHTML = '';
            
            result.data.forEach(branch => {
                list.innerHTML += `
                    <tr>
                        <td><strong>${branch.name}</strong></td>
                        <td>${branch.location}</td>
                        <td class="text-success">${parseFloat(branch.total_revenue).toLocaleString()} EGP</td>
                        <td>${branch.units_sold}</td>
                        <td><span class="badge ${branch.is_active ? 'badge-success' : 'badge-danger'}">${branch.is_active ? 'Active' : 'Disabled'}</span></td>
                        <td>
                            <button class="btn-icon" onclick='openBranchModal(${JSON.stringify(branch)})'><i class="fas fa-edit"></i></button>
                            <button class="btn-icon"><i class="fas fa-eye"></i></button>
                        </td>
                    </tr>
                `;
                
                // Also add to branch selector if not there
                const selector = document.getElementById('global-branch-selector');
                if(![...selector.options].some(opt => opt.value == branch.id)) {
                    const opt = document.createElement('option');
                    opt.value = branch.id;
                    opt.text = branch.name;
                    selector.add(opt);
                }
            });
        }

        // Global Products
        async function loadGlobalProducts() {
            const response = await fetch('api/owner_products.php');
            const result = await response.json();
            const list = document.getElementById('global-products-list');
            list.textContent = '';
            
            result.data.forEach(product => {
                list.innerHTML += `
                    <tr>
                        <td>
                            <div style="font-weight: 600;">${product.model}</div>
                            <div style="font-size: 12px; color: var(--text-secondary);">${product.brand_name} / ${product.category_name}</div>
                        </td>
                        <td>${product.branch_name || 'N/A'}</td>
                        <td>${parseFloat(product.purchase_price).toLocaleString()}</td>
                        <td>${parseFloat(product.min_selling_price).toLocaleString()}</td>
                        <td style="font-weight: 700;">${parseFloat(product.suggested_price).toLocaleString()}</td>
                        <td>
                            <span style="color: ${product.quantity <= product.min_stock ? 'var(--accent-danger)' : 'var(--accent-success)'}">
                                ${product.quantity}
                            </span>
                        </td>
                        <td><span class="badge ${product.is_active ? 'badge-success' : 'badge-danger'}">${product.is_active ? 'Active' : 'Locked'}</span></td>
                        <td>
                            <button class="btn-icon"><i class="fas fa-pencil"></i></button>
                            <button class="btn-icon"><i class="fas fa-lock"></i></button>
                        </td>
                    </tr>
                `;
            });
        }

        // Settings Handling
        document.getElementById('settings-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            
            try {
                const response = await fetch('api/owner_settings.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if(result.success) {
                    alert('System settings updated globally!');
                    location.reload();
                }
            } catch (error) {
                alert('Error updating settings');
            }
        });

        // Initialize
        window.onload = () => {
            loadDashboardData();
            loadBranches();
            loadCategories(); // Preload categories
        };
    </script>
</body>
</html>
