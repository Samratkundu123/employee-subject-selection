<?php
// Brainware University Employee Subject Selection System
// Authentication, Session, CSRF & Rate Limiting Guard
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../helpers/Response.php';

class Auth
{
    // ==========================================
    // CSRF PROTECTION
    // ==========================================

    public static function csrfToken(): string
    {
        Config::startSession();
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public static function csrfField(): string
    {
        $token = self::csrfToken();
        return '<input type="hidden" name="csrf_token" value="' . Response::escape($token) . '">';
    }

    public static function verifyCsrfToken(?string $token): bool
    {
        Config::startSession();
        if (empty($token) || empty($_SESSION['_csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['_csrf_token'], $token);
    }

    // ==========================================
    // RATE LIMITING
    // ==========================================

    public static function isRateLimited(string $email, int $maxAttempts = 5, int $decaySeconds = 300): bool
    {
        try {
            $pdo = Database::getConnection();
            $ip = Response::getClientIp();
            $since = date('Y-m-d H:i:s', time() - $decaySeconds);

            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM login_attempts 
                 WHERE (ip_address = :ip OR email = :email) AND attempted_at >= :since"
            );
            $stmt->execute([
                ':ip'    => $ip,
                ':email' => strtolower(trim($email)),
                ':since' => $since
            ]);

            $count = (int)$stmt->fetchColumn();
            return $count >= $maxAttempts;
        } catch (Throwable $e) {
            error_log("Rate limit check error: " . $e->getMessage());
            return false; // Fallback to allowing in case of transient DB error
        }
    }

    public static function recordFailedLogin(string $email): void
    {
        try {
            $pdo = Database::getConnection();
            $ip = Response::getClientIp();
            $stmt = $pdo->prepare(
                "INSERT INTO login_attempts (ip_address, email, attempted_at) 
                 VALUES (:ip, :email, NOW())"
            );
            $stmt->execute([
                ':ip'    => $ip,
                ':email' => strtolower(trim($email))
            ]);
        } catch (Throwable $e) {
            error_log("Record failed login error: " . $e->getMessage());
        }
    }

    public static function clearLoginAttempts(string $email): void
    {
        try {
            $pdo = Database::getConnection();
            $ip = Response::getClientIp();
            $stmt = $pdo->prepare(
                "DELETE FROM login_attempts 
                 WHERE ip_address = :ip OR email = :email"
            );
            $stmt->execute([
                ':ip'    => $ip,
                ':email' => strtolower(trim($email))
            ]);
        } catch (Throwable $e) {
            error_log("Clear login attempts error: " . $e->getMessage());
        }
    }

    // ==========================================
    // FACULTY AUTHENTICATION
    // ==========================================

    public static function loginFaculty(array $faculty): void
    {
        Config::startSession();
        session_regenerate_id(true);

        $_SESSION['auth_faculty'] = [
            'id'            => (int)$faculty['id'],
            'name'          => (string)$faculty['name'],
            'email'         => strtolower((string)$faculty['email']),
            'employee_code' => (string)$faculty['employee_code'],
            'role'          => 'faculty',
            'login_time'    => time()
        ];

        self::clearLoginAttempts($faculty['email']);
    }

    public static function isFacultyLoggedIn(): bool
    {
        Config::startSession();
        return !empty($_SESSION['auth_faculty']['id']) && $_SESSION['auth_faculty']['role'] === 'faculty';
    }

    public static function getCurrentFaculty(): ?array
    {
        Config::startSession();
        return self::isFacultyLoggedIn() ? $_SESSION['auth_faculty'] : null;
    }

    public static function requireFaculty(): array
    {
        if (!self::isFacultyLoggedIn()) {
            Response::redirect('/login');
        }
        return $_SESSION['auth_faculty'];
    }

    // ==========================================
    // HOD AUTHENTICATION
    // ==========================================

    public static function loginHod(array $admin): void
    {
        Config::startSession();
        session_regenerate_id(true);

        $_SESSION['auth_hod'] = [
            'id'         => (int)($admin['id'] ?? 1),
            'name'       => (string)($admin['name'] ?? 'Head of Department'),
            'email'      => strtolower((string)$admin['email']),
            'role'       => 'hod',
            'login_time' => time()
        ];

        self::clearLoginAttempts($admin['email']);
    }

    public static function isHodLoggedIn(): bool
    {
        Config::startSession();
        return !empty($_SESSION['auth_hod']['id']) && $_SESSION['auth_hod']['role'] === 'hod';
    }

    public static function getCurrentHod(): ?array
    {
        Config::startSession();
        return self::isHodLoggedIn() ? $_SESSION['auth_hod'] : null;
    }

    public static function requireHod(bool $isApi = false): array
    {
        if (!self::isHodLoggedIn()) {
            if ($isApi) {
                Response::json([
                    'success' => false,
                    'error'   => 'Unauthorized. HOD authentication required.'
                ], 401);
            }
            Response::redirect('/hod/login');
        }
        return $_SESSION['auth_hod'];
    }

    // ==========================================
    // LOGOUT
    // ==========================================

    public static function logout(): void
    {
        Config::startSession();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
    }
}
