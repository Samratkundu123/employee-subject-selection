<?php
// Brainware University Employee Subject Selection System
// Subject Model
declare(strict_types=1);

require_once __DIR__ . '/../database/Database.php';

class Subject
{
    public static function getAllActive(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT id, subject_code, subject_name, active 
             FROM subjects 
             WHERE active = 1 
             ORDER BY id ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $pdo = Database::getConnection();
        // Sanitize integers
        $sanitizedIds = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($sanitizedIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($sanitizedIds), '?'));
        $stmt = $pdo->prepare(
            "SELECT id, subject_code, subject_name, active 
             FROM subjects 
             WHERE id IN ({$placeholders}) AND active = 1"
        );
        $stmt->execute($sanitizedIds);

        $results = [];
        while ($row = $stmt->fetch()) {
            $results[(int)$row['id']] = $row;
        }
        return $results;
    }

    public static function countActive(): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT COUNT(*) FROM subjects WHERE active = 1");
        return (int)$stmt->fetchColumn();
    }
}
