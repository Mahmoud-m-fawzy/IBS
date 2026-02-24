<?php
// Customers Tab Content
?>
<!-- Customers Management Tab -->
<div id="customers" class="tab-content">
    <div class="section dashboard-section">
        <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2 data-translate="customers.title" style="margin: 0; display: flex; align-items: center; gap: 10px;">
                <span class="section-icon">👥</span> Customer Tab
            </h2>
            <div class="stats-badge" style="background: var(--gradient-primary); color: white; padding: 5px 15px; border-radius: 20px; font-size: 14px; font-weight: 600; box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: 5px;">
                <span id="customerCount">0</span> <span data-translate="navigation.customers">Customers</span>
            </div>
        </div>
        
        <!-- Search and Filter -->
        <div class="filter-container glassy-card" style="margin-bottom: 30px; padding: 25px; background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(10px); border-radius: 16px; border: 1px solid rgba(255,255,255,0.5); box-shadow: var(--shadow-sm);">
            <h3 data-translate="customers.search" style="margin-top: 0; margin-bottom: 20px; font-size: 1.1em; color: var(--gray-700); display: flex; align-items: center; gap: 8px;">
                🔍 Search Customers
            </h3>
            <div class="search-flex-row" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
                <div class="search-input-wrapper" style="flex: 1; min-width: 300px; position: relative;">
                    <input type="text" id="customerSearch" data-translate-placeholder="customers.searchPlaceholder" placeholder="Search by name, phone, or email..."
                        style="width: 100%; padding: 12px 15px 12px 45px; border: 2px solid var(--gray-200); border-radius: 12px; font-size: 16px; transition: var(--transition-normal); background: white;">
                    <span style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--gray-400); font-size: 18px;">🔍</span>
                </div>
                <div class="button-group" style="display: flex; gap: 10px;">
                    <button onclick="searchCustomers()" class="btn btn-primary" style="margin: 0;" data-translate="common.search">Search</button>
                    <button onclick="loadCustomers()" class="btn btn-secondary" style="margin: 0; background: var(--gray-200); color: var(--gray-700);" data-translate="common.reset">Reset</button>
                </div>
            </div>
        </div>

        <!-- Customers Table -->
        <div class="table-card glassy-card" style="background: white; border-radius: 16px; overflow: hidden; box-shadow: var(--shadow-md); border: 1px solid var(--gray-100);">
            <div class="card-header" style="padding: 20px 25px; border-bottom: 1px solid var(--gray-100); display: flex; justify-content: space-between; align-items: center; background: var(--gray-50);">
                <h3 data-translate="customers.list" style="margin: 0; font-size: 1.1em; font-weight: 700;">📋 Customer List</h3>
            </div>
            <div class="table-responsive" style="overflow-x: auto;">
                <table id="customersTable" style="width: 100%; border-collapse: separate; border-spacing: 0;">
                    <thead>
                        <tr style="background: var(--gray-50);">
                            <th style="padding: 15px 25px; text-align: left; border-bottom: 2px solid var(--gray-100); color: var(--gray-600); font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 1px;" data-translate="customers.id">ID</th>
                            <th style="padding: 15px 25px; text-align: left; border-bottom: 2px solid var(--gray-100); color: var(--gray-600); font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 1px;" data-translate="customers.name">Name</th>
                            <th style="padding: 15px 25px; text-align: left; border-bottom: 2px solid var(--gray-100); color: var(--gray-600); font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 1px;" data-translate="customers.phone">Phone</th>
                            <th style="padding: 15px 25px; text-align: left; border-bottom: 2px solid var(--gray-100); color: var(--gray-600); font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 1px;" data-translate="customers.email">Email</th>
                            <th style="padding: 15px 25px; text-align: left; border-bottom: 2px solid var(--gray-100); color: var(--gray-600); font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 1px;" data-translate="customers.totalPurchases">Total Purchases</th>
                            <th style="padding: 15px 25px; text-align: left; border-bottom: 2px solid var(--gray-100); color: var(--gray-600); font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 1px;" data-translate="common.createdAt">Created</th>
                            <th style="padding: 15px 25px; text-align: center; border-bottom: 2px solid var(--gray-100); color: var(--gray-600); font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 1px;" data-translate="common.actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="customersTableBody">
                        <!-- Customers will be loaded here -->
                    </tbody>
                </table>
                <div id="noCustomersFound" style="text-align: center; padding: 60px 40px; color: var(--gray-400); display: none;">
                    <div style="font-size: 48px; margin-bottom: 15px;">🔍</div>
                    <p data-translate="customers.noCustomersFound" style="font-size: 18px; font-weight: 500;">No customers found matching your search</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Customer Modal -->
