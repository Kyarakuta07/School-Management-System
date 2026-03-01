/**
 * CSRF Helper for CodeIgniter 4
 * Automatically adds CSRF token to fetch() requests
 */

/**
 * Get CSRF token from cookie
 * @returns {string} CSRF token
 */
function getCsrfToken() {
    // 1. Primary: Try to get from injected global variables (Most reliable for battles)
    if (typeof BATTLE_CONFIG !== 'undefined' && BATTLE_CONFIG.csrfToken) {
        return BATTLE_CONFIG.csrfToken;
    }
    if (typeof CSRF_TOKEN !== 'undefined') {
        return CSRF_TOKEN;
    }

    // 2. Secondary: Try to get from cookie
    const cookieName = 'csrf_cookie_name=';
    const cookies = document.cookie.split(';');

    for (let cookie of cookies) {
        cookie = cookie.trim();
        if (cookie.indexOf(cookieName) === 0) {
            return cookie.substring(cookieName.length);
        }
    }

    // 3. Fallback: Try to get from meta tag
    const metaParams = [
        'csrf_test_name',
        'X-CSRF-TOKEN',
        'csrf-token'
    ];

    for (const name of metaParams) {
        const meta = document.querySelector(`meta[name="${name}"]`);
        if (meta && meta.content) {
            return meta.content;
        }
    }

    return '';
}

/**
 * Fetch wrapper with automatic CSRF token injection
 */
async function fetchWithCsrf(url, options = {}) {
    const token = getCsrfToken();

    // Only add CSRF for POST/PUT/DELETE methods
    if (options.method && ['POST', 'PUT', 'DELETE'].includes(options.method.toUpperCase())) {
        options.headers = options.headers || {};

        // Use the specific header name if available, otherwise fallback to default
        let headerName = 'X-CSRF-TOKEN';
        if (typeof BATTLE_CONFIG !== 'undefined' && BATTLE_CONFIG.csrfHeader) {
            headerName = BATTLE_CONFIG.csrfHeader;
        } else if (typeof CSRF_HEADER !== 'undefined') {
            headerName = CSRF_HEADER;
        }

        options.headers[headerName] = token;
    }

    return fetch(url, options);
}

// Export to window for global access
window.fetchWithCsrf = fetchWithCsrf;
window.getCsrfToken = getCsrfToken;


