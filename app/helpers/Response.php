<?php
// Brainware University Employee Subject Selection System
// Response & Utility Helper
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

class Response
{
    public static function json(mixed $data, int $statusCode = 200): void
    {
        Config::sendSecurityHeaders();
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function redirect(string $path): void
    {
        header("Location: {$path}");
        exit;
    }

    public static function escape(?string $string): string
    {
        if ($string === null) {
            return '';
        }
        return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($parts[0]);
        }
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    public static function isValidEmail(string $email): bool
    {
        $clean = trim(strtolower($email));
        return filter_var($clean, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function formatDateTime(?string $timestamp): array
    {
        if (!$timestamp) {
            return [
                'date' => '-',
                'time' => '-',
                'full' => '-'
            ];
        }

        try {
            $dt = new DateTime($timestamp, new DateTimeZone('Asia/Kolkata'));
            return [
                'date' => $dt->format('d-m-Y'),
                'time' => $dt->format('h:i:s A'),
                'full' => $dt->format('d-m-Y h:i:s A')
            ];
        } catch (Exception) {
            return [
                'date' => $timestamp,
                'time' => '',
                'full' => $timestamp
            ];
        }
    }
}
