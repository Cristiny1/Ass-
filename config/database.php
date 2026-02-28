<?php
// =============================================================================
// config/db.php
// Central PDO database connection — include this file in every PHP page.
// Usage:  require_once __DIR__ . '/../config/db.php';
//         $row = $pdo->prepare("SELECT ..."); ...
// =============================================================================

// ── Connection settings ───────────────────────────────────────────────────────
// In production: move these to a .env file or server environment variables.
// NEVER commit real credentials to version control.
define('DB_HOST',    getenv('DB_HOST')    ?: 'localhost');
define('DB_PORT',    getenv('DB_PORT')    ?: '3306');
define('DB_NAME',    getenv('DB_NAME')    ?: 'quizsystem');
define('DB_USER',    getenv('DB_USER')    ?: 'root');       // change in production
define('DB_PASS',    getenv('DB_PASS')    ?: '');           // change in production
define('DB_CHARSET', 'utf8mb4');

// ── PDO options ───────────────────────────────────────────────────────────────
$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
);

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // throw on SQL errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // arrays by default
    PDO::ATTR_EMULATE_PREPARES   => false,                    // real prepared statements
    PDO::ATTR_PERSISTENT         => false,                    // no connection pooling (safe default)
    PDO::MYSQL_ATTR_FOUND_ROWS   => true,                     // UPDATE returns matched rows
];

// ── Create connection (singleton pattern) ─────────────────────────────────────
function getPDO() : PDO
{
    static $pdo = null;

    if ($pdo === null) {
        global $dsn, $options;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log the real error privately — never expose it to the browser
            error_log('[DB ERROR] ' . $e->getMessage());
            http_response_code(500);
            die('A database error occurred. Please try again later.');
        }
    }

    return $pdo;
}

// Shorthand — every file can call $pdo directly after require_once
$pdo = getPDO();