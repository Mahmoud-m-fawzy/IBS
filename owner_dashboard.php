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
error_log("Owner dashboard accessed - User ID: " . $_SESSION['user_id'] . ", Username: " . ($_SESSION['username'] ?? 'Not set'));

include_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get owner statistics
$stats = [];
try {
    // Total branches
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM branches");
    $stmt->execute();
    $stats['total_branches'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total staff across all branches
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE role IN ('admin', 'staff')");
    $stmt->execute();
    $stats['total_staff'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total customers across all branches
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM customers");
    $stmt->execute();
    $stats['total_customers'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total products across all branches
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM products");
    $stmt->execute();
    $stats['total_products'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total stock value across all branches
    $stmt = $db->prepare("SELECT SUM(stock * purchase_price) as total_value FROM products");
    $stmt->execute();
    $stats['total_stock_value'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_value'] ?? 0;
    
    // Today's sales across all branches
    $stmt = $db->prepare("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM sales WHERE DATE(sale_date) = CURDATE()");
    $stmt->execute();
    $today_sales = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['today_sales_count'] = $today_sales['count'];
    $stats['today_sales_amount'] = $today_sales['total'];
    
    // This month's sales across all branches
    $stmt = $db->prepare("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM sales WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())");
    $stmt->execute();
    $month_sales = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['month_sales_count'] = $month_sales['count'];
    $stats['month_sales_amount'] = $month_sales['total'];
    
} catch (Exception $e) {
    error_log("Error getting owner stats: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard - IBS</title>
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
            background: #ffffff;
            min-height: 100vh;
            color: #333;
        }
        
        .owner-dashboard {
            padding: 30px;
            max-width: 1400px;
            margin: 0 auto;
            animation: fadeInUp 0.6s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .owner-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            position: relative;
            overflow: hidden;
        }
        
        .owner-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 3s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                opacity: 0.1;
            }
            50% {
                transform: scale(1.1);
                opacity: 0.05;
            }
        }
        
        .owner-info h1 {
            margin: 0;
            font-size: 2.8em;
            font-weight: 700;
            text-shadow: 2px 4px rgba(0,0,0,0.3);
            position: relative;
            z-index: 2;
        }
        
        .owner-info p {
            margin: 10px 0 0 0;
            opacity: 0.95;
            font-size: 1.2em;
            font-weight: 300;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-left: 4px solid;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            display: none;
        }
        
        .stat-card.branches { 
            border-left-color: #FF6B6B; 
            background: #ffffff;
            color: #FF6B6B;
        }
        
        .stat-card.branches:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(255,107,107,0.2);
        }
        
        .stat-card.staff { 
            border-left-color: #4ECDC4; 
            background: #ffffff;
            color: #4ECDC4;
        }
        
        .stat-card.staff:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(78,205,196,0.2);
        }
        
        .stat-card.customers { 
            border-left-color: #45B7D1; 
            background: #ffffff;
            color: #45B7D1;
        }
        
        .stat-card.customers:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(33,150,243,0.2);
        }
        
        .stat-card.products { 
            border-left-color: #96CEB4; 
            background: #ffffff;
            color: #96CEB4;
        }
        
        .stat-card.products:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(150,206,180,0.2);
        }
        
        .stat-card.stock-value { 
            border-left-color: #FFEAA7; 
            background: #ffffff;
            color: #F39C12;
        }
        
        .stat-card.stock-value:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(243,156,18,0.2);
        }
        
        .stat-card.today-sales { 
            border-left-color: #DDA0DD; 
            background: #ffffff;
            color: #DDA0DD;
        }
        
        .stat-card.today-sales:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(156,39,176,0.2);
        }
        
        .stat-card.month-sales { 
            border-left-color: #98D8C8; 
            background: #ffffff;
            color: #98D8C8;
        }
        
        .stat-card.month-sales:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(39,174,96,0.2);
        }
        
        .stat-icon {
            font-size: 2.5em;
            margin-bottom: 15px;
            opacity: 0.8;
        }
        
        .stat-value {
            font-size: 2.2em;
            font-weight: 700;
            margin-bottom: 8px;
            color: #333;
        }
        
        .stat-label {
            font-size: 1em;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 500;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 50px;
        }
        
        .action-card {
            background: white;
            padding: 35px;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            text-align: center;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }
        
        .action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1) 50%, transparent 100%);
            transition: all 0.6s ease;
        }
        
        .action-card:hover::before {
            left: 0;
        }
        
        .action-card:hover {
            transform: translateY(-10px) scale(1.05);
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            text-decoration: none;
            color: inherit;
        }
        
        .action-icon {
            font-size: 3.5em;
            margin-bottom: 20px;
            color: #667eea;
            transition: all 0.3s ease;
        }
        
        .action-card:hover .action-icon {
            color: #764ba2;
            transform: scale(1.1);
        }
        
        .action-title {
            font-size: 1.5em;
            font-weight: 700;
            margin-bottom: 12px;
            color: #2c3e50;
            transition: all 0.3s ease;
        }
        
        .action-desc {
            font-size: 1em;
            color: #7f8c8d;
            line-height: 1.5;
            transition: all 0.3s ease;
        }
        
        .action-card:hover .action-title {
            color: #667eea;
            transform: translateY(-2px);
        }
        
        .action-card:hover .action-desc {
            color: #667eea;
        }
        
        .logout-btn {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(231,76,60,0.3);
            position: relative;
            overflow: hidden;
        }
        
        .logout-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2) 50%, transparent 100%);
        }
        
        .logout-btn:hover {
            background: linear-gradient(135deg, #c0392b 0%, #a02d2e 100%);
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(192,57,43,0.4);
        }
        
        .welcome-message {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
            border: 2px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
        }
        
        .welcome-message h2 {
            margin: 0;
            color: #667eea;
            font-size: 1.5em;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .owner-dashboard {
                padding: 20px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .stat-card {
                padding: 25px;
            }
            
            .action-card {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="dashboard-header">
            <div class="dashboard-info">
                <h1><i class="fas fa-crown"></i> Owner Dashboard</h1>
                <p>Complete Business Management System</p>
            </div>
            <div>
                <button class="logout-btn" onclick="window.location.href='index.php?logout=true'">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </div>

        <!-- Welcome Message -->
        <div class="welcome-message">
            <h2><i class="fas fa-hand-wave"></i> Welcome, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Owner'); ?>!</h2>
            <p>Manage your entire business from one powerful dashboard</p>
        </div>

        <!-- Statistics Overview -->
        <div class="stats-grid">
            <div class="stat-card branches">
                <div class="stat-icon"><i class="fas fa-building"></i></div>
                <div class="stat-value"><?php echo number_format($stats['total_branches']); ?></div>
                <div class="stat-label">Total Branches</div>
            </div>
            
            <div class="stat-card staff">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-value"><?php echo number_format($stats['total_staff']); ?></div>
                <div class="stat-label">Total Staff</div>
            </div>
            
            <div class="stat-card customers">
                <div class="stat-icon"><i class="fas fa-user-friends"></i></div>
                <div class="stat-value"><?php echo number_format($stats['total_customers']); ?></div>
                <div class="stat-label">Total Customers</div>
            </div>
            
            <div class="stat-card products">
                <div class="stat-icon"><i class="fas fa-box"></i></div>
                <div class="stat-value"><?php echo number_format($stats['total_products']); ?></div>
                <div class="stat-label">Total Products</div>
            </div>
            
            <div class="stat-card stock-value">
                <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                <div class="stat-value"><?php echo number_format($stats['total_stock_value'], 2); ?> EGP</div>
                <div class="stat-label">Total Stock Value</div>
            </div>
            
            <div class="stat-card today-sales">
                <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                <div class="stat-value"><?php echo number_format($stats['today_sales_count']); ?></div>
                <div class="stat-label">Today's Sales</div>
            </div>
            
            <div class="stat-card month-sales">
                <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="stat-value"><?php echo number_format($stats['month_sales_amount'], 2); ?> EGP</div>
                <div class="stat-label">This Month's Sales</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="owner_branches.php" class="action-card">
                <div class="action-icon"><i class="fas fa-building"></i></div>
                <div class="action-title">Branch Management</div>
                <div class="action-desc">Manage all branches</div>
            </a>
            
            <a href="owner_staff.php" class="action-card">
                <div class="action-icon"><i class="fas fa-users-cog"></i></div>
                <div class="action-title">Staff Management</div>
                <div class="action-desc">Manage all staff across branches</div>
            </a>
            
            <a href="owner_customers.php" class="action-card">
                <div class="action-icon"><i class="fas fa-user-friends"></i></div>
                <div class="action-title">Customer Management</div>
                <div class="action-desc">Manage all customers</div>
            </a>
            
            <a href="owner_stock.php" class="action-card">
                <div class="action-icon"><i class="fas fa-warehouse"></i></div>
                <div class="action-title">Stock Management</div>
                <div class="action-desc">View stock across all branches</div>
            </a>
            
            <a href="owner_reports.php" class="action-card">
                <div class="action-icon"><i class="fas fa-chart-bar"></i></div>
                <div class="action-title">Reports & Analytics</div>
                <div class="action-desc">View comprehensive reports</div>
            </a>
            
            <a href="owner_settings.php" class="action-card">
                <div class="action-icon"><i class="fas fa-cogs"></i></div>
                <div class="action-title">System Settings</div>
                <div class="action-desc">Configure system settings</div>
            </a>
        </div>
    </div>

    <script src="assets/js/translations.js"></script>
    <script>
        // Initialize language manager
        if (typeof langManager !== 'undefined') {
            langManager.init();
        }
    </script>
</body>
</html>
