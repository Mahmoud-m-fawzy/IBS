<?php
// Staff Management Tab Content
?>
<!-- Staff Management Tab -->
<div id="staff" class="tab-content">
    <div class="section">
        <h2 data-translate="staff.title">👥 Staff Management</h2>
        <h3 data-translate="staff.addStaff">Add New Staff</h3>
        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <div class="form-group">
                        <label data-translate="staff.username">Username:</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label data-translate="staff.password">Password:</label>
                        <input type="text" name="password" required>
                    </div>
                    <div class="form-group">
                        <label data-translate="staff.role">Role:</label>
                        <select name="role" required>
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div>
                    <div class="form-group">
                        <label data-translate="staff.name">Full Name:</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group">
                        <label data-translate="staff.phone">Phone:</label>
                        <input type="text" name="phone">
                    </div>
                    <div class="form-group">
                        <label data-translate="staff.email">Email:</label>
                        <input type="email" name="email">
                    </div>
                </div>
            </div>
            <button type="submit" name="add_user" class="btn" data-translate="staff.addStaff">Add Staff</button>
        </form>

        <h3 style="margin-top: 30px;" data-translate="staff.currentStaff">Current Staff</h3>

        <!-- Staff Search Bar -->
        <div class="form-group" style="margin: 20px 0;">
            <label data-translate="staff.searchStaff">🔍 Search Staff:</label>
            <input type="text" id="staff-search" placeholder="Search by username, name, role, or phone..."
                style="width: 100%; max-width: 500px; font-size: 16px; padding: 12px;">
            <div id="staff-search-results-count" style="margin-top: 5px; color: #666; font-size: 14px;"></div>
        </div>

        <table id="staff-table">
            <thead>
                <tr>
                    <th data-translate="staff.username">Username</th>
                    <th data-translate="staff.name">Name</th>
                    <th data-translate="staff.role">Role</th>
                    <th data-translate="staff.phone">Phone</th>
                    <th data-translate="staff.status">Status</th>
                    <th data-translate="staff.actions">Actions</th>
                </tr>
            </thead>
            <tbody id="staff-tbody"></tbody>
        </table>
    </div>
</div>
