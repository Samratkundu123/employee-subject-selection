<?php
// Brainware University Employee Subject Selection System
// Submission Model with Database-Level Integrity Enforcement
declare(strict_types=1);

require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/Subject.php';

class Submission
{
    public static function findByFacultyId(int $facultyId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT s.*, f.name AS faculty_name, f.employee_code 
             FROM submissions s
             JOIN faculty f ON s.faculty_id = f.id
             WHERE s.faculty_id = :faculty_id 
             LIMIT 1"
        );
        $stmt->execute([':faculty_id' => $facultyId]);
        $sub = $stmt->fetch();
        return $sub ?: null;
    }

    public static function findByFacultyEmail(string $email): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT s.*, f.name AS faculty_name, f.employee_code 
             FROM submissions s
             JOIN faculty f ON s.faculty_id = f.id
             WHERE s.faculty_email = :email 
             LIMIT 1"
        );
        $stmt->execute([':email' => strtolower(trim($email))]);
        $sub = $stmt->fetch();
        return $sub ?: null;
    }

    public static function getSubmissionDetails(int $submissionId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT s.*, f.name AS faculty_name, f.employee_code, f.email AS faculty_email 
             FROM submissions s
             JOIN faculty f ON s.faculty_id = f.id
             WHERE s.id = :id 
             LIMIT 1"
        );
        $stmt->execute([':id' => $submissionId]);
        $submission = $stmt->fetch();
        if (!$submission) {
            return null;
        }

        // Fetch subjects in exact selection order (1 to 5)
        $subStmt = $pdo->prepare(
            "SELECT ss.selection_order, sub.id, sub.subject_code, sub.subject_name 
             FROM submission_subjects ss
             JOIN subjects sub ON ss.subject_id = sub.id
             WHERE ss.submission_id = :sub_id 
             ORDER BY ss.selection_order ASC"
        );
        $subStmt->execute([':sub_id' => $submissionId]);
        $submission['subjects'] = $subStmt->fetchAll();

        $submission['receipt_code'] = sprintf('SUB-%06d', (int)$submission['id']);
        return $submission;
    }

    public static function getSubmissionByFacultyIdWithSubjects(int $facultyId): ?array
    {
        $sub = self::findByFacultyId($facultyId);
        if (!$sub) {
            return null;
        }
        return self::getSubmissionDetails((int)$sub['id']);
    }

    public static function create(int $facultyId, string $facultyEmail, array $orderedSubjectIds): array
    {
        $cleanEmail = strtolower(trim($facultyEmail));

        // 1. Strict Validation: Count must be EXACTLY 5
        if (count($orderedSubjectIds) !== 5) {
            throw new InvalidArgumentException("Exactly 5 subjects must be selected. Received " . count($orderedSubjectIds) . ".");
        }

        // 2. Strict Validation: Duplicate check
        $uniqueIds = array_unique($orderedSubjectIds);
        if (count($uniqueIds) !== 5) {
            throw new InvalidArgumentException("Duplicate subjects are not allowed in selection.");
        }

        // 3. Strict Validation: Validate all IDs are positive integers
        $sanitizedIds = [];
        foreach ($orderedSubjectIds as $id) {
            $intId = (int)$id;
            if ($intId <= 0) {
                throw new InvalidArgumentException("Invalid subject identifier provided.");
            }
            $sanitizedIds[] = $intId;
        }

        // 4. Verify all 5 subjects exist and are active in catalog
        $catalogSubjects = Subject::findByIds($sanitizedIds);
        if (count($catalogSubjects) !== 5) {
            throw new InvalidArgumentException("One or more selected subjects are invalid or inactive.");
        }

        // 5. Pre-check: Has this faculty/email already submitted?
        $existing = self::findByFacultyEmail($cleanEmail);
        if ($existing !== null) {
            throw new RuntimeException("You have already submitted your subject selection. Resubmission is not allowed.", 409);
        }

        $existingById = self::findByFacultyId($facultyId);
        if ($existingById !== null) {
            throw new RuntimeException("You have already submitted your subject selection. Resubmission is not allowed.", 409);
        }

        // 6. Begin Transaction
        $pdo = Database::getConnection();
        Database::beginTransaction();

        try {
            // Insert Submission master record
            $insertSub = $pdo->prepare(
                "INSERT INTO submissions (faculty_id, faculty_email, submitted_at, status) 
                 VALUES (:faculty_id, :faculty_email, NOW(), 'SUBMITTED')"
            );
            $insertSub->execute([
                ':faculty_id'    => $facultyId,
                ':faculty_email' => $cleanEmail
            ]);

            $submissionId = (int)$pdo->lastInsertId();

            // Insert 5 subjects in exact selected order (1 to 5)
            $insertSubject = $pdo->prepare(
                "INSERT INTO submission_subjects (submission_id, subject_id, selection_order) 
                 VALUES (:submission_id, :subject_id, :selection_order)"
            );

            for ($i = 0; $i < 5; $i++) {
                $insertSubject->execute([
                    ':submission_id'   => $submissionId,
                    ':subject_id'      => $sanitizedIds[$i],
                    ':selection_order' => $i + 1
                ]);
            }

            Database::commit();

            return self::getSubmissionDetails($submissionId);

        } catch (PDOException $e) {
            Database::rollBack();
            // 23000 indicates unique constraint violation (duplicate key)
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate entry')) {
                throw new RuntimeException("You have already submitted your subject selection. Resubmission is not allowed.", 409);
            }
            error_log("Submission insertion failed: " . $e->getMessage());
            throw new RuntimeException("An error occurred while saving your subject selection. Please try again.");
        } catch (Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }
}
