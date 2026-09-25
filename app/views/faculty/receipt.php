<?php
$pageTitle = 'Submission Receipt & Status - Brainware University';
require_once __DIR__ . '/../layout/header.php';

$formattedDate = Response::formatDateTime($submission['submitted_at']);
?>

<div class="receipt-wrapper">
    <div class="receipt-card">
        <div class="receipt-header">
            <div class="receipt-icon-circle">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <h2>Submission Recorded & Locked</h2>
            <p>Your subject preferences have been officially registered</p>
        </div>

        <div class="receipt-body">
            <div class="locked-notice" style="margin-bottom: 1.5rem;">
                <h4>STATUS: <?= Response::escape($submission['status']) ?></h4>
                <p>
                    Your subject selection has already been submitted and finalized in the database. 
                    <strong>You cannot edit, modify, or resubmit your selection.</strong>
                </p>
            </div>

            <div class="receipt-meta-grid">
                <div class="meta-item">
                    <label>Receipt / Submission ID</label>
                    <span style="font-family: monospace; font-size: 1.05rem; color: var(--bwu-navy);">
                        <?= Response::escape($submission['receipt_code']) ?>
                    </span>
                </div>
                <div class="meta-item">
                    <label>Submission Date & Time</label>
                    <span><?= Response::escape($formattedDate['full']) ?></span>
                </div>
                <div class="meta-item">
                    <label>Faculty Name</label>
                    <span><?= Response::escape($submission['faculty_name']) ?></span>
                </div>
                <div class="meta-item">
                    <label>Official Email</label>
                    <span><?= Response::escape($submission['faculty_email']) ?></span>
                </div>
                <div class="meta-item">
                    <label>Employee Code</label>
                    <span><?= Response::escape($submission['employee_code']) ?></span>
                </div>
                <div class="meta-item">
                    <label>Final Status</label>
                    <span style="color: #059669; font-weight: 700;">&#10003; SUBMITTED</span>
                </div>
            </div>

            <h3 style="font-size: 1.05rem; margin-bottom: 12px; color: var(--bwu-navy);">
                Your Selected Subjects (In Chosen Order)
            </h3>

            <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); overflow: hidden; margin-bottom: 1.75rem;">
                <ul class="receipt-subjects-list" style="margin-bottom: 0;">
                    <?php foreach ($submission['subjects'] as $sub): ?>
                        <li class="receipt-subject-item">
                            <div class="receipt-order-num"><?= (int)$sub['selection_order'] ?></div>
                            <div class="receipt-subject-info">
                                <strong><?= Response::escape($sub['subject_name']) ?></strong>
                                <span>Subject Code: <?= Response::escape($sub['subject_code']) ?></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
                <button type="button" class="btn-secondary-custom" onclick="window.print()">
                    🖨️ Print / Save Receipt
                </button>
                <a href="/logout" class="btn-nav-logout" style="padding: 9px 18px; font-size: 0.88rem;">
                    Sign Out
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
