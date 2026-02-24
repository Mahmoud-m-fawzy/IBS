# IBS Mobile Shop Management System

A comprehensive mobile phone shop management system built with PHP, MySQL, and JavaScript featuring real-time inventory management, sales processing, and receipt generation.

![IBS Mobile Shop](assets/css/logo.jpeg)

## Features

### User Management
- **Role-based Access Control**: Admin and Staff roles with different permissions
- **Secure Authentication**: Database-driven login system with plain-text passwords for easy management
- **User Administration**: Add, edit, and manage user accounts

### Inventory Management
- **Product Catalog**: Comprehensive product database with brands, models, and specifications
- **Stock Tracking**: Real-time stock levels with low-stock alerts
- **Category Management**: Organize products by categories (Smartphones, Accessories, Tablets, etc.)
- **Supplier Management**: Track product suppliers and contact information

### Sales Management
- **Receipt Creation**: Interactive receipt builder for staff
- **Real-time Calculations**: Automatic tax calculation (10%) and totals
- **Stock Updates**: Automatic inventory updates after sales
- **Receipt Printing**: Professional receipt generation and printing
- **Sales History**: Complete transaction tracking and reporting

### Customer Management
- **Customer Database**: Store customer information and contact details
- **Purchase History**: Track customer buying patterns and total purchases
- **Customer Tiers**: Bronze, Silver, Gold customer classification

### Dashboard & Analytics
- **Real-time Statistics**: Live business metrics and KPIs
- **Sales Reports**: Comprehensive sales analysis and reporting
- **Inventory Insights**: Stock levels, low-stock alerts, and product performance

## Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript (ES6+), Font Awesome
- **Backend**: PHP 7.4+ with PDO
- **Database**: MySQL 5.7+ with normalized schema
- **Architecture**: MVC (Model-View-Controller) Pattern
- **Server**: Apache/WAMP/XAMPP compatible

## Installation & Setup

### Prerequisites
- WAMP/XAMPP/LAMP server
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web browser (Chrome, Firefox, Safari, Edge)

### Step 1: Clone Repository
```bash
git clone https://github.com/Aliahmed1202/Inventory.git
cd Inventory
```
### Step 2: Database Setup
```sql
# Create database
mysql -u root -p -e "CREATE DATABASE ibs_store;"

# Import full database (structure + seed data)
mysql -u root -p ibs_store < database/ibs_store_complete.sql
```
### Step 3: Configure Database Connection
Edit `config/database.php` with your database credentials:
```php
private $host = "localhost";
private $db_name = "ibs_store";
private $username = "root";
private $password = ""; // Your MySQL password
```
### Step 4: Verify Setup
Visit: `http://localhost/Inventory IBS/`

## Default User Accounts

| Username | Password | Role | Name |
|----------|----------|------|------|
| admin | admin123 | admin | System Administrator |
| staff1 | staff123 | staff | Ahmed Hassan |
| staff2 | staff123 | staff | Sarah Johnson |
| manager1 | admin123 | admin | Mike Wilson |

## Usage Guide

### For Staff Users:
1. **Login** with staff credentials
2. **View Inventory** - Browse available products
3. **Create Receipt** - Add products to receipt, enter customer info
4. **Process Sales** - Complete transactions and print receipts

### For Admin Users:
1. **Full System Access** - All staff features plus:
2. **User Management** - Add/edit users and roles
3. **Sales Reports** - View comprehensive sales analytics
4. **Customer Management** - Access customer database
5. **System Administration** - Manage categories, suppliers

## Project Structure

```
Inventory IBS/
├── api/                    # PHP API endpoints
│   ├── auth.php           # Authentication
│   ├── products.php       # Product management
│   ├── customers.php      # Customer management
│   ├── sales.php          # Sales processing
│   ├── suppliers.php      # Supplier management
│   └── dashboard.php      # Dashboard data
├── assets/
│   ├── css/               # CSS stylesheets
│   │   └── style.css       # Main stylesheet
│   └── js/                # JavaScript files
│       ├── app.js         # Main application logic
│       └── receipt.js     # Receipt management
├── config/
│   └── database.php       # Database configuration
├── database/
│   ├── ibs_store_complete.sql # Complete database (structure + data)
│   └── update_products_structure.sql # Product updates
├── admin_dashboard.php    # Admin interface
├── staff_dashboard.php    # Staff interface
├── index.php              # Login page
└── README.md              # Documentation
```

## 🛠️ Setup Instructions

### 1. Database Setup
1. Create MySQL database `ibs_store`
2. Import the provided dump (tables + data):
   ```sql
   mysql -u root -p ibs_store < database/ibs_store_complete.sql
   ```

### 2. Configuration
1. Update database credentials in `config/database.php`:
   ```php
   private $host = "localhost";
   private $db_name = "ibs_store";
   private $username = "root";
   private $password = "";
   ```

### 3. Testing
1. Access application: `http://localhost/Inventory IBS/`

## 🔐 Demo Credentials

### Admin Access
- **Username**: admin
- **Password**: admin123
- **Role**: admin

### Staff Access
- **Username**: staff1
- **Password**: staff123
- **Role**: staff

## 🏗️ Architecture

### MVC Pattern
- **Model**: Database tables and API endpoints
- **View**: HTML templates and JavaScript UI components
- **Controller**: JavaScript application logic and PHP API controllers

### Database Schema
- **users**: Staff and admin accounts
- **products**: Product inventory
- **customers**: Customer database
- **sales**: Sales transactions
- **sale_items**: Individual sale items
- **stock_movements**: Inventory tracking

## 🔧 Key Features

### Real-time Inventory
- Stock levels update automatically after sales
- Low stock alerts
- Product search and filtering

### Professional Receipts
- Auto-generated receipt numbers
- Tax calculations (10%)
- Printable format
- Customer information tracking

### Role-based Access
- Staff: Limited to inventory viewing and receipt creation
- Admin: Full system access and management

### API Integration
- RESTful API design
- JSON data exchange
- Error handling and validation

## 🚨 Error Handling

The system includes comprehensive error handling:
- Database connection validation
- API response validation
- User input validation
- Stock level verification
- Transaction rollback on errors

## 📊 Sample Data

The system comes with sample data including:
- 20+ mobile products (Apple, Samsung, Google, Huawei)
- 10 sample customers
- Recent sales transactions
- Product categories and suppliers

## 🔄 Future Enhancements

- Barcode scanning
- Advanced reporting
- Email notifications
- Multi-store support
- Mobile app integration
