<?php
// Local Development Router for PHP Built-in Server
// Usage: php -S localhost:8000 router.php
declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$publicFile = __DIR__ . '/public' . $uri;

// If static asset exists in public directory, serve it directly with proper MIME type
if ($uri !== '/' && file_exists($publicFile) && !is_dir($publicFile)) {
    $mimeTypes = [
        'css'   => 'text/css; charset=utf-8',
        'js'    => 'application/javascript; charset=utf-8',
        'svg'   => 'image/svg+xml',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'gif'   => 'image/gif',
        'ico'   => 'image/x-icon',
        'woff2' => 'font/woff2',
        'woff'  => 'font/woff',
        'ttf'   => 'font/ttf'
    ];

    $ext = strtolower(pathinfo($publicFile, PATHINFO_EXTENSION));
    if (isset($mimeTypes[$ext])) {
        header('Content-Type: ' . $mimeTypes[$ext]);
    } else {
        header('Content-Type: application/octet-stream');
    }

    header('Content-Length: ' . filesize($publicFile));
    readfile($publicFile);
    exit;
}

// Otherwise, route through the application entry point
require_once __DIR__ . '/public/index.php';
