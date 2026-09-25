<?php
$pageTitle = 'Select Subjects - Faculty Portal';
require_once __DIR__ . '/../layout/header.php';
?>

<!-- Header Info Card -->
<div class="dashboard-header-card">
    <div class="welcome-info">
        <h2>Faculty Subject Selection</h2>
        <p>
            Welcome, <strong><?= Response::escape($faculty['name']) ?></strong> &bull; 
            <span><?= Response::escape($faculty['email']) ?></span> &bull; 
            Emp Code: <strong><?= Response::escape($faculty['employee_code']) ?></strong>
        </p>
        <p style="margin-top: 4px; color: var(--text-muted); font-size: 0.82rem;">
            Please select <strong>exactly 5 subjects</strong> in your preferred teaching order.
        </p>
    </div>

    <div class="selection-tracker">
        <div id="selectionCounter" class="counter-pill incomplete">
            Selected: <span id="countDisplay">0</span> / 5
        </div>
    </div>
</div>

<!-- Subjects Section -->
<div class="subjects-section-title">
    <h3>Available Academic Curriculum Subjects</h3>
    <span style="font-size: 0.82rem; color: var(--text-muted);">
        Click a subject card to select/deselect
    </span>
</div>

<div class="subjects-grid" id="subjectsGrid">
    <?php foreach ($subjects as $subject): ?>
        <div class="subject-card" 
             id="card-<?= (int)$subject['id'] ?>" 
             data-id="<?= (int)$subject['id'] ?>" 
             data-code="<?= Response::escape($subject['subject_code']) ?>" 
             data-name="<?= Response::escape($subject['subject_name']) ?>">
            <div class="subject-checkbox">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <div class="subject-details">
                <span class="subject-code-tag"><?= Response::escape($subject['subject_code']) ?></span>
                <span class="subject-name-text"><?= Response::escape($subject['subject_name']) ?></span>
            </div>
            <div class="order-badge" id="order-badge-<?= (int)$subject['id'] ?>"></div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Sticky Bottom Action Bar -->
<div class="bottom-submit-bar">
    <div>
        <span style="font-weight: 600; font-size: 0.95rem; color: var(--text-primary);" id="barStatusText">
            Select 5 subjects to enable review
        </span>
    </div>
    <div>
        <button id="btnReviewSelection" class="btn-primary-custom" style="width: auto; padding: 10px 24px;" disabled>
            <span>Review Selection</span>
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        </button>
    </div>
</div>

<!-- Review & Confirmation Modal -->
<div id="reviewModal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Review Your Subject Selection</h3>
            <button type="button" class="modal-close-btn" id="btnModalClose">&times;</button>
        </div>

        <div class="modal-body">
            <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 14px; margin-bottom: 1.25rem;">
                <h4 style="font-size: 0.9rem; font-weight: 700; margin-bottom: 10px; color: var(--bwu-navy);">
                    Faculty Details (Required for Submission) <span style="color: #ef4444;">*</span>
                </h4>
                <div style="margin-bottom: 10px;">
                    <label for="inputFacultyName" style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 3px; color: var(--text-primary);">
                        Faculty Name <span style="color: #ef4444;">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="inputFacultyName" 
                        class="form-control-custom" 
                        value="<?= Response::escape($faculty['name']) ?>" 
                        placeholder="e.g. Dr. Arindam Roy" 
                        required 
                        style="background: #ffffff;"
                    >
                </div>
                <div style="margin-bottom: 8px;">
                    <label for="inputEmployeeCode" style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 3px; color: var(--text-primary);">
                        Employee Code <span style="color: #ef4444;">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="inputEmployeeCode" 
                        class="form-control-custom" 
                        value="<?= Response::escape($faculty['employee_code']) ?>" 
                        placeholder="e.g. BWU/EMP/2026/042" 
                        required 
                        style="background: #ffffff;"
                    >
                </div>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 6px;">
                    Official Email: <strong><?= Response::escape($faculty['email']) ?></strong>
                </p>
            </div>

            <h4 style="font-size: 0.92rem; margin-bottom: 8px; color: var(--bwu-navy);">Selected Subjects (In Order):</h4>
            <ol id="reviewSubjectsList" style="padding-left: 20px; font-size: 0.9rem; line-height: 1.8; margin-bottom: 1.5rem;">
                <!-- Populated by JavaScript -->
            </ol>

            <div class="locked-notice">
                <h4>IMPORTANT NOTICE</h4>
                <p>
                    You can submit your subject selection <strong>only once</strong>. 
                    After submission, you <strong>cannot edit, delete, or resubmit</strong> your selection.
                </p>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn-secondary-custom" id="btnModalBack">Back</button>
            <button type="button" class="btn-primary-custom" id="btnConfirmSubmit" style="width: auto; background: #059669;">
                <span id="btnSubmitText">Confirm & Submit</span>
            </button>
        </div>
    </div>
</div>

<script>
    window.BWU_CSRF_TOKEN = "<?= Auth::csrfToken() ?>";
</script>
<script src="/js/faculty.js"></script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
