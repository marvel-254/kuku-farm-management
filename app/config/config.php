<?php
// Database Configuration - SQLite
define('DB_HOST', 'localhost');
define('DB_USER', '');
define('DB_PASSWORD', '');
define('DB_NAME', 'kuku_farm.sqlite');
define('DB_PATH', __DIR__ . '/../../database/' . DB_NAME);

// Application Configuration
define('APP_NAME', 'KUKU');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost:4567');

// Security Configuration
define('HASH_ALGO', PASSWORD_DEFAULT);
define('SESSION_LIFETIME', 3600); // 1 hour

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/php_errors.log');

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    $sessionPath = __DIR__ . '/../../sessions';
    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0755, true);
    }
    ini_set('session.save_path', $sessionPath);
    session_start();
}

// Database Connection
function getConnection() {
    try {
        if (!file_exists(DB_PATH)) {
            throw new Exception("Database file not found: " . DB_PATH);
        }
        
        $conn = new PDO('sqlite:' . DB_PATH);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // Enable foreign keys
        $conn->exec('PRAGMA foreign_keys = ON');
        
        return $conn;
    } catch(PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());
        die("Database connection failed. Please contact administrator.");
    }
}

// Helper Functions
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/login.php');
    }
}

function getUserRole() {
    return $_SESSION['user_role'] ?? 'guest';
}

// Logging function
function log_message($message, $level = 'INFO') {
    $logFile = __DIR__ . '/../../logs/app.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

function hasRole($role) {
    return getUserRole() === $role;
}
?>