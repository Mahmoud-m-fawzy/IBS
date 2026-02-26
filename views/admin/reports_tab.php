<?php
// Enterprise Reports Tab Content
?>
<div id="reports" class="tab-content transition-fade">
    <div class="reports-container">
        <!-- Section 1: Filter Panel (Glassmorphism) -->
        <div class="report-card filter-section glass-effect">
            <div class="filter-header">
                <h2 class="premium-title"><span class="title-accent"></span> <span data-translate="reports.title">Operational Reports</span></h2>
                <div class="filter-actions">
                    <button class="btn-premium btn-primary-premium" onclick="applyReportFilter()">
                        <span class="btn-icon">🔍</span> <span data-translate="reports.apply">Apply</span>
                    </button>
                    <button class="btn-premium btn-secondary-premium" onclick="resetReportFilter()">
                        <span class="btn-icon">🔄</span> <span data-translate="reports.reset">Reset</span>
                    </button>
                    <button class="btn-premium btn-success-premium" onclick="printBranchReport()">
                        <span class="btn-icon">🖨️</span> <span data-translate="reports.printReport">Print Report</span>
                    </button>
                </div>
            </div>
            <div class="filter-grid">
                <div class="premium-input-group">
                    <label data-translate="reports.dateFrom">From Date</label>
                    <div class="input-with-icon">
                        <span class="field-icon">📅</span>
                        <input type="date" id="report-date-from" class="premium-input">
                    </div>
                </div>
                <div class="premium-input-group">
                    <label data-translate="reports.dateTo">To Date</label>
                    <div class="input-with-icon">
                        <span class="field-icon">📅</span>
                        <input type="date" id="report-date-to" class="premium-input">
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Summary Cards -->
        <div class="stats-grid-6">
            <div class="report-stat-card">
                <div class="stat-icon transactions">📑</div>
                <div class="stat-content">
                    <span class="stat-label" data-translate="reports.totalTransactions">Total Transactions</span>
                    <h3 id="rep-total-transactions">0</h3>
                </div>
            </div>
            <div class="report-stat-card">
                <div class="stat-icon revenue">💰</div>
                <div class="stat-content">
                    <span class="stat-label" data-translate="reports.totalRevenue">Total Revenue</span>
                    <h3 id="rep-total-revenue">0.00 EGP</h3>
                </div>
            </div>
            <div class="report-stat-card">
                <div class="stat-icon units">📦</div>
                <div class="stat-content">
                    <span class="stat-label" data-translate="reports.soldUnits">Total Sold Units</span>
                    <h3 id="rep-sold-units">0</h3>
                </div>
            </div>
            <div class="report-stat-card">
                <div class="stat-icon cash">💵</div>
                <div class="stat-content">
                    <span class="stat-label" data-translate="reports.cashCollected">Cash Collected</span>
                    <h3 id="rep-cash-collected">0.00 EGP</h3>
                </div>
            </div>
            <div class="report-stat-card">
                <div class="stat-icon returns">🔄</div>
                <div class="stat-content">
                    <span class="stat-label" data-translate="reports.returnsCount">Returns Count</span>
                    <h3 id="rep-returns-count">0</h3>
                </div>
            </div>
            <div class="report-stat-card">
                <div class="stat-icon average">📈</div>
                <div class="stat-content">
                    <span class="stat-label" data-translate="reports.avgInvoice">Avg Invoice Value</span>
                    <h3 id="rep-avg-invoice">0.00 EGP</h3>
                </div>
            </div>
        </div>

        <!-- Section 3: Charts Row -->
        <div class="charts-grid">
            <div class="report-card chart-container-large">
                <h3 class="chart-title">📈 <span data-translate="reports.salesTrend">Sales Trend</span></h3>
                <div class="canvas-holder">
                    <canvas id="salesTrendChart"></canvas>
                </div>
            </div>
            <div class="report-card chart-container-small">
                <h3 class="chart-title">💳 <span data-translate="reports.paymentBreakdown">Payment Breakdown</span></h3>
                <div class="canvas-holder pie">
                    <canvas id="paymentMethodChart"></canvas>
                </div>
                <div id="payment-method-table-container">
                    <table class="mini-table">
                        <thead>
                            <tr>
                                <th data-translate="sales.method">Method</th>
                                <th data-translate="sales.count">Count</th>
                                <th data-translate="sales.amount">Total</th>
                                <th>%</th>
                            </tr>
                        </thead>
                        <tbody id="payment-breakdown-body">
                            <!-- Populated by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Section 4: Operational Tables -->
        <div class="tables-grid">
            <div class="report-card table-section">
                <h3 class="chart-title">🏆 <span data-translate="reports.topProducts">Top Selling Products</span></h3>
                <div class="table-wrapper">
                    <table class="professional-table">
                        <thead>
                            <tr>
                                <th data-translate="inventory.code">Code</th>
                                <th data-translate="inventory.product">Product Name</th>
                                <th class="text-right" data-translate="sales.quantity">Units Sold</th>
                                <th class="text-right" data-translate="reports.revenue">Revenue</th>
                                <th class="text-right">% Share</th>
                            </tr>
                        </thead>
                        <tbody id="top-selling-products-body">
                            <!-- Populated by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="report-card side-section">
                <h3 class="chart-title">↩️ <span data-translate="reports.returnsSummary">Returns Summary</span></h3>
                <div class="returns-summary-content">
                    <div class="returns-item">
                        <span class="returns-label" data-translate="reports.returnedSales">Returned Sales</span>
                        <span class="returns-value" id="rep-ret-sales">0</span>
                    </div>
                    <div class="returns-item">
                        <span class="returns-label" data-translate="reports.returnedUnits">Returned Units</span>
                        <span class="returns-value" id="rep-ret-units">0</span>
                    </div>
                    <div class="returns-item">
                        <span class="returns-label" data-translate="reports.returnedAmount">Returned Amount</span>
                        <span class="returns-value danger" id="rep-ret-amount">0.00 EGP</span>
                    </div>
                </div>
                
                <div style="margin-top: 20px; padding: 15px; background: var(--gray-50); border-radius: 12px; border: 1px dashed var(--gray-200);">
                    <p style="font-size: 11px; color: var(--gray-500); margin: 0; line-height: 1.4;">
                        * This report focus on branch operations. All values are calculated based on the selected period.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Premium Reports Styles */