<div id="editCustomerModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(5px);">
    <div class="modal-content" style="background-color: white; margin: 5% auto; padding: 0; border-radius: 20px; width: 90%; max-width: 550px; box-shadow: var(--shadow-xl); overflow: hidden; animation: modalSlideIn 0.3s ease-out;">
        <div class="modal-header" style="background: var(--gradient-primary); color: white; padding: 25px; display: flex; justify-content: space-between; align-items: center;">
            <h3 data-translate="customers.editCustomer" style="margin: 0; font-size: 1.5em; display: flex; align-items: center; gap: 10px;">
                <span>✏️</span> Edit Customer
            </h3>
            <span class="close" onclick="closeEditCustomerModal()" style="color: white; font-size: 28px; font-weight: bold; cursor: pointer; opacity: 0.8; transition: 0.2s;">&times;</span>
        </div>
        <div style="padding: 30px;">
            <form id="editCustomerForm">
                <input type="hidden" id="editCustomerId" name="id">
                <div class="form-row" style="display: grid; grid-template-columns: 1fr; gap: 20px;">
                    <div class="form-group" style="margin: 0;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--gray-700);"><span data-translate="customers.name">Name</span> <span style="color: var(--primary-red);">*</span></label>
                        <input type="text" name="name" id="editCustomerName" required
                            style="width: 100%; max-width: 100%; padding: 12px 15px; border: 2px solid var(--gray-200); border-radius: 10px; font-size: 16px; transition: var(--transition-normal);">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group" style="margin: 0;">
                            <label data-translate="customers.phone" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--gray-700);">Phone</label>
                            <input type="tel" name="phone" id="editCustomerPhone"
                                style="width: 100%; max-width: 100%; padding: 12px 15px; border: 2px solid var(--gray-200); border-radius: 10px; font-size: 16px; transition: var(--transition-normal);">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label data-translate="customers.email" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--gray-700);">Email</label>
                            <input type="email" name="email" id="editCustomerEmail"
                                style="width: 100%; max-width: 100%; padding: 12px 15px; border: 2px solid var(--gray-200); border-radius: 10px; font-size: 16px; transition: var(--transition-normal);">
                        </div>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label data-translate="customers.address" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--gray-700);">Address</label>
                        <textarea name="address" id="editCustomerAddress" rows="3"
                            style="width: 100%; max-width: 100%; padding: 12px 15px; border: 2px solid var(--gray-200); border-radius: 10px; font-size: 16px; resize: vertical; transition: var(--transition-normal);"></textarea>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label data-translate="customers.totalPurchases" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--gray-700);">Total Purchases</label>
                        <input type="number" name="total_purchases" id="editCustomerTotalPurchases" step="0.01" min="0"
                            style="width: 100%; max-width: 100%; padding: 12px 15px; border: 2px solid var(--gray-200); border-radius: 10px; font-size: 16px; transition: var(--transition-normal);">
                    </div>
                </div>
                <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 35px; padding-top: 20px; border-top: 1px solid var(--gray-100);">
                    <button type="button" onclick="closeEditCustomerModal()" class="btn" style="background: var(--gray-200); color: var(--gray-700); margin: 0; box-shadow: none;" data-translate="common.cancel">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="margin: 0;" data-translate="customers.updateCustomer">Update Customer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
#customers input:focus, #customers textarea:focus {
    outline: none;
    border-color: var(--primary-blue) !important;
    box-shadow: 0 0 0 4px rgba(0, 86, 179, 0.1) !important;
}

#customers tr:hover {
    background-color: var(--gray-50);
}

