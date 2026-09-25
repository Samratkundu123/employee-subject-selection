<?php
// Brainware University Employee Subject Selection System
// Faculty Model
declare(strict_types=1);

require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../helpers/Response.php';

class Faculty
{
    public static function findByEmail(string $email): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT * FROM faculty 
             WHERE email = :email AND status = 'Active' 
             LIMIT 1"
        );
        $stmt->execute([':email' => strtolower(trim($email))]);
        $faculty = $stmt->fetch();
        return $faculty ?: null;
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT * FROM faculty 
             WHERE id = :id 
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $faculty = $stmt->fetch();
        return $faculty ?: null;
    }

    public static function create(string $name, string $email, string $employeeCode, string $password): array
    {
        $cleanEmail = strtolower(trim($email));
        $cleanName = trim($name);
        $cleanCode = strtoupper(trim($employeeCode));

        if ($cleanName === '' || $cleanEmail === '' || $cleanCode === '') {
            throw new InvalidArgumentException("Name, Email, and Employee Code are required.");
        }

        if (!Response::isValidEmail($cleanEmail)) {
            throw new InvalidArgumentException("Invalid email format.");
        }

        $existing = self::findByEmail($cleanEmail);
        if ($existing !== null) {
            throw new RuntimeException("A faculty member with this email already exists.", 409);
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id FROM faculty WHERE employee_code = :code LIMIT 1");
        $stmt->execute([':code' => $cleanCode]);
        if ($stmt->fetch()) {
            throw new RuntimeException("A faculty member with employee code {$cleanCode} already exists.", 409);
        }

        $pass = trim($password) !== '' ? trim($password) : 'Faculty@123';
        $hash = password_hash($pass, PASSWORD_DEFAULT);

        $insert = $pdo->prepare(
            "INSERT INTO faculty (employee_code, name, email, password_hash, status) 
             VALUES (:code, :name, :email, :hash, 'Active')"
        );
        $insert->execute([
            ':code'  => $cleanCode,
            ':name'  => $cleanName,
            ':email' => $cleanEmail,
            ':hash'  => $hash
        ]);

        $newId = (int)$pdo->lastInsertId();
        return self::findById($newId);
    }

    public static function updateDetails(int $id, string $name, string $employeeCode): bool
    {
        $cleanName = trim($name);
        $cleanCode = strtoupper(trim($employeeCode));

        if ($cleanName === '' || $cleanCode === '') {
            throw new InvalidArgumentException("Faculty Name and Employee Code cannot be empty.");
        }

        $pdo = Database::getConnection();
        
        // Check if employee_code is already taken by another faculty member
        $stmt = $pdo->prepare("SELECT id FROM faculty WHERE employee_code = :code AND id != :id LIMIT 1");
        $stmt->execute([':code' => $cleanCode, ':id' => $id]);
        if ($stmt->fetch()) {
            throw new RuntimeException("The employee code '{$cleanCode}' is already registered to another faculty member.", 409);
        }

        $update = $pdo->prepare("UPDATE faculty SET name = :name, employee_code = :code WHERE id = :id");
        return $update->execute([
            ':name' => $cleanName,
            ':code' => $cleanCode,
            ':id'   => $id
        ]);
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM faculty WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public static function getCounts(): array
    {
        $pdo = Database::getConnection();

        $totalStmt = $pdo->query("SELECT COUNT(*) FROM faculty WHERE status = 'Active'");
        $total = (int)$totalStmt->fetchColumn();

        $submittedStmt = $pdo->query(
            "SELECT COUNT(DISTINCT s.faculty_id) 
             FROM submissions s 
             JOIN faculty f ON s.faculty_id = f.id 
             WHERE f.status = 'Active'"
        );
        $submitted = (int)$submittedStmt->fetchColumn();
        $pending = max(0, $total - $submitted);

        return [
            'total'     => $total,
            'submitted' => $submitted,
            'pending'   => $pending
        ];
    }

    public static function getAllWithSubmissionStatus(?string $search = null, ?string $filter = null): array
    {
        $pdo = Database::getConnection();

        $sql = "SELECT 
                    f.id AS faculty_id,
                    f.employee_code,
                    f.name AS faculty_name,
                    f.email AS faculty_email,
                    s.id AS submission_id,
                    s.submitted_at,
                    s.status AS submission_status
                FROM faculty f
                LEFT JOIN submissions s ON f.id = s.faculty_id
                WHERE f.status = 'Active'";

        $params = [];

        // Apply Search
        if ($search !== null && trim($search) !== '') {
            $term = '%' . trim($search) . '%';
            $sql .= " AND (f.name LIKE :s_name OR f.email LIKE :s_email OR f.employee_code LIKE :s_code)";
            $params[':s_name'] = $term;
            $params[':s_email'] = $term;
            $params[':s_code'] = $term;
        }

        // Apply Filter
        $normalizedFilter = strtolower(trim((string)$filter));
        if ($normalizedFilter === 'submitted') {
            $sql .= " AND s.id IS NOT NULL";
        } elseif ($normalizedFilter === 'pending') {
            $sql .= " AND s.id IS NULL";
        }

        $sql .= " ORDER BY (s.id IS NOT NULL) DESC, s.submitted_at DESC, f.id ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        if (empty($rows)) {
            return [];
        }

        // Collect submission IDs to load subjects in exact order
        $submissionIds = [];
        foreach ($rows as $r) {
            if (!empty($r['submission_id'])) {
                $submissionIds[] = (int)$r['submission_id'];
            }
        }

        $subjectsBySub = [];
        if (!empty($submissionIds)) {
            $placeholders = implode(',', array_fill(0, count($submissionIds), '?'));
            $subStmt = $pdo->prepare(
                "SELECT ss.submission_id, ss.selection_order, sub.subject_code, sub.subject_name
                 FROM submission_subjects ss
                 JOIN subjects sub ON ss.subject_id = sub.id
                 WHERE ss.submission_id IN ({$placeholders})
                 ORDER BY ss.submission_id ASC, ss.selection_order ASC"
            );
            $subStmt->execute($submissionIds);
            while ($subRow = $subStmt->fetch()) {
                $subId = (int)$subRow['submission_id'];
                $order = (int)$subRow['selection_order'];
                $subjectsBySub[$subId][$order] = $subRow['subject_name'];
            }
        }

        // Format each row with 5 subjects and clean display data
        $result = [];
        $slNo = 1;
        foreach ($rows as $row) {
            $subId = !empty($row['submission_id']) ? (int)$row['submission_id'] : null;
            $isSubmitted = ($subId !== null);

            $dateTime = Response::formatDateTime($row['submitted_at']);

            $subject1 = $isSubmitted ? ($subjectsBySub[$subId][1] ?? '-') : '-';
            $subject2 = $isSubmitted ? ($subjectsBySub[$subId][2] ?? '-') : '-';
            $subject3 = $isSubmitted ? ($subjectsBySub[$subId][3] ?? '-') : '-';
            $subject4 = $isSubmitted ? ($subjectsBySub[$subId][4] ?? '-') : '-';
            $subject5 = $isSubmitted ? ($subjectsBySub[$subId][5] ?? '-') : '-';

            $result[] = [
                'sl_no'           => $slNo++,
                'faculty_id'      => (int)$row['faculty_id'],
                'employee_code'   => (string)$row['employee_code'],
                'faculty_name'    => (string)$row['faculty_name'],
                'faculty_email'   => (string)$row['faculty_email'],
                'submission_id'   => $subId,
                'receipt_code'    => $isSubmitted ? sprintf('SUB-%06d', $subId) : '-',
                'subject_1'       => $subject1,
                'subject_2'       => $subject2,
                'subject_3'       => $subject3,
                'subject_4'       => $subject4,
                'subject_5'       => $subject5,
                'submission_date' => $dateTime['date'],
                'submission_time' => $dateTime['time'],
                'submitted_at'    => $row['submitted_at'],
                'status'          => $isSubmitted ? 'Submitted' : 'Pending'
            ];
        }

        return $result;
    }

    public static function deleteAll(): bool
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
            $pdo->exec("TRUNCATE TABLE submission_subjects;");
            $pdo->exec("TRUNCATE TABLE submissions;");
            $pdo->exec("TRUNCATE TABLE faculty;");
            $pdo->exec("TRUNCATE TABLE login_attempts;");
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
