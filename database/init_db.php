<?php
// Database Initialization & Migration Script
declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';

echo "=== Initializing Brainware Subject Selection Database ===" . PHP_EOL;

try {
    $dbHost = Config::get('DB_HOST', '127.0.0.1');
    $dbPort = Config::get('DB_PORT', '3306');
    $dbName = Config::get('DB_DATABASE', 'bwu_subject_selection');
    $dbUser = Config::get('DB_USERNAME', 'root');
    $dbPass = Config::get('DB_PASSWORD', '');

    // Step 1: Connect to server without database to create DB if needed
    $dsnRoot = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
    $pdo = new PDO($dsnRoot, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "[1/3] Creating database `{$dbName}` if it doesn't exist..." . PHP_EOL;
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$dbName}`");

    // Step 2: Apply Schema
    echo "[2/3] Applying schema (database/schema.sql)..." . PHP_EOL;
    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new RuntimeException("Schema file not found at {$schemaFile}");
    }
    $schemaSql = file_get_contents($schemaFile);
    $pdo->exec($schemaSql);

    // Step 3: Apply Seed Data
    echo "[3/3] Applying seed data (database/seed.sql)..." . PHP_EOL;
    $seedFile = __DIR__ . '/seed.sql';
    if (!file_exists($seedFile)) {
        throw new RuntimeException("Seed file not found at {$seedFile}");
    }
    $seedSql = file_get_contents($seedFile);
    $pdo->exec($seedSql);

    // Verify Counts
    $facultyCount = $pdo->query("SELECT COUNT(*) FROM faculty")->fetchColumn();
    $subjectCount = $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
    $subCount = $pdo->query("SELECT COUNT(*) FROM submissions")->fetchColumn();

    echo PHP_EOL . " Database initialized successfully!" . PHP_EOL;
    echo "  - Subjects: {$subjectCount}" . PHP_EOL;
    echo "  - Faculty: {$facultyCount}" . PHP_EOL;
    echo "  - Submissions: {$subCount}" . PHP_EOL;

} catch (Throwable $e) {
    echo " Error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
