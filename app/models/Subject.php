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

    /**
     * Imports an array of subjects.
     * If $replace is true:
     * - If no submissions exist, clears existing subjects.
     * - If submissions exist, deactivates older subjects to protect foreign key integrity.
     */
    public static function importSubjects(array $subjects, bool $replace = true): int
    {
        if (empty($subjects)) {
            throw new InvalidArgumentException("No valid subjects found to import.");
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $subCount = (int)$pdo->query("SELECT COUNT(*) FROM submissions")->fetchColumn();

            if ($replace) {
                if ($subCount > 0) {
                    $pdo->exec("UPDATE subjects SET active = 0");
                } else {
                    $pdo->exec("DELETE FROM subjects");
                }
            }

            $stmt = $pdo->prepare(
                "INSERT INTO subjects (subject_code, subject_name, active) 
                 VALUES (:code, :name, 1)"
            );

            $imported = 0;
            foreach ($subjects as $s) {
                $code = trim($s['subject_code'] ?? '');
                $name = trim($s['subject_name'] ?? '');

                if ($name === '') {
                    continue;
                }

                if ($code === '') {
                    $code = sprintf('SUB-%02d', $imported + 1);
                }

                $stmt->execute([
                    ':code' => $code,
                    ':name' => $name
                ]);
                $imported++;
            }

            $pdo->commit();
            return $imported;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function getTemplateCsv(): string
    {
        $csv = "Subject Code,Subject Name\r\n";
        $csv .= "SUB-01,Database Management System\r\n";
        $csv .= "SUB-02,Computer Network\r\n";
        $csv .= "SUB-03,Operating System\r\n";
        $csv .= "SUB-04,Data Structure and Algorithm\r\n";
        $csv .= "SUB-05,Machine Learning\r\n";
        return $csv;
    }
}
