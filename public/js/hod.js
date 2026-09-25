// Brainware University Employee Subject Selection System
// HOD Dashboard Search, Filter & Live Refresh Logic

document.addEventListener('DOMContentLoaded', () => {
    let currentFilter = 'all';
    let searchTimeout = null;

    const filterButtons = document.querySelectorAll('.filter-btn');
    const searchInput = document.getElementById('searchInput');
    const tableBody = document.getElementById('facultyTableBody');

    // Filter Buttons Click
    filterButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            filterButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentFilter = btn.getAttribute('data-filter') || 'all';
            fetchFacultyData();
        });
    });

    // Search Input with 300ms Debounce
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                fetchFacultyData();
            }, 300);
        });
    }

    async function fetchFacultyData() {
        const query = (searchInput ? searchInput.value : '').trim();
        const url = `/api/hod/faculty?filter=${encodeURIComponent(currentFilter)}&search=${encodeURIComponent(query)}`;

        try {
            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!res.ok) {
                if (res.status === 401) {
                    window.location.href = '/hod/login';
                    return;
                }
                throw new Error('Failed to fetch faculty list');
            }

            const data = await res.json();
            if (data.success) {
                renderTable(data.faculty);
                if (data.counts) {
                    updateKpis(data.counts);
                }
            }
        } catch (err) {
            console.error('Error loading data:', err);
        }
    }

    function renderTable(faculty) {
        if (!tableBody) return;
        tableBody.innerHTML = '';

        if (!faculty || faculty.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="10" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                        No faculty records found matching current criteria.
                    </td>
                </tr>
            `;
            return;
        }

        faculty.forEach(f => {
            const tr = document.createElement('tr');

            const isSub = (f.status === 'Submitted');
            const badgeClass = isSub ? 'badge-submitted' : 'badge-pending';
            const statusLabel = isSub ? 'Submitted' : 'Pending';

            tr.innerHTML = `
                <td>${f.sl_no}</td>
                <td>
                    <strong>${escapeHtml(f.faculty_name)}</strong>
                    <div style="font-size: 0.74rem; color: var(--text-muted);">${escapeHtml(f.employee_code)}</div>
                </td>
                <td>${escapeHtml(f.faculty_email)}</td>
                <td>${escapeHtml(f.subject_1)}</td>
                <td>${escapeHtml(f.subject_2)}</td>
                <td>${escapeHtml(f.subject_3)}</td>
                <td>${escapeHtml(f.subject_4)}</td>
                <td>${escapeHtml(f.subject_5)}</td>
                <td style="white-space: nowrap;">${escapeHtml(f.submission_date)}</td>
                <td style="text-align: center;">
                    <span class="badge-status ${badgeClass}">${statusLabel}</span>
                </td>
            `;

            tableBody.appendChild(tr);
        });
    }

    function updateKpis(counts) {
        const kTotal = document.getElementById('kpiTotal');
        const kSub = document.getElementById('kpiSubmitted');
        const kPen = document.getElementById('kpiPending');
        const fAll = document.getElementById('filterAllCount');
        const fSub = document.getElementById('filterSubCount');
        const fPen = document.getElementById('filterPenCount');

        if (kTotal) kTotal.textContent = counts.total;
        if (kSub) kSub.textContent = counts.submitted;
        if (kPen) kPen.textContent = counts.pending;
        if (fAll) fAll.textContent = counts.total;
        if (fSub) fSub.textContent = counts.submitted;
        if (fPen) fPen.textContent = counts.pending;
    }

    // Add Faculty Modal Handling
    const addFacultyModal = document.getElementById('addFacultyModal');
    const btnOpenAddFaculty = document.getElementById('btnOpenAddFaculty');
    const btnAddFacultyClose = document.getElementById('btnAddFacultyClose');
    const btnAddFacultyCancel = document.getElementById('btnAddFacultyCancel');
    const addFacultyForm = document.getElementById('addFacultyForm');
    const btnSaveFaculty = document.getElementById('btnSaveFaculty');

    if (btnOpenAddFaculty && addFacultyModal) {
        btnOpenAddFaculty.addEventListener('click', () => {
            addFacultyForm.reset();
            addFacultyModal.classList.add('active');
            document.getElementById('newFacultyName').focus();
        });

        const closeAddModal = () => {
            addFacultyModal.classList.remove('active');
        };

        if (btnAddFacultyClose) btnAddFacultyClose.addEventListener('click', closeAddModal);
        if (btnAddFacultyCancel) btnAddFacultyCancel.addEventListener('click', closeAddModal);

        if (addFacultyForm) {
            addFacultyForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                const name = document.getElementById('newFacultyName').value.trim();
                const email = document.getElementById('newFacultyEmail').value.trim();
                const code = document.getElementById('newFacultyCode').value.trim();
                const password = document.getElementById('newFacultyPassword').value;

                if (!name || !email || !code) {
                    showToast('Please fill in Name, Email, and Employee Code.', 'warning');
                    return;
                }

                btnSaveFaculty.disabled = true;

                try {
                    const res = await fetch('/api/hod/faculty/add', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            name,
                            email,
                            employee_code: code,
                            password: password || 'Faculty@123'
                        })
                    });

                    const data = await res.json();

                    if (res.ok && data.success) {
                        showToast('Faculty member added successfully!', 'success');
                        closeAddModal();
                        fetchFacultyData();
                    } else {
                        showToast(data.message || 'Failed to add faculty member.', 'error');
                    }
                } catch (err) {
                    showToast('Network error while adding faculty.', 'error');
                } finally {
                    btnSaveFaculty.disabled = false;
                }
            });
        }
    }

    function escapeHtml(str) {
        if (!str) return '-';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
});
