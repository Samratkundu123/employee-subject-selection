<?php
// Brainware University Employee Subject Selection System
// Database Connection Manager using PHP PDO
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

class Database
{
    private static ?PDO $pdo = null;

    public static function getConnection(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $host = (string)Config::get('DB_HOST', '127.0.0.1');
        $port = (string)Config::get('DB_PORT', '3306');
        $dbname = (string)Config::get('DB_DATABASE', 'bwu_subject_selection');
        $username = (string)Config::get('DB_USERNAME', 'root');
        $password = (string)Config::get('DB_PASSWORD', '');

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        // SSL options for hosted production MySQL (e.g. TiDB Cloud, PlanetScale, Aiven, AWS RDS)
        $sslCa = Config::get('DB_SSL_CA');
        if (!empty($sslCa) && file_exists((string)$sslCa)) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = (string)$sslCa;
        } else {
            // Auto-detect standard Linux CA bundles on Vercel / AWS Lambda
            $caPaths = [
                '/etc/pki/tls/certs/ca-bundle.crt',
                '/etc/ssl/certs/ca-certificates.crt',
                '/etc/ssl/cert.pem'
            ];
            foreach ($caPaths as $caPath) {
                if (file_exists($caPath)) {
                    $options[PDO::MYSQL_ATTR_SSL_CA] = $caPath;
                    break;
                }
            }
        }

        try {
            self::$pdo = new PDO($dsn, $username, $password, $options);
            return self::$pdo;
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new RuntimeException("Could not connect to MySQL at {$host}:{$port} (database: '{$dbname}'). Error: " . $e->getMessage());
        }
    }

    public static function testConnection(): array
    {
        $host = (string)Config::get('DB_HOST', '127.0.0.1');
        $port = (string)Config::get('DB_PORT', '3306');
        $dbname = (string)Config::get('DB_DATABASE', 'bwu_subject_selection');

        try {
            $pdo = self::getConnection();
            $pdo->query("SELECT 1");
            return [
                'status'   => 'connected',
                'host'     => $host,
                'port'     => $port,
                'database' => $dbname,
                'error'    => null
            ];
        } catch (Throwable $e) {
            return [
                'status'   => 'error',
                'host'     => $host,
                'port'     => $port,
                'database' => $dbname,
                'error'    => $e->getMessage()
            ];
        }
    }

    public static function beginTransaction(): bool
    {
        return self::getConnection()->beginTransaction();
    }

    public static function commit(): bool
    {
        return self::getConnection()->commit();
    }

    public static function rollBack(): bool
    {
        if (self::getConnection()->inTransaction()) {
            return self::getConnection()->rollBack();
        }
        return false;
    }
}
