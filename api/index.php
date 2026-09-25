<?php
// Brainware University Employee Subject Selection System
// Vercel Serverless Function Entry Point
declare(strict_types=1);

require_once __DIR__ . '/../app/Router.php';

Router::dispatch();
