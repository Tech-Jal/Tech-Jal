<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test if file is being accessed
echo "Script started...<br>";

// Test if we can require the file
try {
    require_once 'config/database.php';
    echo "Database config file loaded successfully...<br>";
} catch (Exception $e) {
    echo "Error loading database config: " . $e->getMessage() . "<br>";
    exit;
}

// Test database connection
try {
    $database = new Database();
    echo "Database class instantiated...<br>";
    
    $conn = $database->getConnection();
    echo "getConnection() method called...<br>";

    if($conn) {
        echo "Connected successfully to the database!<br>";
        
        // Test query
        $stmt = $conn->query("SELECT COUNT(*) as category_count FROM categories");
        $result = $stmt->fetch();
        echo "Number of categories in database: " . $result['category_count'] . "<br>";
    } else {
        echo "Connection failed - conn is null<br>";
    }
} catch(PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "<br>";
    echo "Error Code: " . $e->getCode() . "<br>";
} catch(Exception $e) {
    echo "General Error: " . $e->getMessage() . "<br>";
} 