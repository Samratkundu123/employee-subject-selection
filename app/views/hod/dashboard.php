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
        <button type="button" class="btn-secondary-custom" id="btnRefreshData" title="Refresh live roster and submissions" style="display: inline-flex; align-items: center; gap: 6px; padding: 9px 15px; font-size: 0.88rem; background: #ffffff; border: 1px solid var(--border-color); color: var(--bwu-navy); border-radius: var(--radius-md); font-weight: 600; cursor: pointer; transition: all 0.2s;">
            <svg id="refreshIcon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            <span>Refresh</span>
        </button>
        <button type="button" class="btn-primary-custom" id="btnOpenUploadSubjects" style="width: auto; padding: 9px 16px; font-size: 0.88rem; background: #0284c7; border-color: #0284c7;">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
            <span>Upload Subjects</span>
        </button>
        <button type="button" class="btn-primary-custom" id="btnOpenAddFaculty" style="width: auto; padding: 9px 18px; font-size: 0.88rem; background: var(--bwu-navy);">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            <span>Add Faculty</span>
        </button>
        <button type="button" class="btn-primary-custom" id="btnClearAllFaculty" title="Delete all faculty records and submissions" style="width: auto; padding: 9px 16px; font-size: 0.88rem; background: #dc2626; border-color: #dc2626;">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
            <span>Delete All Faculty</span>
        </button>
        <a href="/api/hod/export" class="btn-excel-download" id="btnDownloadExcel">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            <span>Download Excel</span>
        </a>
        <button type="button" class="btn-primary-custom" id="btnDeleteAllSubmissions" style="width: auto; padding: 9px 16px; font-size: 0.88rem; background: #dc2626; border-color: #dc2626;">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
            <span>Delete All Submissions</span>
        </button>
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

<!-- Upload Subjects Modal -->
<div id="uploadSubjectsModal" class="modal-overlay">
    <div class="modal-card" style="max-width: 560px;">
        <div class="modal-header">
            <h3>Upload Academic Subjects</h3>
            <button type="button" class="modal-close-btn" id="btnUploadSubjectsClose">&times;</button>
        </div>
        <form id="uploadSubjectsForm" enctype="multipart/form-data">
            <div class="modal-body">
                <p style="font-size: 0.88rem; color: var(--text-secondary); margin-bottom: 1.25rem; line-height: 1.5;">
                    Upload an Excel (<code>.xlsx</code>) or CSV (<code>.csv</code>) file with your curriculum subjects. The updated list will immediately be displayed in all faculty profiles for subject selection.
                </p>

                <div class="form-group">
                    <label for="subjectFileInput">Select Excel or CSV File</label>
                    <input 
                        type="file" 
                        id="subjectFileInput" 
                        name="subject_file" 
                        class="form-control-custom" 
                        accept=".xlsx, .csv, .txt, .xls" 
                        required
                        style="padding: 8px 12px;"
                    >
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 6px;">
                        <small style="color: var(--text-muted); font-size: 0.78rem;">Supported formats: .xlsx, .csv</small>
                        <a href="/api/hod/subjects/template" class="text-link" style="font-size: 0.82rem; font-weight: 600; color: var(--bwu-navy); text-decoration: underline;">
                            Download Template (.csv) &darr;
                        </a>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 1rem;">
                    <label style="margin-bottom: 8px;">Import Mode</label>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; font-size: 0.88rem; cursor: pointer;">
                            <input type="radio" name="mode" value="replace" checked style="accent-color: var(--bwu-navy);">
                            <span><strong>Replace Active Catalog</strong> (Replaces active subjects with this new list)</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; font-size: 0.88rem; cursor: pointer;">
                            <input type="radio" name="mode" value="append" style="accent-color: var(--bwu-navy);">
                            <span><strong>Append to Catalog</strong> (Keeps existing subjects and adds new ones)</span>
                        </label>
                    </div>
                </div>

                <div id="uploadStatusMsg" style="display: none; margin-top: 1rem; padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.85rem;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary-custom" id="btnUploadSubjectsCancel">Cancel</button>
                <button type="submit" class="btn-primary-custom" id="btnSubmitUploadSubjects" style="width: auto; background: #0284c7; border-color: #0284c7;">
                    <span id="uploadBtnText">Import & Update Catalog</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script src="/js/hod.js"></script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
