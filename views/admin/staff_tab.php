<?php
// Staff Management Tab Content - Refined & Fixed
?>

<!-- Staff Management Tab -->
<div id="staff" class="tab-content" style="background: #f8fafc; padding: 25px; border-radius: 12px;">

    <!-- Section Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h2 style="font-size: 26px; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 12px;">
            <span style="background: #e2e8f0; padding: 10px; border-radius: 10px;">👥</span>
            <span data-translate="staff.title">Staff Management</span>
        </h2>
        <div style="display: flex; gap: 12px;">
            <button onclick="toggleSmsPanel()" class="cust-btn-primary" style="background: #2563eb; border: none; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);">
                📱 <span data-translate="staff.sendMessage">WhatsApp Broadcast</span>
            </button>
            <button onclick="toggleAddStaffPanel()" class="cust-btn-primary" id="addStaffToggleBtn" style="background: #0ea5e9; border: none; box-shadow: 0 4px 6px -1px rgba(14, 165, 233, 0.2);">
                ➕ <span data-translate="staff.addStaff">Add Staff</span>
            </button>
        </div>
    </div>

    <!-- Stat Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div class="prof-card color-indigo">
            <div class="prof-card-info">
                <span class="prof-card-title" data-translate="staff.totalStaff">Total Staff</span>
                <span id="staff-stat-total" class="prof-card-value">0</span>
            </div>
            <div class="prof-card-icon">👥</div>
        </div>
        <div class="prof-card color-emerald">
            <div class="prof-card-info">
                <span class="prof-card-title" data-translate="staff.activeStaff">Active Staff</span>
                <span id="staff-stat-active" class="prof-card-value">0</span>
            </div>
            <div class="prof-card-icon">✅</div>
        </div>
        <div class="prof-card color-rose">
            <div class="prof-card-info">
                <span class="prof-card-title" data-translate="staff.inactiveStaff">Inactive Staff</span>
                <span id="staff-stat-inactive" class="prof-card-value">0</span>
            </div>
            <div class="prof-card-icon">🚫</div>
        </div>
    </div>

    <!-- WhatsApp Messaging Panel (collapsible) -->
    <div id="smsPanel" class="prof-panel" style="margin-bottom: 30px; display: none; border: 2px solid #2563eb; background: #fff; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px;">
            <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                🟢 <span data-translate="staff.sendMessage">Broadcast via WhatsApp</span>
            </h3>
            <button onclick="toggleSmsPanel()" class="prof-text-btn" style="color: #64748b; font-weight: 600;">✕ Close</button>
        </div>
        <div class="prof-input-group">
            <label style="margin-bottom: 8px; display: block; font-weight: 600; color: #475569;" data-translate="staff.writeMessage">Message Content</label>
            <textarea id="smsMessageContent" rows="3" style="width: 100%; padding: 15px; border-radius: 12px; border: 1.5px solid #cbd5e1; font-size: 14px; outline: none; transition: border-color 0.2s; position: relative; z-index: 10;" data-translate-placeholder="staff.writeMessage" placeholder="Type your WhatsApp message here..."></textarea>
        </div>
        <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
            <button onclick="sendBroadcastSms()" class="cust-btn-primary" style="background: #2563eb; width: auto; padding: 12px 30px;">
                <span data-translate="staff.sendSms">Send Messages</span> ⚡
            </button>
        </div>
    </div>

    <!-- Add Staff Panel (collapsible - Professional Design) -->
    <div id="addStaffPanel" class="prof-panel" style="margin-bottom: 30px; display: none; background: #fff; border: 1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); border-radius: 16px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px; border-bottom: 1.5px solid #f1f5f9; padding-bottom: 15px;">
            <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 10px;">
                👤 <span data-translate="staff.addStaff">Register New Staff</span>
            </h3>
            <button onclick="toggleAddStaffPanel()" class="prof-text-btn" style="color: #64748b; font-weight: 600;">✕ Dismiss</button>
        </div>
        <form method="POST">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">
                <div class="prof-input-group">
                    <label style="font-weight: 700; color: #334155; display: flex; align-items: center; gap: 5px;">
                        🔑 <span data-translate="staff.username">Username</span>
                    </label>
                    <input type="text" name="username" required style="width: 100%; padding: 12px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; background: #f8fafc;">
                </div>
                <div class="prof-input-group">
                    <label style="font-weight: 700; color: #334155; display: flex; align-items: center; gap: 5px;">
                        🛡️ <span data-translate="staff.password">Password</span>
                    </label>
                    <input type="password" name="password" required style="width: 100%; padding: 12px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; background: #f8fafc;">
                </div>
                <div class="prof-input-group">
                    <label style="font-weight: 700; color: #334155; display: flex; align-items: center; gap: 5px;">
                        🏷️ <span data-translate="staff.role">Access Role</span>
                    </label>
                    <select name="role" required style="width: 100%; padding: 12px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; background: #f8fafc; cursor: pointer;">
                        <option value="staff">Standard Staff</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <div class="prof-input-group">
                    <label style="font-weight: 700; color: #334155; display: flex; align-items: center; gap: 5px;">
                        📝 <span data-translate="staff.name">Full Display Name</span>
                    </label>
                    <input type="text" name="name" required style="width: 100%; padding: 12px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; background: #f8fafc;">
                </div>
                <div class="prof-input-group">
                    <label style="font-weight: 700; color: #334155; display: flex; align-items: center; gap: 5px;">
                        📞 <span data-translate="staff.phone">Phone Number</span>
                    </label>
                    <input type="text" name="phone" placeholder="+201..." style="width: 100%; padding: 12px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; background: #f8fafc;">
                </div>
                <div class="prof-input-group">
                    <label style="font-weight: 700; color: #334155; display: flex; align-items: center; gap: 5px;">
                        📧 <span data-translate="staff.email">Email Address</span>
                    </label>
                    <input type="email" name="email" style="width: 100%; padding: 12px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; background: #f8fafc;">
                </div>
            </div>
            <div style="margin-top: 30px; display: flex; justify-content: flex-end; gap: 15px; padding-top: 20px; border-top: 1.5px solid #f1f5f9;">
                <button type="button" onclick="toggleAddStaffPanel()" style="background: #fff; color: #64748b; padding: 12px 28px; border-radius: 10px; font-weight: 700; font-size: 14px; border: 1.5px solid #e2e8f0; cursor: pointer; transition: all 0.2s;" data-translate="common.cancel">Discard</button>
                <button type="submit" name="add_user" class="cust-btn-primary" style="background: #0ea5e9; border: none; padding: 12px 35px; border-radius: 10px; font-weight: 800;">
                    ✅ <span data-translate="staff.addStaff">Confirm Registration</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Search Panel -->
    <div class="prof-panel" style="margin-bottom: 30px; background: #fff; border-radius: 16px; border: 1px solid #e2e8f0;">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
            <span style="font-size: 20px;">🔍</span>
            <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: #1e293b;" data-translate="staff.searchStaff">Find Team Member</h3>
        </div>
        <div class="prof-input-group">
            <input type="text" id="staff-search" data-translate-placeholder="staff.search" placeholder="Search by name, role, phone..." 
                   style="width: 100%; padding: 14px 20px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 15px; transition: all 0.3s; position: relative; z-index: 10;"
                   onkeyup="if(typeof filterStaff === 'function') filterStaff(this.value)">
        </div>
        <div id="staff-search-results-count" style="margin-top: 10px; font-size: 13px; font-weight: 600; color: #64748b;"></div>
    </div>

    <!-- Staff Table -->
    <div class="prof-panel" style="padding: 0; overflow: hidden; border-radius: 16px; border: 1px solid #e2e8f0; background: #fff; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);">
        <div style="padding: 20px 25px; background: #fdfdfd; border-bottom: 1.5px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 14px; color: #475569; font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase;">
                🛡️ <span data-translate="staff.title">Staff Registry</span>
            </span>
            <button onclick="if(typeof loadStaff === 'function') loadStaff()" class="prof-icon-btn" style="background: #f1f5f9; border-radius: 8px; padding: 8px;" title="Refresh Data">🔄</button>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: separate; border-spacing: 0;">
                <thead>
                    <tr style="background: #f8fafc;">
                        <th style="padding: 18px 25px; text-align: left; font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;" data-translate="staff.username">Identifier</th>
                        <th style="padding: 18px 25px; text-align: left; font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;" data-translate="staff.name">Full Name</th>
                        <th style="padding: 18px 25px; text-align: left; font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;" data-translate="staff.role">Hierarchy</th>
                        <th style="padding: 18px 25px; text-align: left; font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;" data-translate="staff.phone">Contact</th>
                        <th style="padding: 18px 25px; text-align: center; font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;" data-translate="staff.status">Condition</th>
                        <th style="padding: 18px 25px; text-align: center; font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;" data-translate="staff.actions">Modify</th>
                    </tr>
                </thead>
                <tbody id="staff-tbody" style="color: #334155; font-size: 14px;">
                    <!-- Populated by displayStaff() in admin_dashboard.php -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Staff Tab Interaction Logic
