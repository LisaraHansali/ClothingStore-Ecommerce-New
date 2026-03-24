<?php


class Database {
    private static $connection;
    private static $host = "localhost";
    private static $username = "root";
    private static $password = "";
    private static $database = "clothesstore_db";
    private static $port = "3306";
    
    /**
     * Establish database connection
     * @return mysqli connection object
     */
    public static function setUpConnection() {
        if (!isset(Database::$connection) || Database::$connection->connect_errno) {
            try {
                Database::$connection = new mysqli(
                    Database::$host, 
                    Database::$username, 
                    Database::$password, 
                    Database::$database, 
                    Database::$port
                );
                
                // Check for connection errors
                if (Database::$connection->connect_error) {
                    throw new Exception("Connection failed: " . Database::$connection->connect_error);
                }
                
                // Set charset to UTF-8 for proper encoding
                Database::$connection->set_charset("utf8");
                
            } catch (Exception $e) {
                error_log("Database connection error: " . $e->getMessage());
                die("Database connection failed. Please try again later.");
            }
        }
        
        return Database::$connection;
    }
    
    /**
     * Execute INSERT, UPDATE, DELETE queries
     * @param string $query SQL query
     * @return bool success status
     */
    public static function iud($query) {
        Database::setUpConnection();
        
        try {
            $result = Database::$connection->query($query);
            
            if (!$result) {
                throw new Exception("Query execution failed: " . Database::$connection->error);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("IUD Query error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Execute SELECT queries
     * @param string $query SQL query
     * @return mysqli_result|bool
     */
    public static function search($query) {
        Database::setUpConnection();
        
        try {
            $resultset = Database::$connection->query($query);
            
            if (!$resultset) {
                throw new Exception("Search query failed: " . Database::$connection->error);
            }
            
            return $resultset;
        } catch (Exception $e) {
            error_log("Search Query error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Execute prepared statements safely (recommended for user input)
     * @param string $query SQL query with placeholders
     * @param array $params Parameters to bind
     * @param string $types Parameter types (s=string, i=integer, d=double, b=blob)
     * @return mysqli_stmt|bool
     */
    public static function prepare($query, $params = [], $types = '') {
        Database::setUpConnection();
        
        try {
            $stmt = Database::$connection->prepare($query);
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . Database::$connection->error);
            }
            
            // Bind parameters if provided
            if (!empty($params) && !empty($types)) {
                $stmt->bind_param($types, ...$params);
            }
            
            return $stmt;
        } catch (Exception $e) {
            error_log("Prepared statement error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get single row from database
     * @param string $query SQL query
     * @param array $params Parameters for prepared statement
     * @param string $types Parameter types
     * @return array|bool
     */
    public static function getSingleRow($query, $params = [], $types = '') {
        $stmt = Database::prepare($query, $params, $types);
        
        if ($stmt && $stmt->execute()) {
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        }
        
        return false;
    }
    
    /**
     * Get multiple rows from database
     * @param string $query SQL query
     * @param array $params Parameters for prepared statement
     * @param string $types Parameter types
     * @return array
     */
    public static function getMultipleRows($query, $params = [], $types = '') {
        $stmt = Database::prepare($query, $params, $types);
        
        if ($stmt && $stmt->execute()) {
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        
        return [];
    }
    
    /**
     * Escape string to prevent SQL injection (use prepared statements instead when possible)
     * @param string $string String to escape
     * @return string
     */
    public static function escape($string) {
        Database::setUpConnection();
        return Database::$connection->real_escape_string($string);
    }
    
    /**
     * Get last inserted ID
     * @return int
     */
    public static function getLastInsertId() {
        Database::setUpConnection();
        return Database::$connection->insert_id;
    }
    
    /**
     * Get number of affected rows
     * @return int
     */
    public static function getAffectedRows() {
        Database::setUpConnection();
        return Database::$connection->affected_rows;
    }
    
    /**
     * Begin transaction
     * @return bool
     */
    public static function beginTransaction() {
        Database::setUpConnection();
        return Database::$connection->autocommit(false);
    }
    
    /**
     * Commit transaction
     * @return bool
     */
    public static function commit() {
        Database::setUpConnection();
        $result = Database::$connection->commit();
        Database::$connection->autocommit(true);
        return $result;
    }
    
    /**
     * Rollback transaction
     * @return bool
     */
    public static function rollback() {
        Database::setUpConnection();
        $result = Database::$connection->rollback();
        Database::$connection->autocommit(true);
        return $result;
    }
    
    /**
     * Close database connection
     */
    public static function closeConnection() {
        if (isset(Database::$connection)) {
            Database::$connection->close();
            Database::$connection = null;
        }
    }
    
    /**
     * Check if connection is alive
     * @return bool
     */
    public static function isConnected() {
        return isset(Database::$connection) && Database::$connection->ping();
    }
}

// Helper functions for common operations

/**
 * Execute a safe query with automatic parameter binding
 * @param string $query SQL query with placeholders
 * @param array $params Parameters to bind
 * @return mysqli_result|bool
 */
function executeQuery($query, $params = []) {
    if (empty($params)) {
        return Database::search($query);
    }
    
    // Auto-detect parameter types
    $types = '';
    foreach ($params as $param) {
        if (is_int($param)) {
            $types .= 'i';
        } elseif (is_float($param)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
    }
    
    $stmt = Database::prepare($query, $params, $types);
    if ($stmt && $stmt->execute()) {
        return $stmt->get_result();
    }
    
    return false;
}

/**
 * Execute INSERT, UPDATE, DELETE with parameters
 * @param string $query SQL query with placeholders
 * @param array $params Parameters to bind
 * @return bool
 */
function executeUpdate($query, $params = []) {
    if (empty($params)) {
        return Database::iud($query);
    }
    
    // Auto-detect parameter types
    $types = '';
    foreach ($params as $param) {
        if (is_int($param)) {
            $types .= 'i';
        } elseif (is_float($param)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
    }
    
    $stmt = Database::prepare($query, $params, $types);
    if ($stmt) {
        return $stmt->execute();
    }
    
    return false;
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set error reporting for development (remove in production)
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

?>