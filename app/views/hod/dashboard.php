<?php
$pageTitle = 'HOD Dashboard - Department of Computational Sciences';
require_once __DIR__ . '/../layout/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h2 style="font-size: 1.5rem; color: var(--bwu-navy); margin-bottom: 4px;">HOD Subject Selection Overview</h2>
        <p style="color: var(--text-secondary); font-size: 0.88rem;">
            Department of Computational Sciences &bull; Master Faculty Roster & Submission Tracking
        </p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <button type="button" class="btn-primary-custom" id="btnOpenAddFaculty" style="width: auto; padding: 9px 18px; font-size: 0.88rem; background: var(--bwu-navy);">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            <span>Add Faculty</span>
        </button>
        <a href="/api/hod/export" class="btn-excel-download" id="btnDownloadExcel">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            <span>Download Excel</span>
        </a>
    </div>
</div>

<!-- KPI Cards -->
<div class="kpi-grid">
    <div class="kpi-card kpi-total">
        <div>
            <div class="kpi-label">Total Faculty</div>
            <div class="kpi-value" id="kpiTotal"><?= (int)$counts['total'] ?></div>
        </div>
        <div style="background: #e0f2fe; color: #0284c7; width: 44px; height: 44px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
        </div>
    </div>

    <div class="kpi-card kpi-submitted">
        <div>
            <div class="kpi-label">Submitted</div>
            <div class="kpi-value" id="kpiSubmitted"><?= (int)$counts['submitted'] ?></div>
        </div>
        <div style="background: #d1fae5; color: #059669; width: 44px; height: 44px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
    </div>

    <div class="kpi-card kpi-pending">
        <div>
            <div class="kpi-label">Pending</div>
            <div class="kpi-value" id="kpiPending"><?= (int)$counts['pending'] ?></div>
        </div>
        <div style="background: #fef3c7; color: #d97706; width: 44px; height: 44px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
    </div>
</div>

<!-- Toolbar: Filter & Search -->
<div class="hod-action-toolbar">
    <div class="filter-btn-group">
        <button type="button" class="filter-btn active" data-filter="all">
            All (<span id="filterAllCount"><?= (int)$counts['total'] ?></span>)
        </button>
        <button type="button" class="filter-btn" data-filter="submitted">
            Submitted (<span id="filterSubCount"><?= (int)$counts['submitted'] ?></span>)
        </button>
        <button type="button" class="filter-btn" data-filter="pending">
            Pending (<span id="filterPenCount"><?= (int)$counts['pending'] ?></span>)
        </button>
    </div>

    <div class="search-input-wrapper">
        <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        <input type="text" id="searchInput" placeholder="Search faculty name, email, or code..." autocomplete="off">
    </div>
</div>

<!-- Faculty Submissions Table -->
<div class="table-card">
    <div class="table-responsive-custom">
        <table class="faculty-table">
            <thead>
                <tr>
                    <th style="width: 50px;">Sl. No.</th>
                    <th>Faculty Name</th>
                    <th>Email</th>
                    <th>Subject 1</th>
                    <th>Subject 2</th>
                    <th>Subject 3</th>
                    <th>Subject 4</th>
                    <th>Subject 5</th>
                    <th>Date</th>
                    <th style="text-align: center;">Status</th>
                </tr>
            </thead>
            <tbody id="facultyTableBody">
                <?php if (empty($facultyList)): ?>
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                            No faculty records found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($facultyList as $f): ?>
                        <tr>
                            <td><?= (int)$f['sl_no'] ?></td>
                            <td>
                                <strong><?= Response::escape($f['faculty_name']) ?></strong>
                                <div style="font-size: 0.74rem; color: var(--text-muted);"><?= Response::escape($f['employee_code']) ?></div>
                            </td>
                            <td><?= Response::escape($f['faculty_email']) ?></td>
                            <td><?= Response::escape($f['subject_1']) ?></td>
                            <td><?= Response::escape($f['subject_2']) ?></td>
                            <td><?= Response::escape($f['subject_3']) ?></td>
                            <td><?= Response::escape($f['subject_4']) ?></td>
                            <td><?= Response::escape($f['subject_5']) ?></td>
                            <td style="white-space: nowrap;"><?= Response::escape($f['submission_date']) ?></td>
                            <td style="text-align: center;">
                                <?php if ($f['status'] === 'Submitted'): ?>
                                    <span class="badge-status badge-submitted">Submitted</span>
                                <?php else: ?>
                                    <span class="badge-status badge-pending">Pending</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<!-- Add Faculty Modal -->
<div id="addFacultyModal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Add New Faculty Member</h3>
            <button type="button" class="modal-close-btn" id="btnAddFacultyClose">&times;</button>
        </div>
        <form id="addFacultyForm" autocomplete="off">
            <div class="modal-body">
                <div class="form-group">
                    <label for="newFacultyName">Full Name</label>
                    <input type="text" id="newFacultyName" class="form-control-custom" placeholder="e.g. Dr. Jane Doe" required>
                </div>
                <div class="form-group">
                    <label for="newFacultyEmail">Official Email Address</label>
                    <input type="email" id="newFacultyEmail" class="form-control-custom" placeholder="e.g. jane.cs@brainwareuniversity.ac.in" required>
                </div>
                <div class="form-group">
                    <label for="newFacultyCode">Employee Code</label>
                    <input type="text" id="newFacultyCode" class="form-control-custom" placeholder="e.g. BWU-FAC-011" required>
                </div>
                <div class="form-group">
                    <label for="newFacultyPassword">Initial Password <span style="font-weight: normal; color: var(--text-muted);">(Optional, defaults to Faculty@123)</span></label>
                    <input type="password" id="newFacultyPassword" class="form-control-custom" placeholder="Faculty@123">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary-custom" id="btnAddFacultyCancel">Cancel</button>
                <button type="submit" class="btn-primary-custom" id="btnSaveFaculty" style="width: auto;">
                    <span>Save Faculty</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script src="/js/hod.js"></script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
