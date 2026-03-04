/**
 * Class Page JavaScript
 * Handles grade management and top scholars filtering.
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. TOP SCHOLARS FILTERING
    const scholarsList = document.getElementById('scholars-list');
    const originalScholarsHTML = scholarsList?.innerHTML || '';

    window.filterScholars = function (sanctuaryId) {
        if (!scholarsList) return;

        if (!sanctuaryId) {
            scholarsList.innerHTML = originalScholarsHTML;
            const filterMsg = scholarsList.querySelector('.filter-msg');
            if (filterMsg) filterMsg.remove();
            return;
        }

        const rows = scholarsList.querySelectorAll('.scholar-row');
        let visibleCount = 0;

        rows.forEach(row => {
            if (row.getAttribute('data-sanctuary') === sanctuaryId) {
                row.style.display = 'flex';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (visibleCount === 0) {
            const existingMsg = scholarsList.querySelector('.no-scholars');
            if (!existingMsg) {
                const msg = document.createElement('div');
                msg.className = 'no-scholars filter-msg';
                msg.innerHTML = '<i class="fa-solid fa-filter"></i><p>No scholars from this sanctuary.</p>';
                scholarsList.appendChild(msg);
            }
        } else {
            const filterMsg = scholarsList.querySelector('.filter-msg');
            if (filterMsg) filterMsg.remove();
        }
    };

    // 2. GRADE MANAGEMENT (HAKAES/VASIKI)
    const studentSelect = document.getElementById('student-select');
    if (studentSelect) {
        const gradeForm = document.getElementById('grade-form');
        const saveBtn = document.getElementById('save-grades-btn');
        const resultDiv = document.getElementById('grade-result');
        const gradeInputs = document.querySelectorAll('.grade-input');

        function updateTotalPP() {
            let total = 0;
            gradeInputs.forEach(input => { total += parseInt(input.value) || 0; });
            const previewEl = document.getElementById('total-pp-preview');
            if (previewEl) previewEl.textContent = total;
        }

        gradeInputs.forEach(input => { input.addEventListener('input', updateTotalPP); });

        studentSelect.addEventListener('change', async function () {
            const studentId = this.value;
            if (!studentId) {
                if (gradeForm) gradeForm.style.display = 'none';
                return;
            }

            const selected = this.options[this.selectedIndex];
            const nameEl = document.getElementById('selected-student-name');
            const sancEl = document.getElementById('selected-student-sanctuary');

            if (nameEl) nameEl.textContent = selected.dataset.name;
            if (sancEl) sancEl.textContent = selected.dataset.sanctuary;

            try {
                const res = await fetch(API_BASE + 'class/grades?student_id=' + studentId);
                const data = await res.json();
                if (data.success && data.data?.grades) {
                    const g = data.data.grades;
                    ['pop_culture', 'mythology', 'history_of_egypt', 'oceanology', 'astronomy'].forEach(k => {
                        const el = document.getElementById('grade-' + k);
                        if (el) el.value = g[k] || 0;
                    });
                } else {
                    gradeInputs.forEach(input => input.value = 0);
                }
                updateTotalPP();
            } catch (err) {
                console.error('Failed to fetch grades:', err);
                gradeInputs.forEach(input => input.value = 0);
            }

            if (gradeForm) gradeForm.style.display = 'block';
            if (resultDiv) resultDiv.style.display = 'none';
        });

        if (saveBtn) {
            saveBtn.addEventListener('click', async function () {
                const studentId = studentSelect.value;
                if (!studentId) return;

                saveBtn.disabled = true;
                saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

                const grades = {};
                ['pop_culture', 'mythology', 'history_of_egypt', 'oceanology', 'astronomy'].forEach(k => {
                    const el = document.getElementById('grade-' + k);
                    if (el) grades[k] = parseInt(el.value) || 0;
                });

                try {
                    const res = await fetchWithCsrf(API_BASE + 'class/grades', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ student_id: studentId, grades: grades })
                    });
                    const data = await res.json();
                    if (resultDiv) {
                        resultDiv.style.display = 'block';
                        resultDiv.className = 'grade-result ' + (data.success ? 'success' : 'error');
                        resultDiv.textContent = data.message || (data.success ? 'Grades saved!' : 'Failed to save');
                    }
                } catch (err) {
                    if (resultDiv) {
                        resultDiv.style.display = 'block';
                        resultDiv.className = 'grade-result error';
                        resultDiv.textContent = 'Network error. Please try again.';
                    }
                }

                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fa-solid fa-save"></i> Save Grades';
            });
        }
    }

    // 3. GRADES TABLE SEARCH
    const gradeSearch = document.getElementById('grade-search');
    const gradeTableBody = document.querySelector('.grades-table tbody');

    if (gradeSearch && gradeTableBody) {
        gradeSearch.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            const rows = gradeTableBody.querySelectorAll('tr');
            let visibleCount = 0;

            rows.forEach(row => {
                const nameText = (row.querySelector('td[data-label="Nama"]')?.textContent || '').toLowerCase();
                const sancText = (row.querySelector('td[data-label="Sanctuary"]')?.textContent || '').toLowerCase();

                if (nameText.includes(query) || sancText.includes(query)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Update visible rows count
            const countDisplay = document.querySelector('.grades-count');
            if (countDisplay) {
                countDisplay.innerHTML = `${visibleCount} siswa ${query ? '(filtered)' : ''}`;
            }
        });
    }
});
