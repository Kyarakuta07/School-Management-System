// Admin Dashboard Mobile Menu Toggle
document.addEventListener('DOMContentLoaded', function () {
    // Create hamburger button if it doesn't exist
    if (!document.querySelector('.sidebar-toggle')) {
        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'sidebar-toggle';
        toggleBtn.setAttribute('aria-label', 'Toggle Navigation Menu');
        toggleBtn.innerHTML = '<span></span><span></span><span></span>';
        document.body.insertBefore(toggleBtn, document.body.firstChild);

        const sidebar = document.querySelector('.sidebar');

        // Toggle sidebar on click
        toggleBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            sidebar.classList.toggle('open');
            toggleBtn.classList.toggle('active');
            document.body.classList.toggle('sidebar-open');
        });

        // Close sidebar when clicking overlay
        document.body.addEventListener('click', function (e) {
            if (document.body.classList.contains('sidebar-open') &&
                !sidebar.contains(e.target) &&
                !toggleBtn.contains(e.target)) {
                sidebar.classList.remove('open');
                toggleBtn.classList.remove('active');
                document.body.classList.remove('sidebar-open');
            }
        });

        // Close sidebar when clicking menu link
        const menuLinks = sidebar.querySelectorAll('a');
        menuLinks.forEach(link => {
            link.addEventListener('click', function () {
                if (window.innerWidth <= 767) {
                    sidebar.classList.remove('open');
                    toggleBtn.classList.remove('active');
                    document.body.classList.remove('sidebar-open');
                }
            });
        });

        // Handle window resize
        window.addEventListener('resize', function () {
            if (window.innerWidth > 767) {
                sidebar.classList.remove('open');
                toggleBtn.classList.remove('active');
                document.body.classList.remove('sidebar-open');
            }
        });
    }
});
