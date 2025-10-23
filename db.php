<?php
/**
 * Database Connection and Configuration
 *
 * This script establishes a connection to the MySQL database using PDO (PHP Data Objects).
 * It sets up error handling and other best-practice options for database interaction.
 * It should be included at the beginning of any PHP script that needs database access.
 */

// --- Development Error Reporting ---
// WARNING: These should be turned OFF for a production environment.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- Database Configuration ---
$host = 'localhost';       // Or your database host (e.g., 127.0.0.1)
$dbname = 'samspikpok_db'; // The database name
$username = 'root';        // The database username
$password = 'root';        // The database password for MAMP/XAMPP
$charset = 'utf8mb4';      // The character set

// --- Data Source Name (DSN) & PDO Options ---
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,      // Throw exceptions on error
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,            // Fetch results as associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                       // Use native prepared statements
];

// --- Establish PDO Connection ---
try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (\PDOException $e) {
    // For security, log the detailed error instead of displaying it to the user.
    error_log("Database Connection Error: " . $e->getMessage());

    // Display a user-friendly error message.
    // This prevents partial page loads that can cause confusing JavaScript errors.
    http_response_code(503); // Service Unavailable
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Service Unavailable</title>
<style>body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;text-align:center;padding:40px;background-color:#f8f9fa;color:#6c757d;} h1{font-weight:500;} .container{max-width:600px;margin:0 auto;background:#fff;padding:30px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);}</style>
</head>
<body>
    <div class="container">
        <h1>Database Connection Failed</h1>
        <p>We are currently unable to connect to the database. Please check the system configuration and ensure the database server is running.</p>
    </div>
</body></html>
HTML;
    exit; // Stop script execution
}