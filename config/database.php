<?php
define('DB_HOST',    getenv('DB_HOST') ?: 'localhost');
define('DB_PORT',    getenv('DB_PORT') ?: '5432');
define('DB_NAME',    getenv('DB_NAME') ?: 'quizsystem');
define('DB_USER',    getenv('DB_USER') ?: 'postgres');
define('DB_PASS',    getenv('DB_PASS') ?: 'rorn');

$dsn = sprintf(
    'pgsql:host=%s;port=%s;dbname=%s',
    DB_HOST, DB_PORT, DB_NAME
);

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_PERSISTENT         => false,
];

function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        global $dsn, $options;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('[DB ERROR] ' . $e->getMessage());
            http_response_code(500);
            die($e->getMessage());
        }
    }

    return $pdo;
}

$pdo = getPDO();
    
?>