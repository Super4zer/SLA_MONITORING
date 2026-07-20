<?php

namespace App\Config;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?PDO $instance = null;

    // Tambahan: Mencegah instansiasi langsung dari luar kelas (Prinsip Pure Singleton)
    private function __construct() {}
    private function __clone() {}
    public function __wakeup() {}

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            // Mengambil konfigurasi dari environment variables
            $host   = Env::get('DB_HOST', '127.0.0.1');
            $port   = Env::get('DB_PORT', '3306');
            $dbName = Env::get('DB_NAME', 'sla_monitoring');
            $user   = Env::get('DB_USER', 'Ridz'); // Pastikan user ini terdaftar di MySQL lokal Anda
            $pass   = Env::get('DB_PASS', 'tokisaki'); // Pastikan password ini cocok di MySQL lokal Anda

            $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";

            try {
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false, 
                ]);
            } catch (PDOException $e) {
                // Memberikan pesan eror yang lebih informatif saat debugging lokal
                throw new RuntimeException("Database connection failed di localhost: " . $e->getMessage(), (int)$e->getCode());
            }
        }

        return self::$instance;
    }
}