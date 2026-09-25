<?php
// Brainware University Employee Subject Selection System
// Configuration & Environment Loader
declare(strict_types=1);

class Config
{
    private static bool $loaded = false;
    private static array $env = [];

    public static function init(): void
    {
        if (self::$loaded) {
            return;
        }

        // Set Default Timezone to IST (Asia/Kolkata)
        date_default_timezone_set('Asia/Kolkata');

        // Load .env if present (Local environment)
        $envPath = dirname(__DIR__, 2) . '/.env';
        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }
                if (str_contains($line, '=')) {
                    [$name, $value] = explode('=', $line, 2);
                    $name = trim($name);
                    $value = trim($value);
                    // Strip optional quotes
                    if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                        (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                        $value = substr($value, 1, -1);
                    }
                    self::$env[$name] = $value;
                    if (!isset($_SERVER[$name])) {
                        $_SERVER[$name] = $value;
                    }
                    if (!isset($_ENV[$name])) {
                        $_ENV[$name] = $value;
                    }
                }
            }
        }

        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::init();

        if (isset(self::$env[$key])) {
            return self::$env[$key];
        }
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }
        $val = getenv($key);
        if ($val !== false) {
            return $val;
        }

        return $default;
    }

    public static function isProduction(): bool
    {
        return strtolower((string)self::get('APP_ENV', 'production')) === 'production';
    }

    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $isHttps = (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
            self::isProduction()
        );

        // Security session cookie settings
        session_set_cookie_params([
            'lifetime' => 0, // Until browser closes
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');

        session_start();

        // Enforce 3-hour inactivity timeout
        $now = time();
        $timeout = 10800; // 3 hours
        if (isset($_SESSION['LAST_ACTIVITY']) && ($now - $_SESSION['LAST_ACTIVITY'] > $timeout)) {
            session_unset();
            session_destroy();
            session_start();
        }
        $_SESSION['LAST_ACTIVITY'] = $now;
    }

    public static function sendSecurityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Permissions-Policy: geolocation=(), camera=(), microphone=()");
        // Cache control for dynamic application pages
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
    }
}

Config::init();
