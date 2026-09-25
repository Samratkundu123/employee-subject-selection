// Brainware University Employee Subject Selection System
// Faculty Subject Selection Logic & Integrity Enforcement

document.addEventListener('DOMContentLoaded', () => {
    const MAX_SELECTION = 5;
    let selectedSubjects = []; // Stores objects: { id, code, name } in exact order of click

    const cards = document.querySelectorAll('.subject-card');
    const counterDisplay = document.getElementById('countDisplay');
    const counterPill = document.getElementById('selectionCounter');
    const btnReview = document.getElementById('btnReviewSelection');
    const barStatusText = document.getElementById('barStatusText');
    const modal = document.getElementById('reviewModal');
    const modalClose = document.getElementById('btnModalClose');
    const modalBack = document.getElementById('btnModalBack');
    const reviewList = document.getElementById('reviewSubjectsList');
    const btnConfirm = document.getElementById('btnConfirmSubmit');
    const btnSubmitText = document.getElementById('btnSubmitText');

    if (!cards.length) return;

    cards.forEach(card => {
        card.addEventListener('click', () => {
            const id = parseInt(card.getAttribute('data-id'), 10);
            const code = card.getAttribute('data-code');
            const name = card.getAttribute('data-name');

            const existingIndex = selectedSubjects.findIndex(s => s.id === id);

            if (existingIndex !== -1) {
                // Deselect
                selectedSubjects.splice(existingIndex, 1);
            } else {
                // Check if maximum 5 is already reached
                if (selectedSubjects.length >= MAX_SELECTION) {
                    showToast('You can select a maximum of 5 subjects.', 'warning');
                    return;
                }
                // Append in exact order
                selectedSubjects.push({ id, code, name });
            }

            updateUI();
        });
    });

    function updateUI() {
        const count = selectedSubjects.length;

        // 1. Update Counter
        if (counterDisplay) counterDisplay.textContent = count;
        if (counterPill) {
            if (count === MAX_SELECTION) {
                counterPill.className = 'counter-pill complete';
            } else {
                counterPill.className = 'counter-pill incomplete';
            }
        }

        // 2. Update Bottom Bar text and Review Button
        if (barStatusText) {
            if (count === 0) {
                barStatusText.textContent = 'Please select 5 subjects to proceed';
            } else if (count < MAX_SELECTION) {
                barStatusText.textContent = `${count} of ${MAX_SELECTION} selected (select ${MAX_SELECTION - count} more)`;
            } else {
                barStatusText.innerHTML = '<span style="color: #059669; font-weight: 700;">✓ Exactly 5 subjects selected! Ready to review.</span>';
            }
        }

        if (btnReview) {
            btnReview.disabled = (count !== MAX_SELECTION);
        }

        // 3. Update Cards and Order Badges
        cards.forEach(card => {
            const cardId = parseInt(card.getAttribute('data-id'), 10);
            const index = selectedSubjects.findIndex(s => s.id === cardId);
            const badge = document.getElementById(`order-badge-${cardId}`);

            if (index !== -1) {
                card.classList.add('selected');
                if (badge) {
                    badge.textContent = index + 1;
                    badge.style.display = 'flex';
                }
            } else {
                card.classList.remove('selected');
                if (badge) {
                    badge.style.display = 'none';
                    badge.textContent = '';
                }
            }
        });
    }

    // Modal Handling
    if (btnReview) {
        btnReview.addEventListener('click', () => {
            if (selectedSubjects.length !== MAX_SELECTION) {
                showToast('Please select exactly 5 subjects.', 'warning');
                return;
            }

            // Populate Review List with exact selection order
            reviewList.innerHTML = '';
            selectedSubjects.forEach((sub, idx) => {
                const li = document.createElement('li');
                li.innerHTML = `<strong>${escapeHtml(sub.name)}</strong> <span style="color: var(--text-muted); font-size: 0.8rem;">(${escapeHtml(sub.code)})</span>`;
                reviewList.appendChild(li);
            });

            modal.classList.add('active');
        });
    }

    const closeModal = () => {
        if (modal) modal.classList.remove('active');
    };

    if (modalClose) modalClose.addEventListener('click', closeModal);
    if (modalBack) modalBack.addEventListener('click', closeModal);

    // Submission Confirmation
    if (btnConfirm) {
        btnConfirm.addEventListener('click', async () => {
            if (selectedSubjects.length !== MAX_SELECTION) {
                showToast('Exactly 5 subjects must be selected.', 'warning');
                closeModal();
                return;
            }

            const inputNameEl = document.getElementById('inputFacultyName');
            const inputCodeEl = document.getElementById('inputEmployeeCode');

            const facultyName = inputNameEl ? inputNameEl.value.trim() : '';
            const employeeCode = inputCodeEl ? inputCodeEl.value.trim() : '';

            if (!facultyName) {
                showToast('Please enter your Faculty Name before submitting.', 'warning');
                if (inputNameEl) inputNameEl.focus();
                return;
            }

            if (!employeeCode) {
                showToast('Please enter your Employee Code before submitting.', 'warning');
                if (inputCodeEl) inputCodeEl.focus();
                return;
            }

            btnConfirm.disabled = true;
            btnSubmitText.textContent = 'Submitting & Locking...';

            const payload = {
                csrf_token: window.BWU_CSRF_TOKEN || '',
                faculty_name: facultyName,
                employee_code: employeeCode,
                subject_ids: selectedSubjects.map(s => s.id)
            };

            try {
                const response = await fetch('/api/faculty/submit', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    showToast('Subject selection submitted successfully!', 'success');
                    setTimeout(() => {
                        window.location.href = '/dashboard';
                    }, 500);
                } else {
                    const errorMsg = data.message || data.error || 'Submission failed. Please check your selections.';
                    showToast(errorMsg, 'error');
                    btnConfirm.disabled = false;
                    btnSubmitText.textContent = 'Confirm & Submit';
                    if (response.status === 409) {
                        setTimeout(() => {
                            window.location.href = '/dashboard';
                        }, 2000);
                    }
                }
            } catch (err) {
                showToast('Network error while submitting. Please try again.', 'error');
                btnConfirm.disabled = false;
                btnSubmitText.textContent = 'Confirm & Submit';
            }
        });
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
});
