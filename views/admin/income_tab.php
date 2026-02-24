<?php
// Income Tab Content
?>
<!-- Income Tab -->
<div id="income" class="tab-content">
    <div class="section">
        <h2 data-translate="income.title">💰 Income Management</h2>

        <!-- Add Income Entry Form -->
        <div style="margin-bottom: 30px;">
            <h3 data-translate="income.addNewIncome">Add New Income Entry</h3>
            <form id="addIncomeForm">
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
                    <div class="form-group">
                        <label data-translate="income.price">Price: <span style="color: red;">*</span></label>
                        <input type="number" id="income-price" step="0.01" min="0.01" required
                            placeholder="Enter income amount"
                            style="width: 100%; max-width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                        <small style="color: #666; font-size: 12px;" data-translate="income.amountGreaterThanZero">Amount must be greater than 0</small>
                    </div>
                    <div class="form-group">
                        <label data-translate="income.description">Description: <span style="color: red;">*</span></label>
                        <textarea id="income-description" rows="2" required
                            placeholder="Describe the source of income..."
                            style="width: 100%; max-width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px; resize: vertical;"></textarea>
                        <small style="color: #666; font-size: 12px;" data-translate="income.provideIncomeDetails">Provide details about this income entry</small>
                    </div>
                </div>
                <button type="submit" class="btn" style="margin-top: 10px;" data-translate="income.addIncomeEntry">Add Income Entry</button>
            </form>
        </div>

        <!-- Income Entries List -->
        <div>
            <h3 data-translate="income.incomeEntries">Income Entries</h3>
            <div id="income-stats" class="stats-grid" style="margin-bottom: 20px;"></div>
            <table id="income-table">
                <thead>
                    <tr>
                        <th data-translate="income.date">Date</th>
                        <th data-translate="income.name">Name</th>
                        <th data-translate="income.description">Description</th>
                        <th data-translate="income.amount">Amount</th>
                        <th data-translate="income.actions">Actions</th>
                    </tr>
                </thead>
                <tbody id="income-tbody"></tbody>
            </table>
        </div>
    </div>
</div>