.action-btn {
    padding: 8px 12px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    font-weight: 600;
    font-size: 13px;
    transition: var(--transition-normal);
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.btn-edit-cust {
    background: var(--light-blue);
    color: var(--dark-blue);
}

.btn-edit-cust:hover {
    background: var(--accent-blue);
    color: white;
    transform: translateY(-2px);
}

.btn-delete-cust {
    background: var(--light-red);
    color: var(--dark-red);
}

.btn-delete-cust:hover {
    background: var(--primary-red);
    color: white;
    transform: translateY(-2px);
}

.glassy-card {
    transition: var(--transition-normal);
}

.glassy-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

@keyframes modalSlideIn {
    from { opacity: 0; transform: translateY(-50px); }
    to { opacity: 1; transform: translateY(0); }
}

.close:hover {
    opacity: 1 !important;
    transform: scale(1.1);
}
</style>

<script>
let allCustomers = [];
let customersLoaded = false;

// Load customers when tab is shown
document.addEventListener('DOMContentLoaded', function() {
    console.log('Customers tab script loaded');
    
    // Add event listener for when customers tab is shown
    const customersTab = document.querySelector('[onclick*="showTab(\'customers\')"]');
    console.log('Customers tab button found:', customersTab);
    
    if (customersTab) {
        customersTab.addEventListener('click', function() {
            console.log('Customers tab clicked');
            setTimeout(() => {
                loadCustomers();
                customersLoaded = true;
            }, 100);
        });
    } else {
        console.log('Customers tab button not found, trying alternative selector');
        // Try alternative selector
        const allTabs = document.querySelectorAll('.nav-tab');
        allTabs.forEach(tab => {
            if (tab.textContent.includes('Customers') || tab.textContent.includes('CUSTOMERS') || tab.getAttribute('data-translate') === 'navigation.customers') {
                console.log('Found customers tab with alternative method:', tab);
                tab.addEventListener('click', function() {
                    console.log('Customers tab clicked (alternative method)');
                    setTimeout(() => {
                        loadCustomers();
                        customersLoaded = true;
                    }, 100);
                });
            }
        });
    }
    
    // Handle add customer form submission
    const addForm = document.getElementById('addCustomerForm');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            addCustomer();
        });
    }
    
    // Handle edit customer form submission
    const editForm = document.getElementById('editCustomerForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            updateCustomer();
        });
    }
    
    // Handle search input
    const searchInput = document.getElementById('customerSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                searchCustomers();
            }
        });
    }
    
    // Also try to load customers immediately if the tab is already visible
    const customersTabContent = document.getElementById('customers');
    if (customersTabContent && customersTabContent.style.display !== 'none') {
        loadCustomers();
        customersLoaded = true;
    }
    
    // Also load customers after a short delay to ensure everything is ready
    setTimeout(() => {
        const customersTabContent = document.getElementById('customers');
        if (customersTabContent && customersTabContent.classList.contains('active')) {
            loadCustomers();
            customersLoaded = true;
        }
    }, 500);
});

