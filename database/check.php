<?php
require_once __DIR__ . '/../app/database/Database.php';

try {
    $pdo = Database::getConnection();
    echo "Database: Connected successfully!" . PHP_EOL;
    echo "Faculty count: " . $pdo->query("SELECT count(*) FROM faculty")->fetchColumn() . PHP_EOL;
    echo "Subjects count: " . $pdo->query("SELECT count(*) FROM subjects")->fetchColumn() . PHP_EOL;
    echo "Admins count: " . $pdo->query("SELECT count(*) FROM admins")->fetchColumn() . PHP_EOL;
    echo "Submissions count: " . $pdo->query("SELECT count(*) FROM submissions")->fetchColumn() . PHP_EOL;
    
    $faculty = $pdo->query("SELECT id, name, email, employee_code, status FROM faculty LIMIT 5")->fetchAll();
    echo "Faculty rows in DB: " . json_encode($faculty, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Throwable $e) {
    echo "Database error: " . $e->getMessage() . PHP_EOL;
}