:root {
    --reports-bg: #f8fafc;
    --card-bg: rgba(255, 255, 255, 0.85);
    --glass-border: rgba(255, 255, 255, 0.4);
    --premium-blue: #3b82f6;
    --premium-blue-glow: rgba(59, 130, 246, 0.3);
    --premium-green: #10b981;
    --premium-purple: #8b5cf6;
    --premium-amber: #f59e0b;
    --premium-rose: #f43f5e;
    --premium-cyan: #06b6d4;
    --radius-premium: 20px;
}

.reports-container {
    padding: 20px 0;
    display: flex;
    flex-direction: column;
    gap: 30px;
    background: var(--reports-bg);
}

.report-card {
    background: white;
    border-radius: var(--radius-premium);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
    padding: 24px;
    border: 1px solid var(--gray-100);
    position: relative;
    overflow: hidden;
}

.glass-effect {
    background: var(--card-bg) !important;
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid var(--glass-border) !important;
}

.premium-title {
    margin: 0;
    font-size: 20px;
    font-weight: 800;
    color: var(--gray-900);
    display: flex;
    align-items: center;
    gap: 12px;
}

.title-accent {
    width: 6px;
    height: 24px;
    background: linear-gradient(to bottom, var(--premium-blue), var(--premium-cyan));
    border-radius: 3px;
}

.filter-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--gray-100);
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 25px;
}