function loadCustomers() {
    console.log('Loading customers...');
    fetch('api/customers.php')
        .then(response => response.json())
        .then(data => {
            console.log('Customers API response:', data);
            if (data.success) {
                allCustomers = data.data;
                displayCustomers(allCustomers);
                
                // Update customer count badge
                const countBadge = document.getElementById('customerCount');
                if (countBadge) {
                    countBadge.textContent = allCustomers.length;
                }
            } else {
                console.error('Error loading customers:', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function displayCustomers(customers) {
    const tbody = document.getElementById('customersTableBody');
    const noCustomersDiv = document.getElementById('noCustomersFound');
    
    if (customers.length === 0) {
        tbody.innerHTML = '';
        noCustomersDiv.style.display = 'block';
        return;
    }
    
    noCustomersDiv.style.display = 'none';
    
    tbody.innerHTML = customers.map(customer => {
        const editLabel = typeof langManager !== 'undefined' ? langManager.translate('common', 'edit') : 'Edit';
        const deleteLabel = typeof langManager !== 'undefined' ? langManager.translate('common', 'delete') : 'Delete';
        const phoneLabel = customer.phone || (typeof langManager !== 'undefined' ? langManager.translate('common', 'noRecords') : 'No records');

        return `
        <tr style="border-bottom: 1px solid var(--gray-100); transition: var(--transition-normal);">
            <td style="padding: 15px 25px; font-weight: 500; color: var(--gray-600);">${customer.id}</td>
            <td style="padding: 15px 25px;">
                <div style="font-weight: 700; color: var(--gray-900);">${customer.name}</div>
                <div style="font-size: 12px; color: var(--gray-500);">${phoneLabel}</div>
            </td>
            <td style="padding: 15px 25px; color: var(--gray-700);">${customer.phone || '-'}</td>
            <td style="padding: 15px 25px; color: var(--gray-700);">${customer.email || '-'}</td>
            <td style="padding: 15px 25px;">
                <span style="background: var(--light-green); color: var(--dark-green); padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 14px;">
                    ${formatCurrency(customer.total_purchases || 0)}
                </span>
            </td>
            <td style="padding: 15px 25px; color: var(--gray-500); font-size: 13px;">${new Date(customer.created_at).toLocaleDateString(undefined, {year: 'numeric', month: 'short', day: 'numeric'})}</td>
            <td style="padding: 15px 25px; text-align: center;">
                <div style="display: flex; gap: 8px; justify-content: center;">
                    <button class="action-btn btn-edit-cust" onclick="editCustomer(${customer.id})" data-translate="common.edit">
                        <span>✏️</span> ${editLabel}
                    </button>
                    <button class="action-btn btn-delete-cust" onclick="deleteCustomer(${customer.id})" data-translate="common.delete">
                        <span>🗑️</span> ${deleteLabel}
                    </button>
                </div>
            </td>
        </tr>
    `;}).join('');
    
    // Re-apply translations for data-translate attributes in the newly added rows
    if (typeof langManager !== 'undefined') {
        langManager.applyLanguage(langManager.getCurrentLanguage());
    }
}

function addCustomer() {
    const formData = new FormData(document.getElementById('addCustomerForm'));
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        data[key] = value.trim() || null;
    }
    
    fetch('api/customers.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            if (result.action === 'use_existing') {
                alert(`Using existing customer: ${result.customer_name}\nCustomer ID: ${result.customer_id}`);
                // Here you can trigger receipt creation with existing customer
                // For example: createReceiptWithCustomer(result.customer_id, result.customer_name);
            } else if (result.action === 'new_customer') {
                alert(`New customer added successfully!\nCustomer: ${result.customer_name}\nCustomer ID: ${result.customer_id}`);
                // Here you can trigger receipt creation with new customer
                // For example: createReceiptWithCustomer(result.customer_id, result.customer_name);
            }
            
            document.getElementById('addCustomerForm').reset();
            loadCustomers(); // Refresh the customer list
        } else {
            alert('Error: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding customer. Please try again.');
    });
}

function editCustomer(id) {
    const customer = allCustomers.find(c => c.id === id);
    if (!customer) return;
    
    document.getElementById('editCustomerId').value = customer.id;
    document.getElementById('editCustomerName').value = customer.name;
    document.getElementById('editCustomerPhone').value = customer.phone || '';
    document.getElementById('editCustomerEmail').value = customer.email || '';
    document.getElementById('editCustomerAddress').value = customer.address || '';
    document.getElementById('editCustomerTotalPurchases').value = customer.total_purchases;
    
    document.getElementById('editCustomerModal').style.display = 'block';
}

function closeEditCustomerModal() {
    document.getElementById('editCustomerModal').style.display = 'none';
}

function updateCustomer() {
    const formData = new FormData(document.getElementById('editCustomerForm'));
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        data[key] = value.trim() || null;
    }
    
    fetch(`api/customers.php?id=${data.id}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            const successMsg = typeof langManager !== 'undefined' ? langManager.translate('customers', 'updatedSuccess') : 'Customer updated successfully!';
            alert(successMsg);
            closeEditCustomerModal();
            loadCustomers();
        } else {
            alert('Error: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const errorMsg = typeof langManager !== 'undefined' ? langManager.translate('customers', 'errorUpdating') : 'Error updating customer. Please try again.';
        alert(errorMsg);
    });
}

function deleteCustomer(id) {
    const confirmMsg = typeof langManager !== 'undefined' ? langManager.translate('customers', 'deleteConfirm') : 'Are you sure you want to delete this customer?';
    if (!confirm(confirmMsg)) {
        return;
    }
    
    fetch(`api/customers.php?id=${id}`, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            const successMsg = typeof langManager !== 'undefined' ? langManager.translate('customers', 'deletedSuccess') : 'Customer deleted successfully!';
            alert(successMsg);
            loadCustomers();
        } else {
            alert('Error: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const errorMsg = typeof langManager !== 'undefined' ? langManager.translate('customers', 'errorDeleting') : 'Error deleting customer. Please try again.';
        alert(errorMsg);
    });
}

function searchCustomers() {
    const searchTerm = document.getElementById('customerSearch').value.toLowerCase().trim();
    
    if (!searchTerm) {
        displayCustomers(allCustomers);
        return;
    }
    
    const filteredCustomers = allCustomers.filter(customer => 
        customer.name.toLowerCase().includes(searchTerm) ||
        (customer.phone && customer.phone.toLowerCase().includes(searchTerm)) ||
        (customer.email && customer.email.toLowerCase().includes(searchTerm))
    );
    
    displayCustomers(filteredCustomers);
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('editCustomerModal');
    if (event.target === modal) {
        closeEditCustomerModal();
    }
}
</script>
