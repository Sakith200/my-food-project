<?php
// Database configuration
$host = 'localhost';
$dbname = 'sm_fast_food';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_PERSISTENT         => true,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    $pdo->exec("SET time_zone = '+05:30'");
    $pdo->exec("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

function getDBConnection() {
    global $pdo;
    return $pdo;
}

// Database helper functions
function executeQuery($query, $params = []) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query execution failed: " . $e->getMessage());
        throw new Exception("Database operation failed");
    }
}

function fetchAll($query, $params = []) {
    $stmt = executeQuery($query, $params);
    return $stmt->fetchAll();
}

function fetchOne($query, $params = []) {
    $stmt = executeQuery($query, $params);
    return $stmt->fetch();
}

function getLastInsertId() {
    $pdo = getDBConnection();
    return $pdo->lastInsertId();
}

// Security functions
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhone($phone) {
    return preg_match('/^[0-9+\-\s()]+$/', $phone);
}
?>