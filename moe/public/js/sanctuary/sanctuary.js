/**
 * sanctuary.js
 * Handles sanctuary interactions: daily reward timer and potentially other AJAX actions.
 */
document.addEventListener('DOMContentLoaded', function () {
    const timerDisplay = document.getElementById('daily-timer');
    if (timerDisplay) {
        let remaining = parseInt(timerDisplay.getAttribute('data-remaining')) || 0;

        const updateTimer = () => {
            if (remaining <= 0) {
                timerDisplay.textContent = "Ready!";
                setTimeout(() => window.location.reload(), 1000);
                return;
            }

            remaining--;
            const hours = Math.floor(remaining / 3600);
            const minutes = Math.floor((remaining % 3600) / 60);
            const seconds = remaining % 60;

            timerDisplay.textContent =
                String(hours).padStart(2, '0') + ':' +
                String(minutes).padStart(2, '0') + ':' +
                String(seconds).padStart(2, '0');
        };

        setInterval(updateTimer, 1000);
    }
});
