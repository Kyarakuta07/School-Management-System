/**
 * Subject Materials & Quiz Management JS
 */

document.addEventListener('DOMContentLoaded', function () {
    // Prefer window.MOE_CONFIG (set by inline PHP script, always fresh)
    // Fallback to data attributes for backward compatibility
    const wrapper = document.querySelector('.main-dashboard-wrapper');
    const dataConfig = wrapper ? wrapper.dataset : {};
    const config = window.MOE_CONFIG || dataConfig;

    const CSRF_NAME = config.csrfName || 'csrf_test_name';
    let _csrfToken = config.csrfToken || '';
    const currentSubject = config.subject || '';
    const API_BASE = config.apiBase || '';
    const BASE_URL = config.baseUrl || '';

    // Fresh CSRF token reader — CI4 regenerates tokens after each POST
    function getCSRF() {
        // Try reading from csrf_cookie_name cookie (always up-to-date)
        const match = document.cookie.match(/csrf_cookie_name=([^;]+)/);
        if (match) return match[1];
        return _csrfToken;
    }

    // 1. MODAL CONTROLS
    window.openAddModal = function () {
        document.getElementById('addModal')?.classList.add('active');
    };
    window.closeAddModal = function () {
        document.getElementById('addModal')?.classList.remove('active');
    };
    window.openQuizModal = function () {
        document.getElementById('quizModal')?.classList.add('active');
    };
    window.closeQuizModal = function () {
        document.getElementById('quizModal')?.classList.remove('active');
    };

    window.toggleContentField = function () {
        const typeSelect = document.getElementById('typeSelect');
        if (!typeSelect) return;
        const type = typeSelect.value;
        document.getElementById('textContentGroup').style.display = type === 'text' ? 'block' : 'none';
        document.getElementById('youtubeContentGroup').style.display = type === 'youtube' ? 'block' : 'none';
        document.getElementById('pdfContentGroup').style.display = type === 'pdf' ? 'block' : 'none';
    };

    // 2. FORM SUBMISSIONS
    const addMaterialForm = document.getElementById('addMaterialForm');
    if (addMaterialForm) {
        addMaterialForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            const type = formData.get('material_type');
            const resultDiv = document.getElementById('formResult');

            if (type === 'pdf') {
                const pdfFile = document.getElementById('pdfFileInput').files[0];
                if (!pdfFile) {
                    resultDiv.innerHTML = '<span style="color:#e74c3c;">✗ Please select a PDF file</span>';
                    return;
                }
                const pdfFormData = new FormData();
                pdfFormData.append(CSRF_NAME, getCSRF());
                pdfFormData.append('subject', currentSubject);
                pdfFormData.append('title', formData.get('title'));
                pdfFormData.append('pdf_file', pdfFile);
                try {
                    const response = await fetch(API_BASE + 'materials/upload', {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': getCSRF() },
                        body: pdfFormData
                    });
                    const result = await response.json();
                    if (result.success) {
                        resultDiv.innerHTML = '<span style="color:#4caf50;">✓ PDF uploaded!</span>';
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        resultDiv.innerHTML = '<span style="color:#e74c3c;">✗ ' + (result.error || 'Failed') + '</span>';
                    }
                } catch (err) {
                    resultDiv.innerHTML = '<span style="color:#e74c3c;">✗ Network error</span>';
                }
                return;
            }

            const content = type === 'text' ? formData.get('text_content') : formData.get('youtube_content');
            try {
                const response = await fetch(API_BASE + 'materials/add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': getCSRF()
                    },
                    body: JSON.stringify({
                        [CSRF_NAME]: getCSRF(),
                        subject: currentSubject,
                        title: formData.get('title'),
                        material_type: type,
                        content: content
                    })
                });
                const result = await response.json();
                if (result.success) {
                    resultDiv.innerHTML = '<span style="color:#4caf50;">✓ Material added!</span>';
                    setTimeout(() => location.reload(), 1000);
                } else {
                    resultDiv.innerHTML = '<span style="color:#e74c3c;">✗ ' + (result.error || 'Failed') + '</span>';
                }
            } catch (err) {
                resultDiv.innerHTML = '<span style="color:#e74c3c;">✗ Network error</span>';
            }
        });
    }

    const createQuizForm = document.getElementById('createQuizForm');
    if (createQuizForm) {
        createQuizForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            const resultDiv = document.getElementById('quizFormResult');
            try {
                const response = await fetch(API_BASE + 'quiz/create', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': getCSRF()
                    },
                    body: JSON.stringify({
                        [CSRF_NAME]: getCSRF(),
                        subject: currentSubject,
                        title: formData.get('quiz_title'),
                        description: formData.get('quiz_description'),
                        time_limit: parseInt(formData.get('time_limit')),
                        passing_score: parseInt(formData.get('passing_score'))
                    })
                });
                const result = await response.json();
                if (result.success) {
                    resultDiv.innerHTML = '<span style="color:#4caf50;">✓ Quiz created! Redirecting...</span>';
                    setTimeout(() => { window.location.href = BASE_URL + 'quiz/manage?id=' + result.quiz_id; }, 1000);
                } else {
                    resultDiv.innerHTML = '<span style="color:#e74c3c;">✗ ' + (result.error || 'Failed') + '</span>';
                }
            } catch (err) {
                resultDiv.innerHTML = '<span style="color:#e74c3c;">✗ Network error</span>';
            }
        });
    }

    // 3. ACTION HANDLERS — Visual confirmation (no confirm() dialog)
    window.confirmDelete = function (btn, id) {
        if (btn.dataset.confirming === 'true') {
            // Second click = do delete
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            deleteMaterial(id);
            return;
        }
        // First click = show confirmation state
        btn.dataset.confirming = 'true';
        btn.innerHTML = '<i class="fas fa-check"></i> Sure?';
        btn.style.color = '#e74c3c';
        btn.style.background = 'rgba(231,76,60,0.2)';
        // Reset after 3 seconds if no second click
        setTimeout(function () {
            btn.dataset.confirming = '';
            btn.innerHTML = '<i class="fas fa-trash"></i>';
            btn.style.color = '';
            btn.style.background = '';
        }, 3000);
    };

    async function deleteMaterial(id) {
        try {
            const response = await fetch(API_BASE + 'materials/delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCSRF()
                },
                body: JSON.stringify({ [CSRF_NAME]: getCSRF(), id_material: id })
            });
            const result = await response.json();
            if (result.success) location.reload();
            else alert(result.error || 'Failed to delete');
        } catch (err) {
            alert('Network error');
        }
    }

    window.updateQuizStatus = async function (quizId, newStatus) {
        if (!confirm(`Change quiz status to "${newStatus}"?`)) return;
        try {
            const response = await fetch(API_BASE + 'quiz/update-status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCSRF()
                },
                body: JSON.stringify({ [CSRF_NAME]: getCSRF(), quiz_id: quizId, status: newStatus })
            });
            const result = await response.json();
            if (result.success) location.reload();
            else alert(result.error || 'Failed to update');
        } catch (err) {
            alert('Network error');
        }
    };

    // 4. PDF VIEWER MODAL
    window.openPdfModal = function (viewUrl, title, downloadUrl) {
        const modal = document.getElementById('pdfViewerModal');
        const frame = document.getElementById('pdfViewerFrame');
        const titleEl = document.getElementById('pdfViewerTitle');
        const dlLink = document.getElementById('pdfDownloadLink');
        if (!modal || !frame) return;
        titleEl.textContent = '📄 ' + title;
        frame.src = viewUrl;
        if (dlLink) dlLink.href = downloadUrl;
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    };
    window.closePdfModal = function () {
        const modal = document.getElementById('pdfViewerModal');
        const frame = document.getElementById('pdfViewerFrame');
        if (modal) modal.classList.remove('active');
        if (frame) frame.src = ''; // stop loading
        document.body.style.overflow = '';
    };

    // 5. OVERLAY CLOSING
    document.getElementById('addModal')?.addEventListener('click', function (e) {
        if (e.target === this) closeAddModal();
    });
    document.getElementById('quizModal')?.addEventListener('click', function (e) {
        if (e.target === this) closeQuizModal();
    });
    document.getElementById('pdfViewerModal')?.addEventListener('click', function (e) {
        if (e.target === this) closePdfModal();
    });
});