.premium-input-group label {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: var(--gray-600);
    margin-bottom: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.input-with-icon {
    position: relative;
    display: flex;
    align-items: center;
}

.field-icon {
    position: absolute;
    left: 15px;
    font-size: 16px;
    pointer-events: none;
}

.premium-input {
    width: 100%;
    padding: 12px 15px 12px 45px;
    border: 1.5px solid var(--gray-200);
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    color: var(--gray-800);
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    background: white;
}

.premium-input:focus {
    border-color: var(--premium-blue);
    box-shadow: 0 0 0 4px var(--premium-blue-glow);
    outline: none;
    transform: translateY(-1px);
}

.btn-premium {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    border: none;
}

.btn-primary-premium {
    background: linear-gradient(135deg, var(--premium-blue), #2563eb);
    color: white;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-secondary-premium {
    background: var(--gray-100);
    color: var(--gray-700);
}

.btn-success-premium {
    background: linear-gradient(135deg, var(--premium-green), #059669);
    color: white;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-premium:hover {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
}

.btn-premium:active {
    transform: translateY(0) scale(0.98);
}

.stats-grid-6 {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.report-stat-card {
    background: white;
    padding: 24px;
    border-radius: var(--radius-premium);
    display: flex;
    flex-direction: column;
    gap: 15px;
    border: 1px solid var(--gray-50);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    z-index: 1;
}

.report-stat-card::after {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 100px;
    height: 100px;
    background: radial-gradient(circle at top right, var(--card-accent-color), transparent 70%);
    opacity: 0.1;
    z-index: -1;
}

.report-stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 30px -10px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    width: 54px;
    height: 54px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.1);
}

.report-stat-card:nth-child(1) { --card-accent-color: var(--premium-blue); }
.report-stat-card:nth-child(2) { --card-accent-color: var(--premium-green); }
.report-stat-card:nth-child(3) { --card-accent-color: var(--premium-purple); }
.report-stat-card:nth-child(4) { --card-accent-color: var(--premium-amber); }
.report-stat-card:nth-child(5) { --card-accent-color: var(--premium-rose); }
.report-stat-card:nth-child(6) { --card-accent-color: var(--premium-cyan); }

.report-stat-card .stat-icon {
    background: linear-gradient(135deg, white, #f8fafc);
    border: 1px solid var(--gray-100);
}

.report-stat-card:hover .stat-icon {
    background: var(--card-accent-color);
    color: white !important;
    border-color: var(--card-accent-color);
    transform: rotate(-5deg) scale(1.1);
}

.stat-label {
    font-size: 12px;
    color: var(--gray-500);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}

.report-stat-card h3 {
    margin: 0;
    font-size: 22px;
    font-weight: 900;
    color: var(--gray-900);
    letter-spacing: -0.5px;
}

.charts-grid {
    display: grid;
    grid-template-columns: 1.8fr 1.2fr;
    gap: 30px;
}

.chart-title {
    margin: 0 0 25px 0;
    font-size: 16px;
    font-weight: 800;
    color: var(--gray-800);
    display: flex;
    align-items: center;
    gap: 12px;
}

.chart-title i {
    font-style: normal;
    background: var(--gray-100);
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

.canvas-holder {
    position: relative;
    height: 350px;
    width: 100%;
}

.professional-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 8px;
}

.professional-table th {
    background: var(--gray-50);
    padding: 12px 15px;
    font-size: 12px;
    font-weight: 700;
    color: var(--gray-600);
    text-transform: uppercase;
    letter-spacing: 1px;
    border: none;
    text-align: left;
}

.professional-table tr td {
    background: white;
    padding: 15px;
    font-size: 14px;
    color: var(--gray-700);
    border-top: 1px solid var(--gray-100);
    border-bottom: 1px solid var(--gray-100);
}

.professional-table tr td:first-child {
    border-left: 1px solid var(--gray-100);
    border-bottom-left-radius: 12px;
    border-top-left-radius: 12px;
}

.professional-table tr td:last-child {
    border-right: 1px solid var(--gray-100);
    border-bottom-right-radius: 12px;
    border-top-right-radius: 12px;
}

.professional-table tr:hover td {
    background: var(--gray-50);
    border-color: var(--gray-200);
}

.returns-item {
    background: linear-gradient(135deg, #f8fafc, white);
    border: 1px solid var(--gray-100);
    padding: 15px;
    border-radius: 14px;
    margin-bottom: 12px;
    transition: all 0.2s ease;
}

.returns-item:hover {
    border-color: var(--premium-rose);
    box-shadow: 0 4px 12px rgba(244, 63, 94, 0.1);
}

.returns-label {
    font-size: 12px;
    font-weight: 700;
    color: var(--gray-500);
    text-transform: uppercase;
    display: block;
    margin-bottom: 5px;
}

.returns-value {
    font-size: 18px;
    font-weight: 800;
    color: var(--gray-900);
}

.returns-value.danger {
    color: var(--premium-rose);
}

@media (max-width: 1100px) {
    .charts-grid, .tables-grid {
        grid-template-columns: 1fr;
    }
}
</style>
