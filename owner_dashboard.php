<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header('Location: index.php');
    exit();
}

require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get initial settings
$settings = [];
try {
    $stmt = $db->query("SELECT key_name, key_value FROM settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['key_name']] = $row['key_value'];
    }
} catch (Exception $e) {}

$company_name = $settings['company_name'] ?? 'IBS Store';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Hub | <?php echo htmlspecialchars($company_name); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="components/css/style.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --sidebar-bg: #1e293b;
            --sidebar-hover: #334155;
            --content-bg: #f8fafc;
        }
        
        body { background: var(--content-bg); color: #1e293b; font-family: 'Inter', sans-serif; margin: 0; padding: 0; }
        
        /* Layout */
        .layout { display: flex; min-height: 100vh; }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: var(--sidebar-bg);
            color: white;
            position: fixed;
            height: 100vh;
            display: flex;
            flex-direction: column;
            z-index: 1000;
            transition: 0.3s;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar-header {
            padding: 30px 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            background: rgba(0,0,0,0.1);
        }

        .nav-list { padding: 20px 15px; flex-grow: 1; overflow-y: auto; }
        .nav-link {
            display: flex; align-items: center; gap: 12px;
            padding: 14px 18px;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 12px;
            margin-bottom: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }
        .nav-link:hover { color: white; background: var(--sidebar-hover); transform: translateX(5px); }
        .nav-link.active { 
            background: var(--primary-blue);
            color: white;
            box-shadow: 0 4px 12px rgba(0, 86, 179, 0.3);
            transform: translateX(5px);
        }

        /* Language Dongle */
        .lang-dongle {
            margin: 20px;
            padding: 15px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .toggle-switch {
            width: 48px; height: 24px;
            background: #0f172a;
            border-radius: 20px;
            position: relative;
            cursor: pointer;
            transition: 0.3s;
        }
        .toggle-circle {
            width: 18px; height: 18px;
            background: white;
            border-radius: 50%;
            position: absolute;
            top: 3px; left: 3px;
            transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .toggle-active .toggle-circle { left: 27px; background: var(--primary-blue); }

        /* Main Content */
        .main-content { margin-left: 280px; flex-grow: 1; padding: 40px; transition: 0.3s; }
        body.rtl .main-content { margin-left: 0; margin-right: 280px; }
        body.rtl .sidebar { right: 0; left: auto; }

        .dashboard-header {
            background: var(--gradient-primary);
            color: white;
            padding: 40px;
            border-radius: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }
        .dashboard-header::after {
            content: ''; position: absolute; top: -50%; right: -10%; width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        }

        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px; }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
            border-left: 6px solid var(--primary-blue);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-md); }
        .stat-val { font-size: 28px; font-weight: 800; color: #1e293b; }
        .stat-lbl { color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }

        /* Tables & Cards */
        .card-table { background: white; border-radius: 24px; box-shadow: var(--shadow-sm); overflow: hidden; margin-top: 25px; }
        .table-head { padding: 25px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
        
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8fafc; text-align: left; padding: 18px 25px; font-size: 13px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 20px 25px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #1e293b; }
        tr:hover td { background: #f8fafc; }
        body.rtl th, body.rtl td { text-align: right; }

        .badge { padding: 6px 14px; border-radius: 30px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .b-safe { background: #dcfce7; color: #15803d; }
        .b-warn { background: #fee2e2; color: #b91c1c; }

        .tab-pane { display: none; animation: fadeInUp 0.4s ease-out; }
        .tab-pane.active { display: block; }

        .action-btn {
            width: 36px; height: 36px; border-radius: 10px;
            display: inline-flex; align-items: center; justify-content: center;
            background: #f1f5f9; color: #64748b;
            border: 1px solid #e2e8f0; cursor: pointer; transition: 0.2s;
        }
        .action-btn:hover { background: var(--primary-blue); color: white; border-color: var(--primary-blue); }

        /* Custom Tabs for Master Data */
        .master-tabs { display: flex; gap: 10px; padding: 20px 25px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
        .master-tab { 
            padding: 10px 20px; border-radius: 10px; cursor: pointer; 
            color: #64748b; font-weight: 700; font-size: 13px;
            transition: 0.3s;
        }
        .master-tab.active { background: white; color: var(--primary-blue); box-shadow: var(--shadow-sm); }

        @keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body id="body-main">
    <div class="layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="components/css/logo.jpeg" alt="IBS Logo" style="width: 40px; height: 40px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.2);">
                <div>
                    <h2 style="font-size: 18px; margin: 0; letter-spacing: -0.5px;">IBS Store</h2>
                    <span style="font-size: 11px; color: #94a3b8; text-transform: uppercase; font-weight: 600;">Enterprise Hub</span>
                </div>
            </div>

            <nav class="nav-list">
                <a class="nav-link active" onclick="switchTab('dashboard', this)" data-translate="owner.menu.dashboard">
                    <i class="fas fa-grid-2"></i> Overview
                </a>
                <a class="nav-link" onclick="switchTab('branches', this)" data-translate="owner.menu.branches">
                    <i class="fas fa-building-circle-check"></i> Branch Control
                </a>
                <a class="nav-link" onclick="switchTab('master', this)" data-translate="owner.menu.masterData">
                    <i class="fas fa-database"></i> Master Data
                </a>
                <a class="nav-link" onclick="switchTab('products', this)" data-translate="owner.menu.products">
                    <i class="fas fa-boxes-stacked"></i> Global Stock
                </a>
                <a class="nav-link" onclick="switchTab('analytics', this)" data-translate="owner.menu.analytics">
                    <i class="fas fa-chart-mixed"></i> BI Analytics
                </a>
                <a class="nav-link" onclick="switchTab('access', this)" data-translate="owner.menu.access">
                    <i class="fas fa-user-shield"></i> Access Control
                </a>
                <a class="nav-link" onclick="switchTab('settings', this)" data-translate="owner.menu.settings">
                    <i class="fas fa-sliders"></i> System Config
                </a>
            </nav>

            <div class="lang-dongle">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="lang-icon" style="font-size: 20px;">🌐</span>
                    <span class="lang-text" style="font-size: 13px; font-weight: 700;">EN</span>
                </div>
                <div class="toggle-switch" id="langToggle" onclick="cycleLanguage()">
                    <div class="toggle-circle"></div>
                </div>
            </div>

            <div style="padding: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
                <a href="?logout=true" class="nav-link" style="color: #fca5a5; margin: 0;">
                    <i class="fas fa-power-off"></i> <span data-translate="owner.menu.logout">Logout</span>
                </a>
            </div>
        </aside>

        <main class="main-content">
            <header class="dashboard-header">
                <div>
                    <h1 data-translate="owner.title" style="margin: 0; font-size: 32px; font-weight: 800;">Global Enterprise Control</h1>
                    <p data-translate="owner.subtitle" style="margin: 8px 0 0 0; opacity: 0.9; font-size: 15px;">Global performance across all branches</p>
                </div>

                <div style="display: flex; gap: 15px; align-items: center;">
                    <select id="branch-filter" style="padding: 12px 20px; border-radius: 14px; border: 1px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.1); color: white; outline: none; cursor: pointer; font-weight: 600;" onchange="loadStats()">
                        <option value="all">All Global Branches</option>
                    </select>
                    <div style="display: flex; align-items: center; gap: 12px; padding: 10px 20px; background: rgba(255,255,255,0.15); border-radius: 14px; backdrop-filter: blur(10px);">
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: white; color: var(--primary-blue); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px;">
                            <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                        </div>
                        <span style="font-size: 14px; font-weight: 700; color: white;"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    </div>
                </div>
            </header>

            <!-- Overview -->
            <div id="tab-dashboard" class="tab-pane active">
                <div class="stats-grid">
                    <div class="stat-card">
                        <span class="stat-lbl" data-translate="owner.stats.totalBranches">Total Branches</span>
                        <span class="stat-val" id="stat-branches">0</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-lbl" data-translate="owner.stats.monthlyRevenue">Monthly Revenue</span>
                        <span class="stat-val" id="stat-revenue">0</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-lbl" data-translate="owner.stats.monthlyUnits">Units Sold</span>
                        <span class="stat-val" id="stat-units">0</span>
                    </div>
                    <div class="stat-card" style="border-left-color: #10b981;">
                        <span class="stat-lbl" data-translate="owner.stats.monthlyProfit">Net Profit</span>
                        <span class="stat-val" id="stat-profit">0</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-lbl" data-translate="owner.stats.transactions">Total Transactions</span>
                        <span class="stat-val" id="stat-trans">0</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-lbl" data-translate="owner.stats.lowStock">Low Stock Items</span>
                        <span class="stat-val" id="stat-low">0</span>
                    </div>
                    <div class="stat-card" style="border-left-color: #ef4444;">
                        <span class="stat-lbl" data-translate="owner.stats.totalReturns">Total Returns</span>
                        <span class="stat-val" id="stat-returns">0</span>
                    </div>
                    <div class="stat-card" style="border-left-color: #f59e0b;">
                        <span class="stat-lbl" data-translate="owner.stats.bestPerforming">Best Performing</span>
                        <span class="stat-val" id="stat-best" style="font-size: 20px;">-</span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                    <div class="card-table" style="padding: 30px;">
                        <h3 style="margin: 0 0 25px 0; font-size: 18px; font-weight: 700;">Revenue Performance</h3>
                        <canvas id="mainChart" height="150"></canvas>
                    </div>
                    <div class="card-table" style="padding: 30px;">
                        <h3 style="margin: 0 0 25px 0; font-size: 18px; font-weight: 700;">Category Mix</h3>
                        <canvas id="pieChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Branch Control -->
            <div id="tab-branches" class="tab-pane">
                <div class="card-table">
                    <div class="table-head">
                        <h3 data-translate="owner.branches.title" style="margin: 0; font-size: 20px; font-weight: 800;">Branch Control Panel</h3>
                        <button class="btn" style="padding: 10px 24px; margin: 0;" data-translate="owner.branches.add">
                            <i class="fas fa-plus"></i> Add Branch
                        </button>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th data-translate="owner.branches.table.name">Branch Name</th>
                                <th data-translate="owner.branches.table.location">Location</th>
                                <th data-translate="owner.branches.table.revenue">Revenue</th>
                                <th data-translate="owner.branches.table.units">Units</th>
                                <th data-translate="owner.branches.table.status">Status</th>
                                <th data-translate="owner.branches.table.actions">Control</th>
                            </tr>
                        </thead>
                        <tbody id="branch-list"></tbody>
                    </table>
                </div>
            </div>

            <!-- Master Data -->
            <div id="tab-master" class="tab-pane">
                <div class="card-table">
                    <div class="table-head">
                        <h3 data-translate="owner.masterData.title" style="margin: 0; font-size: 20px; font-weight: 800;">Entity Management</h3>
                        <button class="action-btn" style="width: 140px; gap: 8px; font-weight: 700; border-radius: 12px;" onclick="openMasterModal()">
                            <i class="fas fa-plus"></i> Add New
                        </button>
                    </div>
                    <div class="master-tabs">
                        <div class="master-tab active" onclick="selectMaster('categories', this)">Categories</div>
                        <div class="master-tab" onclick="selectMaster('brands', this)">Brands</div>
                        <div class="master-tab" onclick="selectMaster('suppliers', this)">Suppliers</div>
                    </div>
                    <table>
                        <thead id="master-thead"></thead>
                        <tbody id="master-list"></tbody>
                    </table>
                </div>
            </div>

            <!-- Products -->
            <div id="tab-products" class="tab-pane">
                <div class="card-table">
                    <div class="table-head">
                        <h3 data-translate="owner.products.title" style="margin: 0; font-size: 20px; font-weight: 800;">Global Product Master</h3>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th data-translate="owner.products.table.name">Product & Model</th>
                                <th data-translate="owner.products.table.branch">Storage Branch</th>
                                <th data-translate="owner.products.table.purchase">Cost</th>
                                <th data-translate="owner.products.table.minPrice">Min Price</th>
                                <th data-translate="owner.products.table.market">Market Price</th>
                                <th data-translate="owner.products.table.stock">Stock</th>
                                <th data-translate="owner.products.table.status">Lock Status</th>
                            </tr>
                        </thead>
                        <tbody id="global-products"></tbody>
                    </table>
                </div>
            </div>

            <!-- Access Control -->
            <div id="tab-access" class="tab-pane">
                <div class="card-table">
                    <div class="table-head">
                        <h3 style="margin: 0; font-size: 20px; font-weight: 800;">System Users & Permissions</h3>
                        <button class="btn" style="padding: 10px 24px; margin: 0;" onclick="openUserModal()">
                            <i class="fas fa-user-plus"></i> Create Admin
                        </button>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="users-list"></tbody>
                    </table>
                </div>
            </div>

            <!-- System Settings -->
            <div id="tab-settings" class="tab-pane">
                <div class="card-table" style="max-width: 800px; padding: 40px;">
                    <h3 style="margin: 0 0 30px 0; font-size: 24px; font-weight: 800;">Global Preferences</h3>
                    <form id="global-settings-form" style="display: grid; gap: 20px;">
                        <div class="form-group" style="padding: 0; margin: 0;">
                            <label style="margin-bottom: 10px; display: block;">Company Display Name</label>
                            <input type="text" name="company_name" style="width: 100%; max-width: none;" value="<?php echo htmlspecialchars($company_name); ?>">
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group" style="padding: 0; margin: 0;">
                                <label style="margin-bottom: 10px; display: block;">Currency Symbol</label>
                                <input type="text" name="currency" style="width: 100%; max-width: none;" value="<?php echo htmlspecialchars($settings['currency'] ?? 'EGP'); ?>">
                            </div>
                            <div class="form-group" style="padding: 0; margin: 0;">
                                <label style="margin-bottom: 10px; display: block;">Tax Rate (%)</label>
                                <input type="number" name="tax_rate" style="width: 100%; max-width: none;" value="<?php echo htmlspecialchars($settings['tax_rate'] ?? '0'); ?>">
                            </div>
                        </div>
                        <div class="form-group" style="padding: 0; margin: 0;">
                            <label style="margin-bottom: 10px; display: block;">Invoicing Policy</label>
                            <textarea name="invoice_footer" style="width: 100%; max-width: none; height: 100px;"><?php echo htmlspecialchars($settings['invoice_footer'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" class="btn" style="padding: 18px; width: 100%; margin: 20px 0 0 0; font-size: 16px;">
                            Update Global Configuration
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="components/js/translations.js"></script>
    <script>
        // Tab Management
        function switchTab(tab, el) {
            document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
            el.classList.add('active');
            
            document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
            document.getElementById('tab-' + tab).classList.add('active');

            if(tab === 'dashboard') loadStats();
            if(tab === 'branches') loadBranches();
            if(tab === 'products') loadProducts();
            if(tab === 'master') selectMaster('categories');
            if(tab === 'access') loadUsers();
        }

        // Language Dongle
        function cycleLanguage() {
            const current = langManager.getCurrentLanguage();
            const next = current === 'en' ? 'ar' : 'en';
            document.getElementById('langToggle').classList.toggle('toggle-active');
            langManager.setLanguage(next);
            updateUIByLanguage(next);
        }

        function updateUIByLanguage(lang) {
            document.body.className = lang === 'ar' ? 'rtl' : '';
            document.body.setAttribute('dir', lang === 'ar' ? 'rtl' : 'ltr');
            document.querySelector('.lang-text').innerText = lang === 'en' ? 'EN' : 'AR';
            document.querySelector('.lang-icon').innerText = lang === 'en' ? '🌐' : '🇪🇬';
        }

        // Data Loaders
        async function loadStats() {
            const branchId = document.getElementById('branch-filter').value;
            try {
                const res = await fetch(`api/owner_stats.php?branch_id=${branchId}`);
                const { success, data } = await res.json();
                if(success) {
                    document.getElementById('stat-branches').innerText = data.total_branches;
                    document.getElementById('stat-revenue').innerText = data.total_revenue.toLocaleString();
                    document.getElementById('stat-units').innerText = data.total_units_sold.toLocaleString();
                    document.getElementById('stat-profit').innerText = data.net_profit.toLocaleString();
                    document.getElementById('stat-trans').innerText = data.total_transactions;
                    document.getElementById('stat-low').innerText = data.low_stock_products;
                    document.getElementById('stat-returns').innerText = data.total_returns;
                    document.getElementById('stat-best').innerText = data.best_branch;
                }
            } catch (e) {}
            renderCharts();
        }

        async function loadBranches() {
            const res = await fetch('api/owner_branches.php');
            const { success, data } = await res.json();
            const list = document.getElementById('branch-list');
            list.innerHTML = '';
            data.forEach(b => {
                list.innerHTML += `
                    <tr>
                        <td><strong>${b.name}</strong></td>
                        <td>${b.location}</td>
                        <td><strong>${parseFloat(b.total_revenue).toLocaleString()}</strong></td>
                        <td>${b.units_sold}</td>
                        <td><span class="badge ${b.is_active ? 'b-safe' : 'b-warn'}">${b.is_active ? 'Active' : 'Offline'}</span></td>
                        <td>
                            <button class="action-btn"><i class="fas fa-eye"></i></button>
                            <button class="action-btn"><i class="fas fa-cog"></i></button>
                        </td>
                    </tr>
                `;
            });
        }

        async function loadProducts() {
            const res = await fetch('api/owner_products.php');
            const { success, data } = await res.json();
            const list = document.getElementById('global-products');
            list.innerHTML = '';
            data.forEach(p => {
                list.innerHTML += `
                    <tr>
                        <td><strong>${p.model}</strong><br><small style="color: #64748b">${p.brand_name}</small></td>
                        <td><span class="badge" style="background: #f1f5f9; color: #64748b">${p.branch_name || 'HEAD OFFICE'}</span></td>
                        <td>${parseFloat(p.purchase_price).toLocaleString()}</td>
                        <td>${parseFloat(p.min_selling_price).toLocaleString()}</td>
                        <td style="color: var(--primary-blue); font-weight: 800;">${parseFloat(p.suggested_price).toLocaleString()}</td>
                        <td><strong style="color: ${p.quantity <= p.min_stock ? '#ef4444' : '#10b981'}">${p.quantity}</strong></td>
                        <td><i class="fas ${p.is_active ? 'fa-unlock' : 'fa-lock'}" style="color: ${p.is_active ? '#10b981' : '#ef4444'}"></i></td>
                    </tr>
                `;
            });
        }

        async function loadUsers() {
            const res = await fetch('api/users.php');
            const { success, data } = await res.json();
            const list = document.getElementById('users-list');
            list.innerHTML = '';
            data.forEach(u => {
                list.innerHTML += `
                    <tr>
                        <td><strong>${u.name}</strong><br><small>@${u.username}</small></td>
                        <td><span class="badge" style="background:#e0e7ff; color:#4338ca">${u.role}</span></td>
                        <td>${u.phone || '-'}</td>
                        <td><span class="badge ${u.is_active ? 'b-safe' : 'b-warn'}">${u.is_active ? 'Active' : 'Locked'}</span></td>
                        <td><button class="action-btn"><i class="fas fa-edit"></i></button></td>
                    </tr>
                `;
            });
        }

        async function selectMaster(type, el) {
            document.querySelectorAll('.master-tab').forEach(t => t.classList.remove('active'));
            el.classList.add('active');
            const res = await fetch(`api/${type}.php`);
            const { success, data } = await res.json();
            const head = document.getElementById('master-thead');
            const body = document.getElementById('master-list');
            body.innerHTML = '';
            if(type === 'categories') {
                head.innerHTML = '<tr><th>Category</th><th>Details</th><th>Actions</th></tr>';
                data.forEach(c => { body.innerHTML += `<tr><td><strong>${c.name}</strong></td><td>${c.description || '-'}</td><td><button class="action-btn"><i class="fas fa-edit"></i></button></td></tr>`; });
            } else if(type === 'brands') {
                head.innerHTML = '<tr><th>Brand</th><th>Contact</th><th>Actions</th></tr>';
                data.forEach(b => { body.innerHTML += `<tr><td><strong>${b.name}</strong></td><td>${b.contact_info || '-'}</td><td><button class="action-btn"><i class="fas fa-edit"></i></button></td></tr>`; });
            } else if(type === 'suppliers') {
                head.innerHTML = '<tr><th>Supplier</th><th>Person</th><th>Contact</th><th>Actions</th></tr>';
                data.forEach(s => { body.innerHTML += `<tr><td><strong>${s.name}</strong></td><td>${s.contact_person || '-'}</td><td>${s.phone || '-'}</td><td><button class="action-btn"><i class="fas fa-edit"></i></button></td></tr>`; });
            }
        }

        // BI Charts
        let mainChart = null, pieChart = null;
        async function renderCharts() {
            const res = await fetch('api/owner_analytics.php');
            const { success, data } = await res.json();
            if(!success) return;
            if(mainChart) mainChart.destroy();
            if(pieChart) pieChart.destroy();

            mainChart = new Chart(document.getElementById('mainChart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: data.monthly_trend.map(t => t.month),
                    datasets: [{
                        label: 'Gross Revenue',
                        data: data.monthly_trend.map(t => t.revenue),
                        borderColor: '#0056b3',
                        borderWidth: 4,
                        tension: 0.4,
                        fill: true,
                        backgroundColor: 'rgba(0, 86, 179, 0.05)'
                    }]
                },
                options: { plugins: { legend: { display: false } }, responsive: true }
            });

            pieChart = new Chart(document.getElementById('pieChart').getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: data.sales_by_category.map(c => c.name),
                    datasets: [{
                        data: data.sales_by_category.map(c => c.count),
                        backgroundColor: ['#0056b3', '#2563eb', '#3b82f6', '#60a5fa', '#93c5fd'],
                        borderWidth: 0
                    }]
                },
                options: { plugins: { legend: { position: 'bottom' } }, cutout: '75%' }
            });
        }

        // Init
        window.addEventListener('load', () => {
            const lang = localStorage.getItem('language') || 'en';
            if(lang === 'ar') document.getElementById('langToggle').classList.add('toggle-active');
            updateUIByLanguage(lang);
            langManager.applyLanguage(lang);
            loadStats();
            loadBranches();
        });
    </script>
</body>
</html>
