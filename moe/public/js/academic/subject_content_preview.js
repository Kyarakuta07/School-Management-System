/**
 * Subject Content Preview JS
 */

document.addEventListener('DOMContentLoaded', function () {
    window.openContentModal = function (btn) {
        const wrapper = btn.closest('.text-content-wrapper');
        const content = wrapper.querySelector('.text-content');
        const modalTitle = document.getElementById('contentModalTitle');
        const modalBody = document.getElementById('contentModalBody');
        const modal = document.getElementById('contentModal');

        if (modalTitle) modalTitle.textContent = wrapper.dataset.title || 'Material';
        if (modalBody) modalBody.textContent = content.innerHTML; // Note: Use innerHTML if it contains formatting
        // Wait, the previous code used .innerHTML. Let's stick with that if it's safe.
        if (modalBody) modalBody.innerHTML = content.innerHTML;

        if (modal) modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    window.closeContentModal = function () {
        const modal = document.getElementById('contentModal');
        if (modal) modal.classList.remove('active');
        document.body.style.overflow = '';
    };

    const contentModal = document.getElementById('contentModal');
    if (contentModal) {
        contentModal.addEventListener('click', function (e) {
            if (e.target === this) closeContentModal();
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeContentModal();
    });

    // Check for overflow on content for "Read More" visibility
    function checkContentOverflow() {
        document.querySelectorAll('.text-content-wrapper').forEach(wrapper => {
            const content = wrapper.querySelector('.text-content');
            if (content && content.scrollHeight > content.clientHeight) {
                wrapper.classList.add('has-overflow');
            }
        });
    }

    checkContentOverflow();
    // Re-check on resize
    window.addEventListener('resize', checkContentOverflow);
});
