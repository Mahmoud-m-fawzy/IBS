<?php
/**
 * Database Configuration for IBS - Inventory Management System
 * Centralized database settings and connection management
 */

// Prevent direct access
if (!defined('IBS_ACCESS')) {
    die('Direct access to this file is not allowed.');
}

/**
 * Database Configuration
 */
class DatabaseConfig {
    // Database settings
    const DB_HOST = 'localhost';
    const DB_NAME = 'ibs_inventory';
    const DB_USER = 'root';
    const DB_PASSWORD = '';
    const DB_CHARSET = 'utf8mb4';
    
    // Connection settings
    const DB_OPTIONS = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ];
    
    // Table prefixes
    const TABLE_PREFIX = '';
    
    // Timezone
    const TIMEZONE = 'Africa/Cairo';
    
    // Pagination
    const DEFAULT_PAGE_SIZE = 20;
    const MAX_PAGE_SIZE = 100;
    
    // File upload settings
    const MAX_FILE_SIZE = 5242880; // 5MB
    const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
    
    // Session settings
    const SESSION_LIFETIME = 86400; // 24 hours in seconds
    
    // Security settings
    const PASSWORD_MIN_LENGTH = 8;
    const PASSWORD_HASH_ALGO = PASSWORD_DEFAULT;
    const SESSION_SECURE = true;
    const SESSION_HTTPONLY = true;
    const SESSION_SAMESITE = 'Lax';
    
    // Debug settings
    const DEBUG_MODE = true;
    const LOG_ERRORS = true;
    const ERROR_LOG_PATH = __DIR__ . '/../logs/error.log';
    
    // Cache settings
    const CACHE_ENABLED = false;
    const CACHE_LIFETIME = 3600; // 1 hour
    
    // API settings
    const API_VERSION = 'v1';
    const API_RATE_LIMIT = 100; // requests per minute
    
    /**
     * Get database connection string
     */
    public static function getDSN() {
        return sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            self::DB_HOST,
            self::DB_NAME,
            self::DB_CHARSET
        );
    }
    
    /**
     * Get PDO options
     */
    public static function getOptions() {
        return self::DB_OPTIONS;
    }
    
    /**
     * Get table name with prefix
     */
    public static function getTableName($table) {
        return self::TABLE_PREFIX . $table;
    }
    
    /**
     * Validate database configuration
     */
    public static function validate() {
        $errors = [];
        
        // Check required constants
        if (empty(self::DB_HOST)) {
            $errors[] = 'Database host is required';
        }
        
        if (empty(self::DB_NAME)) {
            $errors[] = 'Database name is required';
        }
        
        if (empty(self::DB_USER)) {
            $errors[] = 'Database user is required';
        }
        
        // Validate database name
        if (!preg_match('/^[a-zA-Z0-9_]+$/', self::DB_NAME)) {
            $errors[] = 'Database name contains invalid characters';
        }
        
        return empty($errors);
    }
    
    /**
     * Get database configuration as array
     */
    public static function getConfig() {
        return [
            'host' => self::DB_HOST,
            'name' => self::DB_NAME,
            'user' => self::DB_USER,
            'password' => self::DB_PASSWORD,
            'charset' => self::DB_CHARSET,
            'options' => self::DB_OPTIONS,
            'prefix' => self::TABLE_PREFIX,
            'timezone' => self::TIMEZONE
        ];
    }
}

/**
 * Database Connection Class
 */
class Database {
    private $connection = null;
    private $config = null;
    
