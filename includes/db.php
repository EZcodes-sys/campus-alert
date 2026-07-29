<?php
require_once __DIR__ . '/config.php';

/**
 * Returns a single shared PDO connection (singleton pattern).
 * Uses real prepared statements (emulation disabled) to prevent SQL injection.
 */
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE  => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES    => false,
            ]);
        } catch (PDOException $e) {
            // Never leak DB credentials/details to the user
            die('Database connection failed. Please contact the system administrator.');
        }
    }

    return $pdo;
}
