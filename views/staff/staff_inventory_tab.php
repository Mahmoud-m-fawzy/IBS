<?php
// Staff Inventory Tab Content
?>
<!-- Inventory Tab -->
<div id="inventory" class="tab-content">
    <div class="section">
        <h2>📦 Inventory View</h2>
        <div id="inventory-stats" class="stats-grid"></div>

        <!-- Search Bar -->
        <div style="margin-bottom: 20px; display: flex; align-items: center; gap: 15px;">
            <div style="flex: 1; max-width: 400px;">
                <input type="text" id="inventorySearchInput" placeholder="🔍 Search by Code, Brand, Model..."
                    style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px;"
                    onkeyup="filterInventory(this.value)"
                    onkeypress="if(event.key==='Enter') filterInventory(this.value)">
            </div>
            <button onclick="clearInventorySearch()" class="btn btn-secondary"
                style="padding: 12px 20px;">Clear</button>
        </div>

        <!-- Search Results Count -->
        <div id="inventory-search-results-count" style="margin-bottom: 15px; color: #666; font-size: 14px;">
            Showing all products
        </div>

        <table id="inventory-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Product</th>
                    <th>Min Price</th>
                    <th>Suggested Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="inventory-tbody">
                <tr>
                    <td colspan="6" class="no-data">Loading inventory...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