    public function __construct() {
        $this->config = DatabaseConfig::getConfig();
        
        // Validate configuration
        $errors = DatabaseConfig::validate();
        if (!empty($errors)) {
            throw new Exception('Database configuration error: ' . implode(', ', $errors));
        }
        
        // Set timezone
        date_default_timezone_set(DatabaseConfig::TIMEZONE);
        
        // Enable error logging
        if (DatabaseConfig::LOG_ERRORS) {
            ini_set('log_errors', 1);
            ini_set('error_log', DatabaseConfig::ERROR_LOG_PATH);
        }
        
        // Set session settings
        ini_set('session.cookie_lifetime', DatabaseConfig::SESSION_LIFETIME);
        ini_set('session.cookie_secure', DatabaseConfig::SESSION_SECURE);
        ini_set('session.cookie_httponly', DatabaseConfig::SESSION_HTTPONLY);
        ini_set('session.cookie_samesite', DatabaseConfig::SESSION_SAMESITE);
        
        // Set display errors based on debug mode
        if (DatabaseConfig::DEBUG_MODE) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        } else {
            error_reporting(0);
            ini_set('display_errors', 0);
        }
    }
    
    /**
     * Get database connection
     */
    public function getConnection() {
        try {
            if ($this->connection === null) {
                $dsn = DatabaseConfig::getDSN();
                $options = DatabaseConfig::getOptions();
                
                $this->connection = new PDO($dsn, $this->config['user'], $this->config['password'], $options);
                
                // Set error mode to exception
                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Enable emulated prepares for older MySQL versions
                $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
                
                // Set default fetch mode
                $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                
                // Set charset
                $this->connection->exec("SET NAMES " . DatabaseConfig::DB_CHARSET);
                
                // Enable strict mode
                $this->connection->exec("SET sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
                
                // Set timezone
                $this->connection->exec("SET time_zone = '" . DatabaseConfig::TIMEZONE . "'");
                
                // Enable foreign key checks
                $this->connection->exec("SET FOREIGN_KEY_CHECKS = 1");
                
                // Enable query cache for better performance
                $this->connection->exec("SET query_cache_type = 1");
                
                // Set autocommit to false for transaction management
                $this->connection->setAttribute(PDO::ATTR_AUTOCOMMIT, false);
                
                if (DatabaseConfig::DEBUG_MODE) {
                    error_log('Database connection established successfully');
                }
            }
            
            return $this->connection;
            
        } catch (PDOException $e) {
            $error_message = 'Database connection failed: ' . $e->getMessage();
            
            if (DatabaseConfig::LOG_ERRORS) {
                error_log($error_message);
            }
            
            if (DatabaseConfig::DEBUG_MODE) {
                throw new Exception($error_message);
            } else {
                throw new Exception('Database connection failed. Please check your configuration.');
            }
        }
    }
    
    /**
     * Close database connection
     */
    public function closeConnection() {
        if ($this->connection !== null) {
            $this->connection = null;
            
            if (DatabaseConfig::DEBUG_MODE) {
                error_log('Database connection closed');
            }
        }
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->connection->rollBack();
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    /**
     * Get affected rows
     */
    public function affectedRows() {
        return $this->connection->rowCount();
    }
    
    /**
     * Execute query
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $error_message = 'Query execution failed: ' . $e->getMessage();
            
            if (DatabaseConfig::LOG_ERRORS) {
                error_log($error_message);
            }
            
            if (DatabaseConfig::DEBUG_MODE) {
                throw new Exception($error_message);
            } else {
                throw new Exception('Query execution failed');
            }
        }
    }
    
    /**
     * Execute multiple queries in transaction
     */
    public function executeTransaction($queries) {
        try {
            $this->beginTransaction();
            
            $results = [];
            foreach ($queries as $query) {
                $stmt = $this->query($query['sql'], $query['params'] ?? []);
                $results[] = $stmt;
            }
            
            $this->commit();
            return $results;
            
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * Check if table exists
     */
    public function tableExists($table) {
        try {
            $stmt = $this->query("SHOW TABLES LIKE ?", ['%' . $table . '%']);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get table structure
     */
    public function getTableStructure($table) {
        try {
            $stmt = $this->query("DESCRIBE " . DatabaseConfig::getTableName($table));
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get database version
     */
    public function getVersion() {
        try {
            $stmt = $this->query("SELECT VERSION() as version");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['version'];
        } catch (Exception $e) {
            return 'Unknown';
        }
    }
    
    /**
     * Get database charset
     */
    public function getCharset() {
        try {
            $stmt = $this->query("SELECT DEFAULT_CHARACTER_SET_NAME() as charset");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['charset'];
        } catch (Exception $e) {
            return 'Unknown';
        }
    }
    
    /**
     * Get database collation
     */
    public function getCollation() {
        try {
            $stmt = $this->query("SELECT DEFAULT_COLLATION_NAME() as collation");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['collation'];
        } catch (Exception $e) {
            return 'Unknown';
        }
    }
    
    /**
     * Get database statistics
     */
    public function getStats() {
        try {
            $stats = [];
            
            // Get table count
            $stmt = $this->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = ?", [DatabaseConfig::DB_NAME]);
            $stats['tables'] = $stmt->fetch(PDO::FETCH_ASSOC)['table_count'];
            
            // Get total records
            $stmt = $this->query("SELECT SUM(table_rows) as total_records FROM information_schema.tables WHERE table_schema = ?", [DatabaseConfig::DB_NAME]);
            $stats['total_records'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_records'];
            
            // Get database size
            $stmt = $this->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb FROM information_schema.tables WHERE table_schema = ?", [DatabaseConfig::DB_NAME]);
            $stats['size_mb'] = $stmt->fetch(PDO::FETCH_ASSOC)['size_mb'];
            
            return $stats;
            
        } catch (Exception $e) {
            return [
                'tables' => 0,
                'total_records' => 0,
                'size_mb' => 0
            ];
        }
    }
    
    /**
     * Backup database
     */
    public function backup($backupFile = null) {
        if ($backupFile === null) {
            $backupFile = DatabaseConfig::DB_NAME . '_backup_' . date('Y-m-d_H-i-s') . '.sql';
        }
        
        try {
            // Get all tables
            $stmt = $this->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $backup = "-- Database Backup\n";
            $backup .= "-- Database: " . DatabaseConfig::DB_NAME . "\n";
            $backup .= "-- Date: " . date('Y-m-d H:i:s') . "\n";
            $backup .= "-- Generated by IBS Inventory Management System\n\n";
            
            foreach ($tables as $table) {
                $backup .= "\n-- Table: $table\n";
                $stmt = $this->query("SHOW CREATE TABLE `$table`");
                $backup .= $stmt->fetch(PDO::FETCH_ASSOC)['Create Table'] . ";\n\n";
                
                $stmt = $this->query("SELECT * FROM `$table`");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $values = array_map(function($value) {
                        return is_null($value) ? 'NULL' : "'" . addslashes($value) . "'";
                    }, $row);
                    
                    $backup .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
                }
                
                $backup .= "\n";
            }
            
            // Write to file
            file_put_contents($backupFile, $backup);
            
            if (DatabaseConfig::LOG_ERRORS) {
                error_log('Database backup created: ' . $backupFile);
            }
            
            return $backupFile;
            
        } catch (Exception $e) {
            $error_message = 'Database backup failed: ' . $e->getMessage();
            
            if (DatabaseConfig::LOG_ERRORS) {
                error_log($error_message);
            }
            
            if (DatabaseConfig::DEBUG_MODE) {
                throw new Exception($error_message);
            } else {
                throw new Exception('Database backup failed');
            }
        }
    }
    
    /**
     * Optimize database
     */
    public function optimize() {
        try {
            $stmt = $this->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $optimized = 0;
            foreach ($tables as $table) {
                $this->query("OPTIMIZE TABLE `$table`");
                $optimized++;
            }
            
            if (DatabaseConfig::LOG_ERRORS) {
                error_log("Database optimized: $optimized tables");
            }
            
            return $optimized;
            
        } catch (Exception $e) {
            $error_message = 'Database optimization failed: ' . $e->getMessage();
            
            if (DatabaseConfig::LOG_ERRORS) {
                error_log($error_message);
            }
            
            if (DatabaseConfig::DEBUG_MODE) {
                throw new Exception($error_message);
            } else {
                throw new Exception('Database optimization failed');
            }
        }
    }
    
    /**
     * Destructor - close connection
     */
    public function __destruct() {
        $this->closeConnection();
    }
}

/**
 * Database helper functions
 */
class DB {
    private static $instance = null;
    
    /**
     * Get database instance (singleton pattern)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    /**
     * Get database connection
     */
    public static function connection() {
        return self::getInstance()->getConnection();
    }
    
    /**
     * Execute query
     */
    public static function query($sql, $params = []) {
        return self::getInstance()->query($sql, $params);
    }
    
    /**
     * Begin transaction
     */
    public static function beginTransaction() {
        return self::getInstance()->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public static function commit() {
        return self::getInstance()->commit();
    }
    
    /**
     * Rollback transaction
     */
    public static function rollback() {
        return self::getInstance()->rollback();
    }
    
    /**
     * Get last insert ID
     */
    public static function lastInsertId() {
        return self::getInstance()->lastInsertId();
    }
    
    /**
     * Get affected rows
     */
    public static function affectedRows() {
        return self::getInstance()->affectedRows();
    }
    
    /**
     * Table exists
     */
    public static function tableExists($table) {
        return self::getInstance()->tableExists($table);
    }
    
    /**
     * Get table structure
     */
    public static function getTableStructure($table) {
        return self::getInstance()->getTableStructure($table);
    }
    
    /**
     * Get database version
     */
    public static function getVersion() {
        return self::getInstance()->getVersion();
    }
    
    /**
     * Get database stats
     */
    public static function getStats() {
        return self::getInstance()->getStats();
    }
    
    /**
     * Backup database
     */
    public static function backup($backupFile = null) {
        return self::getInstance()->backup($backupFile);
    }
    
    /**
     * Optimize database
     */
    public static function optimize() {
        return self::getInstance()->optimize();
    }
}

// Define access constant
define('IBS_ACCESS', true);
?>
