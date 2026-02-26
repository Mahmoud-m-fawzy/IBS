<?php
// Financial Tab Content - Professional Enterprise Redesign
?>
<style>
    /* Professional Financial Design Tokens */
    :root {
        --fin-revenue: #10b981; /* Emerald 500 */
        --fin-expense: #ef4444; /* Red 500 */
        --fin-bg: #f8fafc;
        --fin-card-bg: #ffffff;
        --fin-border: #e2e8f0;
        --fin-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    #financial {
        background: var(--fin-bg);
        padding: 24px;
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }

    .fin-container {
        max-width: 1600px;
        margin: 0 auto;
    }

    /* Summary Stats Grid (Inventory Match) */
    .fin-stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 24px;
    }

    .fin-stat-card {
        background: var(--fin-card-bg);
        border-radius: 12px;
        padding: 20px;
        box-shadow: var(--fin-shadow);
        border: 1px solid var(--fin-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: transform 0.2s, box-shadow 0.2s;
        height: 100px;
    }

    .fin-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.06);
    }

    .fin-stat-info h3 {
        font-size: 24px;
        font-weight: 800;
        color: #1e293b;
        margin: 0;
    }

    .fin-stat-info p {
        font-size: 12px;
        color: #64748b;
        margin: 4px 0 0 0;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .fin-stat-icon {
        font-size: 28px;
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
    }

    /* Twin Panels Layout */
    .fin-panels-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
    }

    .fin-panel {
        background: white;
        border-radius: 16px;
        box-shadow: var(--fin-shadow);
        border: 1px solid var(--fin-border);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .fin-panel-header {
        padding: 20px;
        border-bottom: 1px solid var(--fin-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .fin-panel-header.revenue { border-top: 5px solid var(--fin-revenue); }
    .fin-panel-header.expense { border-top: 5px solid var(--fin-expense); }

    .fin-panel-title {
        font-size: 18px;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* Form Styling (Inventory/Sales Match) */
    .fin-form {
        padding: 20px;
        background: #fdfdfd;
        border-bottom: 1px solid var(--fin-border);
    }

    .fin-form-grid {
        display: grid;
        grid-template-columns: 1fr 2fr auto;
        gap: 12px;
        align-items: end;
    }

    .fin-input-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .fin-input-group label {
        font-size: 11px;
        font-weight: 800;
        color: #475569;
        text-transform: uppercase;
    }

    .fin-input {
        padding: 10px 12px;
        border-radius: 8px;
        border: 1.5px solid #e2e8f0;
        font-size: 14px;
        transition: border-color 0.2s;
        outline: none;
    }

    .fin-input:focus { border-color: var(--inv-primary, #3b82f6); }

    .fin-btn-submit {
        padding: 10px 20px;
        border-radius: 8px;
        border: none;
        color: white;
        font-weight: 700;
        font-size: 14px;
        cursor: pointer;
        transition: opacity 0.2s;
    }
    .fin-btn-submit.revenue { background: var(--fin-revenue); }
    .fin-btn-submit.expense { background: var(--fin-expense); }
    .fin-btn-submit:hover { opacity: 0.9; }

    /* Table Styling */
    .fin-table-wrapper {
        padding: 0;
        max-height: 500px;
        overflow-y: auto;
    }

    .fin-table {
        width: 100%;
        border-collapse: collapse;
    }

    .fin-table th {
        position: sticky;
        top: 0;
        background: #f8fafc;
        padding: 12px 15px;
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        border-bottom: 1px solid var(--fin-border);
        text-align: left;
        z-index: 10;
    }

    .fin-table td {
        padding: 12px 15px;
        font-size: 13.5px;
        color: #1e293b;
        border-bottom: 1px solid #f1f5f9;
    }

    /* Action Buttons (Mini) */
    .fin-actions {
        display: flex;
        gap: 6px;
    }

    .fin-action-btn {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        border: 1px solid var(--fin-border);
        background: white;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 14px;
    }

    .fin-action-btn:hover { background: #f8fafc; transform: translateY(-1px); }
    .fin-action-btn.print:hover { color: #3b82f6; border-color: #3b82f6; }
    .fin-action-btn.delete:hover { color: #ef4444; border-color: #ef4444; }

    /* RTL */
    body.rtl .fin-panel-header { border-left: none; }
    body.rtl .fin-table th, body.rtl .fin-table td { text-align: right; }
</style>

<div id="financial" class="tab-content" style="display: none;">
    <div class="fin-container">
        <!-- Summary Cards -->
        <div class="fin-stats-grid">
            <div class="fin-stat-card">
                <div class="fin-stat-info">
                    <h3 id="stat-total-revenue">0</h3>
                    <p data-translate="income.totalIncome">Total Revenue</p>
                </div>
                <div class="fin-stat-icon" style="background: #ecfdf5; color: var(--fin-revenue);">💰</div>
            </div>
            <div class="fin-stat-card">
                <div class="fin-stat-info">
                    <h3 id="stat-total-expenses">0</h3>
                    <p data-translate="payment.totalPayments">Total Expenses</p>
                </div>
                <div class="fin-stat-icon" style="background: #fef2f2; color: var(--fin-expense);">💸</div>
            </div>
            <div class="fin-stat-card">
                <div class="fin-stat-info">
                    <h3 id="stat-net-balance">0</h3>
                    <p data-translate="reports.netProfit">Net Balance</p>
                </div>
                <div class="fin-stat-icon" style="background: #eff6ff; color: #3b82f6;">⚖️</div>
            </div>
            <div class="fin-stat-card">
                <div class="fin-stat-info">
                    <h3 id="stat-fin-entries">0</h3>
                    <p>Total Transactions</p>
                </div>
                <div class="fin-stat-icon" style="background: #f5f3ff; color: #8b5cf6;">📑</div>
            </div>
        </div>

        <div class="fin-panels-grid">
            <!-- Expense Panel (الهالك او المصروفات) -->
            <div class="fin-panel">
                <div class="fin-panel-header expense">
                    <h3 class="fin-panel-title" data-translate="payment.title">
                        <span>🔴</span> المصروفات التشغيلية
                    </h3>
                </div>

                <form id="addPaymentForm" class="fin-form">
                    <div class="fin-form-grid">
                        <div class="fin-input-group">
                            <label data-translate="payment.amount">Amount</label>
                            <input type="number" id="payment-price" step="0.01" min="0.01" required 
                                   class="fin-input" placeholder="0.00">
                        </div>
                        <div class="fin-input-group">
                            <label data-translate="payment.description">Description</label>
                            <input type="text" id="payment-description" required 
                                   class="fin-input" placeholder="Enter expense details...">
                        </div>
                        <button type="submit" class="fin-btn-submit expense">⚡ Add</button>
                    </div>
                </form>

                <div class="fin-table-wrapper">
                    <table class="fin-table" id="payment-table">
                        <thead>
                            <tr>
                                <th data-translate="payment.date">Date</th>
                                <th data-translate="staff.name">Created By</th>
                                <th data-translate="payment.description">Description</th>
                                <th style="text-align: right;" data-translate="payment.amount">Amount</th>
                                <th style="text-align: center;" data-translate="payment.actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="payment-tbody"></tbody>
                    </table>
                </div>
            </div>

            <!-- Revenue Panel (المدخلات واي ايراد داخل) -->
            <div class="fin-panel">
                <div class="fin-panel-header revenue">
                    <h3 class="fin-panel-title" data-translate="income.title">
                        <span>🟢</span> الإيرادات التشغيلية
                    </h3>
                </div>

                <form id="addIncomeForm" class="fin-form">
                    <div class="fin-form-grid">
                        <div class="fin-input-group">
                            <label data-translate="income.price">Amount</label>
                            <input type="number" id="income-price" step="0.01" min="0.01" required 
                                   class="fin-input" placeholder="0.00">
                        </div>
                        <div class="fin-input-group">
                            <label data-translate="income.description">Description</label>
                            <input type="text" id="income-description" required 
                                   class="fin-input" placeholder="Enter revenue details...">
                        </div>
                        <button type="submit" class="fin-btn-submit revenue">⚡ Add</button>
                    </div>
                </form>

                <div class="fin-table-wrapper">
                    <table class="fin-table" id="income-table">
                        <thead>
                            <tr>
                                <th data-translate="income.date">Date</th>
                                <th data-translate="staff.name">Created By</th>
                                <th data-translate="income.description">Description</th>
                                <th style="text-align: right;" data-translate="income.amount">Amount</th>
                                <th style="text-align: center;" data-translate="income.actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="income-tbody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
