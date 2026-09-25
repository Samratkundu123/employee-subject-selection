@echo off
title Brainware University - Employee Subject Selection System
echo ==============================================================
echo   Brainware University - Employee Subject Selection System
echo ==============================================================
echo.

REM Check if .env exists
if not exist ".env" (
    echo [.env] not found. Creating from .env.example...
    copy .env.example .env
)

echo Starting PHP built-in web server on http://localhost:8000 ...
echo Press Ctrl+C at any time to stop the server.
echo.

php -S localhost:8000 router.php
pause
