/**
 * quiz_manage.js
 * Handles quiz question management: adding and deleting questions via AJAX.
 */

// Config — read from DOM data-attributes on .container
const _mCfg = document.querySelector('.container');
const _md = _mCfg ? _mCfg.dataset : {};
const API_BASE = _md.apiBase || window.API_BASE || '/api/';
const QUIZ_ID = _md.quizId || 0;

// CSRF: CI4 tokenName = csrf_test_name, headerName = X-CSRF-TOKEN
// csrf_meta() outputs <meta name="X-CSRF-TOKEN" content="hash">
const CSRF_NAME = 'csrf_test_name';
const CSRF_TOKEN = (function () {
    // 1. Try meta tag (most reliable, set by csrf_meta())
    const meta = document.querySelector('meta[name="X-CSRF-TOKEN"]');
    if (meta && meta.content) return meta.content;
    // 2. Fallback to data attribute
    return _md.csrfToken || '';
})();

document.addEventListener('DOMContentLoaded', function () {
    const addForm = document.getElementById('addQuestionForm');
    if (addForm) {
        addForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            const resultDiv = document.getElementById('formResult');

            const data = {
                quiz_id: QUIZ_ID,
                question: formData.get('question'),
                option_a: formData.get('option_a'),
                option_b: formData.get('option_b'),
                option_c: formData.get('option_c'),
                option_d: formData.get('option_d'),
                correct_answer: formData.get('correct_answer'),
                points: parseInt(formData.get('points'))
            };
            data[CSRF_NAME] = CSRF_TOKEN;

            try {
                const response = await fetch(API_BASE + 'quiz/add-question', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': CSRF_TOKEN
                    },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (result.success) {
                    resultDiv.innerHTML = '<span style="color:#4caf50;">✓ Question added!</span>';
                    setTimeout(() => location.reload(), 800);
                } else {
                    resultDiv.innerHTML = '<span style="color:#e74c3c;">✗ ' + (result.error || 'Failed') + '</span>';
                }
            } catch (err) {
                resultDiv.innerHTML = '<span style="color:#e74c3c;">✗ Network error</span>';
            }
        });
    }
});

async function deleteQuestion(questionId) {
    if (!confirm('Delete this question?')) return;

    const data = { question_id: questionId };
    data[CSRF_NAME] = CSRF_TOKEN;

    try {
        const response = await fetch(API_BASE + 'quiz/delete-question', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            body: JSON.stringify(data)
        });
        const result = await response.json();

        if (result.success) {
            document.getElementById('question-' + questionId).remove();
        } else {
            alert(result.error || 'Failed to delete');
        }
    } catch (err) {
        alert('Network error');
    }
}