function toggleAddStaffPanel() {
    const panel = document.getElementById('addStaffPanel');
    const isHidden = panel.style.display === 'none';
    panel.style.display = isHidden ? 'block' : 'none';
    if(isHidden) document.getElementById('smsPanel').style.display = 'none';
}

function toggleSmsPanel() {
    const panel = document.getElementById('smsPanel');
    const isHidden = panel.style.display === 'none';
    panel.style.display = isHidden ? 'block' : 'none';
    if(isHidden) document.getElementById('addStaffPanel').style.display = 'none';
}

// Stats Calculation Hook
function updateStaffTabStats() {
    if(typeof allStaffMembers !== 'undefined' && allStaffMembers.length > 0) {
        const total = allStaffMembers.length;
        const active = allStaffMembers.filter(s => parseInt(s.is_active) === 1).length;
        const inactive = total - active;

        const statTotal = document.getElementById('staff-stat-total');
        const statActive = document.getElementById('staff-stat-active');
        const statInactive = document.getElementById('staff-stat-inactive');

        if(statTotal) statTotal.textContent = total;
        if(statActive) statActive.textContent = active;
        if(statInactive) statInactive.textContent = inactive;
    }
}

// WhatsApp Sending Wrapper
function sendBroadcastSms() {
    const content = document.getElementById('smsMessageContent').value.trim();
    if(!content) {
        alert('Please enter a WhatsApp message body');
        return;
    }

    if(!confirm('Broadcast this message to all active staff members via WhatsApp?')) return;

    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '⏳ Processing...';

    fetch('api/send_staff_sms.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: content })
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            alert(translations.staff[langManager.getCurrentLanguage()].smsSent);
            document.getElementById('smsMessageContent').value = '';
            toggleSmsPanel();
        } else {
            alert('Failed: ' + (data.message || 'Unknown server error'));
        }
    })
    .catch(() => alert('Critical connection error during WhatsApp broadcast'))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

// Hook into dashboard loading
document.addEventListener('DOMContentLoaded', () => {
    // Initial stats check
    setTimeout(updateStaffTabStats, 1500);
    
    // Refresh stats when data is loaded
    const staffTabBtn = document.querySelector('[data-translate="navigation.staff"]');
    if(staffTabBtn) {
        staffTabBtn.addEventListener('click', () => {
            setTimeout(() => {
                if(typeof loadStaff === 'function') loadStaff();
                updateStaffTabStats();
            }, 500);
        });
    }
});
</script>
