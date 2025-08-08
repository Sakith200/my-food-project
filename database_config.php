<?php
// config.php - Database configuration file

// Database configuration
$host = 'localhost';
$dbname = 'sm_fast_food';
$username = 'root';  // Change this to your MySQL username
$password = '';      // Change this to your MySQL password
$charset = 'utf8mb4';

// PDO options for better security and error handling
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Create DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

try {
    // Create PDO connection
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Optional: Set timezone
    $pdo->exec("SET time_zone = '+05:30'"); // Sri Lanka timezone
    
} catch (PDOException $e) {
    // Log error (in production, don't show database errors to users)
    error_log("Database connection failed: " . $e->getMessage());
    
    // Show user-friendly message
    die("Database connection failed. Please try again later.");
}

// Function to test database connection
function testDatabaseConnection() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT 1");
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Function to get database connection
function getDBConnection() {
    global $pdo;
    return $pdo;
}
?>
