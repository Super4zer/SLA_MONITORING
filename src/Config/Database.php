<?php

namespace App\Config;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host = Env::get('DB_HOST', '127.0.0.1');
            $port = Env::get('DB_PORT', '3306');
            $dbName = Env::get('DB_NAME', 'sla_monitoring');
            $user = Env::get('DB_USER', 'root');
            $pass = Env::get('DB_PASS', 'reza1234');

            $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";

            try {
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false, // Ensures real prepared statements
                ]);
            } catch (PDOException $e) {
                // In production, log this instead of throwing directly to the user
                throw new RuntimeException("Database connection failed: " . $e->getMessage());
            }
        }

        return self::$instance;
    }
}
