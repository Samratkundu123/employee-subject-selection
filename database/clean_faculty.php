<?php
// Clean all dummy faculty and submissions from both local and cloud databases
declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/database/Database.php';

echo "Cleaning faculty data..." . PHP_EOL;

try {
    $pdo = Database::getConnection();
    $host = Config::get('DB_HOST');
    $db = Config::get('DB_DATABASE');
    echo "Connected to: $host ($db)" . PHP_EOL;

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("TRUNCATE TABLE submission_subjects;");
    $pdo->exec("TRUNCATE TABLE submissions;");
    $pdo->exec("TRUNCATE TABLE faculty;");
    $pdo->exec("TRUNCATE TABLE login_attempts;");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    $facultyCount = (int)$pdo->query("SELECT COUNT(*) FROM faculty")->fetchColumn();
    $subCount = (int)$pdo->query("SELECT COUNT(*) FROM submissions")->fetchColumn();
    $subjectsCount = (int)$pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
    $adminsCount = (int)$pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();

    echo "Cleanup Successful!" . PHP_EOL;
    echo " - Faculty count: $facultyCount" . PHP_EOL;
    echo " - Submissions count: $subCount" . PHP_EOL;
    echo " - Active Subjects: $subjectsCount" . PHP_EOL;
    echo " - Admins: $adminsCount" . PHP_EOL;

} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
