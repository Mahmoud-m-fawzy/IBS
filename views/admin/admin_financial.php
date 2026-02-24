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
error_log("Admin financial dashboard accessed - User ID: " . $_SESSION['user_id'] . ", Username: " . ($_SESSION['username'] ?? 'Not set'));

include_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get financial data
$income = [];
$expenses = [];
$transactions = [];
$searchTerm = '';
$selectedCategory = '';
$selectedPeriod = '';

try {
    // Get income entries
    $incomeQuery = "SELECT i.*, c.name as category_name FROM income i 
                     LEFT JOIN categories c ON i.category_id = c.id 
                     WHERE DATE(i.entry_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                     ORDER BY i.entry_date DESC";
    $incomeStmt = $db->prepare($incomeQuery);
    $incomeStmt->execute();
    while ($row = $incomeStmt->fetch(PDO::FETCH_ASSOC)) {
        $income[] = [
            'id' => (int) $row['id'],
            'description' => $row['description'],
            'price' => (float) $row['price'],
            'entry_date' => $row['entry_date'],
            'category_name' => $row['category_name'],
            'created_at' => $row['created_at']
        ];
    }
    
    // Get expense entries
    $expenseQuery = "SELECT e.*, c.name as category_name FROM payment e 
                     LEFT JOIN categories c ON e.category_id = c.id 
                     WHERE DATE(e.entry_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                     ORDER BY e.entry_date DESC";
    $expenseStmt = $db->prepare($expenseQuery);
    $expenseStmt->execute();
    while ($row = $expenseStmt->fetch(PDO::FETCH_ASSOC)) {
        $expenses[] = [
            'id' => (int) $row['id'],
            'description' => $row['description'],
            'amount' => (float) $row['amount'],
            'entry_date' => $row['entry_date'],
            'payment_method' => $row['payment_method'],
            'reference_number' => $row['reference_number'],
            'category_name' => $row['category_name'],
            'created_at' => $row['created_at']
        ];
    }
    
    // Get categories for filtering
    $categoryQuery = "SELECT * FROM categories ORDER BY name";
    $categoryStmt = $db->prepare($categoryQuery);
    $categoryStmt->execute();
    while ($row = $categoryStmt->fetch(PDO::FETCH_ASSOC)) {
        $categories[] = [
            'id' => (int) $row['id'],
            'name' => $row['name']
        ];
    }
    
    // Handle search and filtering
    if (isset($_GET['search'])) {
        $searchTerm = $_GET['search'];
    }
    
    if (isset($_GET['category'])) {
        $selectedCategory = $_GET['category'];
    }
    
    if (isset($_GET['period'])) {
        $selectedPeriod = $_GET['period'];
    }
    
    // Get filtered transactions
    $whereClause = "1=1";
    $params = [];
    
    if (!empty($searchTerm)) {
        $whereClause .= " AND (i.description LIKE ? OR i.price LIKE ?)";
        $params[] = "%$searchTerm%";
    }
    
    if (!empty($selectedCategory)) {
        $whereClause .= " AND i.category_id = ?";
        $params[] = $selectedCategory;
    }
    
    if (!empty($selectedPeriod)) {
        switch ($selectedPeriod) {
            case '7':
                $whereClause .= " AND DATE(i.entry_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                break;
            case '30':
                $whereClause .= " AND DATE(i.entry_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                break;
            case '90':
                $whereClause .= " AND DATE(i.entry_date) >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)";
                break;
            default:
                $whereClause .= " AND DATE(i.entry_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                break;
        }
    }
    
    // Apply filters
    $incomeQuery = "SELECT i.*, c.name as category_name FROM income i 
                     LEFT JOIN categories c ON i.category_id = c.id 
                     WHERE $whereClause 
                     ORDER BY i.entry_date DESC 
                     LIMIT 50";
    $incomeStmt = $db->prepare($incomeQuery);
    $incomeStmt->execute($params);
    
    while ($row = $incomeStmt->fetch(PDO::FETCH_ASSOC)) {
        $income[] = [
            'id' => (int) $row['id'],
            'description' => $row['description'],
            'price' => (float) $row['price'],
            'entry_date' => $row['entry_date'],
            'category_name' => $row['category_name'],
            'created_at' => $row['created_at']
        ];
    }
    
    // Get filtered expenses
    $expenseQuery = "SELECT e.*, c.name as category_name FROM payment e 
                     LEFT JOIN categories c ON e.category_id = c.id 
                     WHERE $whereClause 
                     ORDER BY e.entry_date DESC 
                     LIMIT 50";
    $expenseStmt = $db->prepare($expenseQuery);
    $expenseStmt->execute($params);
    
    while ($row = $expenseStmt->fetch(PDO::FETCH_ASSOC)) {
        $expenses[] = [
            'id' => (int) $row['id'],
            'description' => $row['description'],
            'amount' => (float) $row['amount'],
            'entry_date' => $row['entry_date'],
            'payment_method' => $row['payment_method'],
            'reference_number' => $row['reference_number'],
            'category_name' => $row['category_name'],
            'created_at' => $row['created_at']
        ];
    }
    
    // Calculate financial statistics
    $totalIncome = array_sum(array_column($income, 'price'));
    $totalExpenses = array_sum(array_column($expenses, 'amount'));
    $netProfit = $totalIncome - $totalExpenses;
    
    // Calculate period-specific statistics
    $periodIncome = array_sum(array_filter($income, function($transaction) {
        switch ($selectedPeriod) {
            case '7':
                return strtotime($transaction['entry_date']) >= strtotime('-7 days');
            case '30':
                return strtotime($transaction['entry_date']) >= strtotime('-30 days');
            default:
                return strtotime($transaction['entry_date']) >= strtotime('-30 days');
        }
    }));
    
    $periodExpenses = array_filter($expenses, function($transaction) {
        switch ($selectedPeriod) {
            case '7':
                return strtotime($transaction['entry_date']) >= strtotime('-7 days');
            case '30':
                return strtotime($transaction['entry_date']) >= strtotime('-30 days');
            default:
                return strtotime($transaction['entry_date']) >= strtotime('-30 days');
        }
    }));
    
    $periodIncome = array_sum(array_filter($income, function($transaction) {
        switch ($selectedPeriod) {
            case '7':
                return strtotime($transaction['entry_date']) >= strtotime('-7 days');
            case '30':
                return strtotime($transaction['entry_date']) >= strtotime('-30 days');
            default:
                return strtotime($transaction['entry_date']) >= strtotime('-30 days');
        }
    }));
    
    $periodExpenses = array_sum(array_filter($expenses, function($transaction) {
        switch ($selectedPeriod) {
            case '7':
                return strtotime($transaction['entry_date']) >= strtotime('-7 days');
            case '30':
                return strtotime($transaction['entry_date']) >= strtotime('-30 days');
            default:
                return strtotime($transaction['entry_date']) >= strtotime('-30 days');
        }
    }));
    
    // Calculate growth metrics
    $incomeGrowth = 0;
    $expenseGrowth = 0;
    if ($periodIncome > 0) {
        $previousPeriodIncome = array_sum(array_filter($income, function($transaction) {
            switch ($selectedPeriod) {
                case '7':
                    return strtotime($transaction['entry_date']) >= strtotime('-14 days') && strtotime($transaction['entry_date']) < strtotime('-7 days');
                case '30':
                    return strtotime($transaction['entry_date']) >= strtotime('-60 days') && strtotime($transaction['entry_date']) < strtotime('-30 days');
            default:
                    return strtotime($transaction['entry_date']) >= strtotime('-30 days');
            }
        }));
        
        $incomeGrowth = (($periodIncome - $previousPeriodIncome) / $previousPeriodIncome) * 100;
    }
    
    if ($periodExpenses > 0) {
        $previousPeriodExpenses = array_sum(array_filter($expenses, function($transaction) {
            switch ($selectedPeriod) {
                case '7':
                    return strtotime($transaction['entry_date']) >= strtotime('-14 days') && strtotime($transaction['entry_date']) < strtotime('-7 days');
                case '30':
                    return strtotime($transaction['entry_date']) >= strtotime('-60 days') && strtotime($transaction['entry_date']) < strtotime('-30 days');
            default:
                    return strtotime($transaction['entry_date']) >= strtotime('-30 days');
            }
        }));
        
        $expenseGrowth = (($periodExpenses - $previousPeriodExpenses) / $previousPeriodExpenses) * 100;
    }
    
    // Get top income categories
    $incomeByCategoryQuery = "SELECT c.name, COUNT(i.id) as count, SUM(i.price) as total 
                        FROM income i 
                        LEFT JOIN categories c ON i.category_id = c.id 
                        WHERE DATE(i.entry_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                        GROUP BY c.id 
                        ORDER BY total DESC 
                        LIMIT 5";
    $incomeByCategoryStmt = $db->prepare($incomeByCategoryQuery);
    $incomeByCategoryStmt->execute();
    $incomeByCategory = $incomeByCategoryStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get top expense categories
    $expenseByCategoryQuery = "SELECT c.name, COUNT(e.id) as count, SUM(e.amount) as total 
                        FROM payment e 
                        LEFT JOIN categories c ON e.category_id = c.id 
                        WHERE DATE(e.entry_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                        GROUP BY c.id 
                        ORDER BY total DESC 
                        LIMIT 5";
    $expenseByCategoryStmt->execute();
    $expenseByCategoryStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate efficiency metrics
    $avgMonthlyIncome = $periodIncome / max(1, date('t', $periodIncome));
    $avgMonthlyExpenses = $periodExpenses / max(1, date('t', $periodExpenses));
    $profitMargin = $totalIncome > 0 ? ($netProfit / $totalIncome) * 100 : 0;
    
} catch (Exception $e) {
    error_log("Error in admin financial dashboard: " . $e->getMessage());
    $income = [];
    $expenses = [];
    $transactions = [];
    $totalIncome = 0;
    $totalExpenses = 0;
    $netProfit = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Dashboard - IBS</title>
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
        
        .financial-dashboard {
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
        
        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .financial-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 12px rgba(0,0,0,0.15);
        }
        
        .summary-card h3 {
            font-size: 16px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .summary-card .amount {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        
        .summary-card .amount.positive {
            color: #28a745;
        }
        
        .summary-card .amount.negative {
            color: #dc3545;
        }
        
        .summary-card .label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
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
    </style>
</head>
<body>
    <div class="financial-dashboard">
        <div class="dashboard-header">
            <h1 class="dashboard-title">💰 Financial Dashboard</h1>
            <p>Income, Expenses & Profit Analysis</p>
        </div>
        
        <!-- Financial Summary -->
        <div class="financial-summary">
            <div class="summary-card">
                <h3>💵 Total Income</h3>
                <div class="amount amount-positive">EGP <?php echo number_format($totalIncome, 2); ?></div>
                <div class="label">Selected Period</div>
            </div>
            
            <div class="summary-card">
                <h3>💸 Total Expenses</h3>
                <div class="amount amount-negative">EGP <?php echo number_format($totalExpenses, 2); ?></div>
                <div class="label">Selected Period</div>
            </div>
            
            <div class="summary-card">
                <h3>📊 Net Profit</h3>
                <div class="amount <?php echo $netProfit >= 0 ? 'amount-positive' : 'amount-negative'; ?>">
                    EGP <?php echo number_format($netProfit, 2); ?>
                </div>
                <div class="label">Selected Period</div>
            </div>
            
            <div class="summary-card">
                <h3>📈 Profit Margin</h3>
                <div class="amount amount-positive"><?php echo number_format($profitMargin, 1); ?>%</div>
                <div class="label">Income vs Expenses Ratio</div>
            </div>
        </div>
        
        <!-- Growth Metrics -->
        <div class="summary-card">
                <h3>📈 Income Growth</h3>
                <div class="amount">
                    <?php echo number_format($incomeGrowth, 1); ?>%
                    <span class="trend-indicator <?php echo $incomeGrowth >= 0 ? 'trend-up' : 'trend-down'; ?>">
                        <?php echo $incomeGrowth >= 0 ? '↑' : '↓'; ?>
                    </span>
                </div>
                <div class="label">Period over Period</div>
            </div>
        </div>
        
        <!-- Monthly Trends Chart -->
        <div class="chart-container">
            <h3>📊 Monthly Financial Trends</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Income</th>
                        <th>Expenses</th>
                        <th>Profit</th>
                        <th>Growth</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $monthlyData = [];
                    
                    // Generate last 6 months of data
                    for ($i = 5; $i >= 0; $i--) {
                        $month = date('Y-m', strtotime("-$i months"));
                        $monthIncome = 0;
                        $monthExpenses = 0;
                        $monthProfit = 0;
                        
                        foreach ($income as $transaction) {
                            if (strtotime($transaction['entry_date']) >= strtotime("-$i months")) {
                                $monthIncome += $transaction['price'];
                            }
                        }
                        
                        foreach ($expenses as $transaction) {
                            if (strtotime($transaction['entry_date']) >= strtotime("-$i months")) {
                                $monthExpenses += $transaction['amount'];
                            }
                        }
                        
                        $monthProfit = $monthIncome - $monthExpenses;
                        
                        $monthlyData[] = [
                            'month' => date('F Y', $month),
                            'income' => $monthIncome,
                            'expenses' => $monthExpenses,
                            'profit' => $monthProfit
                        ];
                    }
                    
                    foreach ($monthlyData as $data) {
                        $monthlyData[] = [
                            'month' => date('F Y', $data['month']),
                            'income' => $data['income'],
                            'expenses' => $data['expenses'],
                            'profit' => $data['profit']
                        ];
                    }
                    
                    // Calculate growth rates
                    if ($data['month'] > 0) {
                        $prevMonth = date('Y-m', strtotime("-" . ($data['month'] + 1) . " months"));
                        $prevIncome = isset($monthlyData[$data['month'] - 1]) ? $monthlyData[$data['month'] - 1]['income'] : 0;
                        $prevExpenses = isset($monthlyData[$data['month'] - 1]) ? $monthlyData[$data['month'] - 1]['expenses'] : 0;
                        
                        $monthlyData[$data['month']]['growth'] = $prevIncome > 0 ? (($data['income'] - $prevIncome) / $prevIncome) * 100 : 0;
                    }
                }
                    
                    // Calculate average values
                    $avgMonthlyIncome = array_sum(array_column($monthlyData, 'income')) / max(1, count($monthlyData));
                    $avgMonthlyExpenses = array_sum(array_column($monthlyData, 'expenses')) / max(1, count($monthlyData));
                    $avgMonthlyProfit = array_sum(array_column($monthlyData, 'profit')) / max(1, count($monthlyData));
                }
            }
            
            // Output monthly data table
                    <tr>
                        <td><?php echo date('F Y', $data['month']); ?></td>
                        <td class="amount-positive">EGP <?php echo number_format($data['income'], 2); ?></td>
                        <td class="amount-negative">EGP <?php echo number_format($data['expenses'], 2); ?></td>
                        <td class="amount <?php echo $data['profit'] >= 0 ? 'amount-positive' : 'amount-negative'; ?>">
                            EGP <?php echo number_format($data['profit'], 2); ?>
                        </td>
                        <td>
                            <?php 
                            $growth = isset($monthlyData[$data['month']]['growth']) ? $monthlyData[$data['month']]['growth'] : 0;
                            echo number_format($growth, 1); ?>%
                            <span class="trend-indicator <?php echo $growth >= 0 ? 'trend-up' : 'trend-down'; ?>">
                                <?php echo $growth >= 0 ? '↑' : '↓'; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Top Income Sources -->
        <div class="chart-container">
            <h3>💰 Top Income Sources</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Source</th>
                        <th>Total</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($incomeByCategory as $category): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                        <td class="amount-positive">EGP <?php echo number_format($category['total'], 2); ?></td>
                        <td><?php echo $category['count']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Top Expense Categories -->
        <div class="chart-container">
            <h3>💸 Top Expense Categories</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Total</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expenseByCategory as $category): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                        <td class="amount-negative">EGP <?php echo number_format($category['total'], 2); ?></td>
                        <td><?php echo $category['count']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="owner_dashboard.php" class="btn btn-primary">📊 Dashboard</a>
            <a href="owner_customers.php" class="btn btn-success">👥 Customer Management</a>
            <a href="owner_staff.php" class="btn btn-primary">👥 Staff Management</a>
            <a href="owner_products.php" class="btn btn-primary">📦 Product Management</a>
            <a href="owner_financial.php" class="btn btn-primary">💰 Financial Dashboard</a>
        </div>
    </div>
</body>
</html>
