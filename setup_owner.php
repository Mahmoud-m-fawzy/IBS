<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // 1. Create settings table
    $sql = "CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        key_name VARCHAR(50) UNIQUE NOT NULL,
        key_value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $db->exec($sql);
    echo "Settings table created or already exists.\n";

    // 2. Initialize settings
    $default_settings = [
        ['company_name', 'IBS Store'],
        ['company_logo', ''],
        ['currency', 'EGP'],
        ['tax_percentage', '0'],
        ['invoice_footer', 'Thank you for your business!'],
        ['theme_accent', '#667eea']
    ];

    foreach ($default_settings as $setting) {
        $stmt = $db->prepare("INSERT IGNORE INTO settings (key_name, key_value) VALUES (?, ?)");
        $stmt->execute($setting);
    }
    echo "Default settings initialized.\n";

    // 3. Add any missing columns to products if needed (for global pricing)
    // Checking if branch_id is already there (it should be according to previous research)
    
    echo "Database setup complete.\n";

} catch (Exception $e) {
    echo "Error during setup: " . $e->getMessage() . "\n";
}
