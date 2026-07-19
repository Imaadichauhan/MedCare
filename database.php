<?php
/**
 * Database Configuration
 * Update these values to match your local MySQL/XAMPP/WAMP setup.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'hms_db');
define('DB_USER', 'root');
define('DB_PASS', '');        // Default XAMPP/WAMP MySQL password is empty

// Base URL of the project — change if your folder name differs
define('BASE_URL', 'http://localhost/hms');

/**
 * Returns a PDO connection. Throws PDOException on failure
 * which is caught and shown as a friendly message.
 */
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die("<div style='font-family:sans-serif;padding:40px;color:#b91c1c;'>
                    <h2>Database Connection Failed</h2>
                    <p>Could not connect to MySQL. Please check:</p>
                    <ul>
                        <li>XAMPP/WAMP MySQL service is running</li>
                        <li>Database '" . DB_NAME . "' has been imported (see database/hms_schema.sql)</li>
                        <li>Credentials in config/database.php are correct</li>
                    </ul>
                    <p style='color:#666;font-size:13px;'>Error detail: " . htmlspecialchars($e->getMessage()) . "</p>
                 </div>");
        }
    }
    return $pdo;
}
