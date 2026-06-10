<?php
/**
 * SmartLib — PDO database singleton.
 *
 * Uses env vars loaded by phpdotenv in public/index.php.
 * All queries elsewhere must use prepared statements (PDO::prepare + execute).
 */

declare(strict_types=1);

function getDB(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $name = $_ENV['DB_NAME'] ?? 'smartlib';
    $user = $_ENV['DB_USER'] ?? 'root';
    $pass = $_ENV['DB_PASS'] ?? '';

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Use real prepared statements (not emulated) for true SQLi protection
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    return $pdo;
}
