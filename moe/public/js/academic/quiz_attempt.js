/**
 * quiz_attempt.js
 * Handles quiz attempt logic: timer, option selection, progress, and submission.
 */

// ================================================
// CONFIG LOADER (reads from DOM data-attributes)
// ================================================
const _qCfg = document.getElementById('quiz-config');
const _qd = _qCfg ? _qCfg.dataset : {};
const quizId = parseInt(_qd.quizId) || 0;
const totalQuestions = parseInt(_qd.totalQuestions) || 0;
const API_BASE = _qd.apiBase || window.API_BASE || '/api/';

// CSRF: CI4 tokenName = csrf_test_name, headerName = X-CSRF-TOKEN
const CSRF_NAME = 'csrf_test_name';
const csrfToken = (function () {
    const meta = document.querySelector('meta[name="X-CSRF-TOKEN"]');
    if (meta && meta.content) return meta.content;
    return _qd.csrfToken || '';
})();

let timeRemaining;
let answers = {};
let submitted = false;
let timerInterval;

// Auto-init on DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
    const timeLimit = parseInt(_qd.timeLimit) || 600;
    initQuiz({ timeLimit });
});

function initQuiz(config) {
    timeRemaining = config.timeLimit;

    timerInterval = setInterval(() => {
        if (submitted) {
            clearInterval(timerInterval);
            return;
        }

        timeRemaining--;
        const mins = Math.floor(timeRemaining / 60);
        const secs = timeRemaining % 60;

        const timerEl = document.getElementById('timer');
        if (timerEl) {
            timerEl.textContent = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }

        const timerBox = document.getElementById('timerBox');
        if (timeRemaining <= 120 && timerBox) {
            timerBox.classList.add('warning');
        }

        if (timeRemaining <= 0) {
            clearInterval(timerInterval);
            submitQuiz();
        }
    }, 1000);

    // Global listeners or direct calls can still work for the legacy onclicks
}

function selectOption(el, questionId, answer) {
    if (submitted) return;

    const card = el.closest('.question-card');
    card.querySelectorAll('.option-item').forEach(opt => opt.classList.remove('selected'));
    el.classList.add('selected');

    const input = el.querySelector('input');
    if (input) input.checked = true;

    answers[questionId] = answer;
    updateProgress();
}

function updateProgress() {
    const count = Object.keys(answers).length;
    const answeredCountEl = document.getElementById('answeredCount');
    if (answeredCountEl) answeredCountEl.textContent = count;

    const progressFill = document.getElementById('progressFill');
    if (progressFill) {
        progressFill.style.width = (count / totalQuestions * 100) + '%';
    }
}

async function submitQuiz() {
    if (submitted) return;

    const unanswered = totalQuestions - Object.keys(answers).length;
    if (unanswered > 0 && timeRemaining > 0) {
        if (!confirm(`You have ${unanswered} unanswered question(s). Submit anyway?`)) return;
    }

    submitted = true;
    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    }

    try {
        const data = {
            quiz_id: quizId,
            answers: answers
        };
        data[CSRF_NAME] = csrfToken;

        const response = await fetch(API_BASE + 'quiz/submit', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(data)
        });
        const result = await response.json();

        if (result.success) {
            showResult(result);
        } else {
            alert(result.error || 'Failed');
            submitted = false;
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Quiz';
            }
        }
    } catch (err) {
        alert('Network error');
        submitted = false;
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Quiz';
        }
    }
}

function showResult(result) {
    const scoreEl = document.getElementById('resultScore');
    const msgEl = document.getElementById('resultMessage');
    const detailsEl = document.getElementById('resultDetails');
    const iconEl = document.getElementById('resultIcon');
    const modal = document.getElementById('resultModal');

    // BaseApiController::success() merges data flat into root (no 'data' wrapper)
    const score = result.score || 0;
    const correct = result.correct || 0;
    const total = result.total || 0;
    const goldEarned = result.gold_earned || 0;
    const passed = score >= 70;

    if (scoreEl) scoreEl.textContent = score + '%';
    if (msgEl) msgEl.textContent = result.message || (passed ? 'Great job!' : 'Keep trying!');
    if (detailsEl) {
        let html = `Correct: ${correct}/${total}`;
        if (goldEarned > 0) html += `<br>🎉 You earned ${goldEarned} Gold!`;
        detailsEl.innerHTML = html;
    }

    if (iconEl) {
        iconEl.className = 'result-icon ' + (passed ? 'passed' : 'failed');
        iconEl.innerHTML = passed ? '<i class="fas fa-trophy"></i>' : '<i class="fas fa-times"></i>';
    }

    if (modal) modal.classList.add('active');
}

window.onbeforeunload = function () {
    if (!submitted && Object.keys(answers).length > 0) {
        return 'You have unsaved answers.';
    }
};
