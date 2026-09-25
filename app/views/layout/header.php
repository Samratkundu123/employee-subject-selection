<?php
// Layout Header
declare(strict_types=1);

require_once __DIR__ . '/../../auth/Auth.php';
require_once __DIR__ . '/../../helpers/Response.php';

$pageTitle = $pageTitle ?? 'Employee Subject Selection System - Brainware University';
$currentFaculty = Auth::getCurrentFaculty();
$currentHod = Auth::getCurrentHod();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Response::escape($pageTitle) ?></title>
    <meta name="description" content="Official Employee Subject Selection System for Brainware University Department of Computational Sciences">
    
    <!-- Google Fonts: Outfit & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Custom Design System -->
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<header class="navbar-custom">
    <div class="navbar-container">
        <a href="/" class="brand-wrapper">
            <div class="brand-logo-badge">BWU</div>
            <div class="brand-text">
                <h1>Brainware University</h1>
                <span>Department of Computational Sciences</span>
            </div>
        </a>

        <?php if ($currentFaculty): ?>
            <div class="nav-user-meta">
                <div class="user-badge">
                    <span>👨‍🏫 <strong><?= Response::escape($currentFaculty['name']) ?></strong></span>
                    <span class="role-tag"><?= Response::escape($currentFaculty['employee_code']) ?></span>
                </div>
                <a href="/logout" class="btn-nav-logout">Logout</a>
            </div>
        <?php elseif ($currentHod): ?>
            <div class="nav-user-meta">
                <div class="user-badge">
                    <span>🎓 <strong><?= Response::escape($currentHod['name']) ?></strong></span>
                    <span class="role-tag">HOD</span>
                </div>
                <a href="/hod/logout" class="btn-nav-logout">Logout</a>
            </div>
        <?php endif; ?>
    </div>
</header>

<main class="main-content">
