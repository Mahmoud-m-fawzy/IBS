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
error_log("Owner financial dashboard accessed - User ID: " . $_SESSION['user_id'] . ", Username: " . ($_SESSION['username'] ?? 'Not set'));

include_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get financial data
$financials = [];
try {
    // Get income entries
    $incomeQuery = "SELECT i.*, b.name as branch_name FROM income i 
                          LEFT JOIN branches b ON i.branch_id = b.id 
                          WHERE DATE(i.entry_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                          ORDER BY i.entry_date DESC";
    $incomeStmt = $db->prepare($incomeQuery);
    $incomeStmt->execute();
    $incomeEntries = [];
    
    while ($row = $incomeStmt->fetch(PDO::FETCH_ASSOC)) {
        $incomeEntries[] = [
            'id' => (int) $row['id'],
            'description' => $row['description'],
            'price' => (float) $row['price'],
            'entry_date' => $row['entry_date'],
            'branch_name' => $row['branch_name'] ?? 'Not Assigned',
            'type' => 'income'
            'created_at' => $row['created_at']
        ];
    }
    
    // Get expense entries
    $expenseQuery = "SELECT e.*, b.name as branch_name FROM payment e 
                          LEFT JOIN branches b ON e.branch_id = b.id 
                          WHERE DATE(e.entry_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                          ORDER BY e.entry_date DESC";
    $expenseStmt = $db->prepare($expenseQuery);
    $expenseStmt->execute();
    $expenseEntries = [];
    
    while ($row = $expenseStmt->fetch(PDO::FETCH_ASSOC)) {
        $expenseEntries[] = [
            'id' => (int) $row['id'],
            'description' => $row['description'],
            'amount' => (float) $row['amount'],
            'entry_date' => $row['entry_date'],
            'branch_name' => $row['branch_name'] ?? 'Not Assigned',
            'payment_method' => $row['payment_method'],
            'reference_number' => $row['reference_number'],
            'type' => 'expense',
            'created_at' => $row['created_at']
        ];
    }
    
    // Calculate totals
    $totalIncome = array_sum(array_column($incomeEntries, 'price'));
    $totalExpenses = array_sum(array_column($expenseEntries, 'amount'));
    $netProfit = $totalIncome - $totalExpenses;
    
    // Get monthly trends
    $monthlyIncomeQuery = "SELECT 
            DATE_FORMAT(entry_date, '%Y-%m') as month,
            SUM(price) as total_income
                          FROM income 
                          WHERE entry_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                          GROUP BY DATE_FORMAT(entry_date, '%Y-%m')
                          ORDER BY month";
    $monthlyIncomeStmt = $db->prepare($monthlyIncomeQuery);
    $monthlyIncomeStmt->execute();
    $monthlyIncomeData = $monthlyIncomeStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $monthlyExpenseQuery = "SELECT 
            DATE_FORMAT(entry_date, '%Y-%m') as month,
            SUM(amount) as total_expense
                          FROM payment 
                          WHERE entry_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                          GROUP BY DATE_FORMAT(entry_date, '%Y-%m')
                          ORDER BY month";
    $monthlyExpenseStmt = $db->prepare($monthlyExpenseQuery);
    $monthlyExpenseStmt->execute();
    $monthlyExpenseData = $monthlyExpenseStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Merge monthly data
    $monthlyData = [];
    foreach ($monthlyIncomeData as $monthData) {
        $month = $monthData['month'];
        $monthlyData[$month] = [
            'month' => $month,
            'income' => (float) $monthData['total_income'],
            'expenses' => 0
        ];
    }
    
    foreach ($monthlyExpenseData as $monthData) {
        $month = $monthData['month'];
        if (isset($monthlyData[$month])) {
            $monthlyData[$month]['expenses'] = (float) $monthData['total_expense'];
        }
    }
    
    // Calculate monthly profit
    foreach ($monthlyData as &$monthData) {
        $monthData['profit'] = $monthData['income'] - $monthData['expenses'];
    }
    
    // Get top income sources
    $incomeByCategoryQuery = "SELECT 
            description,
            SUM(price) as total,
            COUNT(*) as count
                          FROM income 
                          WHERE entry_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                          GROUP BY description
                          ORDER BY total DESC
                          LIMIT 5";
    $incomeByCategoryStmt = $db->prepare($incomeByCategoryQuery);
    $incomeByCategoryStmt->execute();
    $incomeByCategory = $incomeByCategoryStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get top expense categories
    $expenseByCategoryQuery = "SELECT 
            payment_method as category,
            SUM(amount) as total,
            COUNT(*) as count
                          FROM payment 
                          WHERE entry_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                          GROUP BY payment_method
                          ORDER BY total DESC
                          LIMIT 5";
    $expenseByCategoryStmt = $db->prepare($expenseByCategoryQuery);
    $expenseByCategoryStmt->execute();
    $expenseByCategory = $expenseByCategoryStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate financial ratios
    $financials['total_income'] = $totalIncome;
    $financials['total_expenses'] = $totalExpenses;
    $financials['net_profit'] = $netProfit;
    $financials['profit_margin'] = $totalIncome > 0 ? ($netProfit / $totalIncome) * 100 : 0;
    $financials['income_entries'] = $incomeEntries;
    $financials['expense_entries'] = $expenseEntries;
    $financials['monthly_data'] = $monthlyData;
    $financials['income_by_category'] = $incomeByCategory;
    $financials['expenses_by_category'] = $expenseByCategory;
    
    // Calculate growth metrics
    $lastMonthIncome = end($monthlyData)['income'] ?? 0;
    $thisMonthIncome = 0;
    foreach ($monthlyData as $data) {
        if ($data['month'] === date('Y-m')) {
            $thisMonthIncome = $data['income'];
            break;
        }
    }
    
    $incomeGrowth = $lastMonthIncome > 0 ? (($thisMonthIncome - $lastMonthIncome) / $lastMonthIncome) * 100 : 0;
    
    // Calculate efficiency metrics
    $avgMonthlyIncome = array_sum(array_column($monthlyData, 'income')) / max(1, count($monthlyData));
    $avgMonthlyExpenses = array_sum(array_column($monthlyData, 'expenses')) / max(1, count($monthlyData));
    $financials['avg_monthly_income'] = $avgMonthlyIncome;
    $financials['avg_monthly_expenses'] = $avgMonthlyExpenses;
    $financials['income_growth_rate'] = $incomeGrowth;
    
} catch (Exception $e) {
    error_log("Error getting financial data: " . $e->getMessage());
    $financials = [
        'total_income' => 0,
        'total_expenses' => 0,
        'net_profit' => 0,
        'profit_margin' => 0,
        'income_entries' => [],
        'expense_entries' => [],
        'monthly_data' => [],
        'income_by_category' => [],
        'expenses_by_category' => [],
        'avg_monthly_income' => 0,
        'avg_monthly_expenses' => 0,
        'income_growth_rate' => 0
    ];
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
            margin-bottom: 5px;
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
        
        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
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
        
        .trend-indicator {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .trend-up {
            background: #28a745;
            color: white;
        }
        
        .trend-down {
            background: #dc3545;
            color: white;
        }
        
        .trend-neutral {
            background: #6c757d;
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
                <div class="amount amount-positive">EGP <?php echo number_format($financials['total_income'], 2); ?></div>
                <div class="label">Last 30 Days</div>
            </div>
            
            <div class="summary-card">
                <h3>💸 Total Expenses</h3>
                <div class="amount amount-negative">EGP <?php echo number_format($financials['total_expenses'], 2); ?></div>
                <div class="label">Last 30 Days</div>
            </div>
            
            <div class="summary-card">
                <h3>📊 Net Profit</h3>
                <div class="amount <?php echo $financials['net_profit'] >= 0 ? 'amount-positive' : 'amount-negative'; ?>">
                    EGP <?php echo number_format($financials['net_profit'], 2); ?>
                </div>
                <div class="label">Last 30 Days</div>
            </div>
            
            <div class="summary-card">
                <h3>📈 Profit Margin</h3>
                <div class="amount amount-positive"><?php echo number_format($financials['profit_margin'], 1); ?>%</div>
                <div class="label">Income vs Expenses Ratio</div>
            </div>
            
            <div class="summary-card">
                <h3>📊 Avg Monthly Income</h3>
                <div class="amount amount-positive">EGP <?php echo number_format($financials['avg_monthly_income'], 2); ?></div>
                <div class="label">12-Month Average</div>
            </div>
            
            <div class="summary-card">
                <h3>📉 Avg Monthly Expenses</h3>
                <div class="amount amount-negative">EGP <?php echo number_format($financials['avg_monthly_expenses'], 2); ?></div>
                <div class="label">12-Month Average</div>
            </div>
            
            <div class="summary-card">
                <h3>📈 Income Growth</h3>
                <div class="amount">
                    <?php echo number_format($financials['income_growth_rate'], 1); ?>%
                    <span class="trend-indicator <?php echo $financials['income_growth_rate'] >= 0 ? 'trend-up' : 'trend-down'; ?>">
                        <?php echo $financials['income_growth_rate'] >= 0 ? '↑' : '↓'; ?>
                    </span>
                </div>
                <div class="label">Month over Month</div>
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
                    <?php foreach ($financials['monthly_data'] as $data): ?>
                    <tr>
                        <td><?php echo date('F Y', strtotime($data['month'])); ?></td>
                        <td class="amount-positive">EGP <?php echo number_format($data['income'], 2); ?></td>
                        <td class="amount-negative">EGP <?php echo number_format($data['expenses'], 2); ?></td>
                        <td class="amount <?php echo $data['profit'] >= 0 ? 'amount-positive' : 'amount-negative'; ?>">
                            EGP <?php echo number_format($data['profit'], 2); ?>
                        </td>
                        <td>
                            <?php 
                            $growth = isset($prevData) ? (($data['income'] - $prevData['income']) / $prevData['income']) * 100 : 0;
                            echo number_format($growth, 1); ?>%
                            ?>
                            <span class="trend-indicator <?php echo $growth >= 0 ? 'trend-up' : 'trend-down'; ?>">
                                <?php echo $growth >= 0 ? '↑' : '↓'; ?>
                            </span>
                        </td>
                    </tr>
                    <?php 
                    $prevData = $data; 
                    endforeach; ?>
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
                    <?php foreach ($financials['income_by_category'] as $source): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($source['description']); ?></td>
                        <td class="amount-positive">EGP <?php echo number_format($source['total'], 2); ?></td>
                        <td><?php echo $source['count']; ?></td>
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
                    <?php foreach ($financials['expenses_by_category'] as $category): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($category['category']); ?></td>
                        <td class="amount-negative">EGP <?php echo number_format($category['total'], 2); ?></td>
                        <td><?php echo $category['count']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Action Buttons -->
        <div class="action-buttons">
            <button class="btn btn-primary" onclick="window.location.href='owner_dashboard.php'">📊 Dashboard</button>
            <button class="btn btn-success" onclick="window.location.href='owner_customers.php'">👥 Customers</button>
            <button class="btn btn-primary" onclick="window.location.href='owner_staff.php'">👥 Staff</button>
            <button class="btn btn-primary" onclick="window.location.href='owner_stock.php'">📦 Inventory</button>
        </div>
    </div>
</body>
</html>
