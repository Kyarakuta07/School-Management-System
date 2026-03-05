/**
 * Session Guard — Global session resilience for mobile browsers.
 * 
 * Features:
 * 1. Heartbeat — pings server every 10 min to keep session alive
 * 2. Tab-resume — detects visibilitychange, refreshes session + CSRF token
 * 3. Global 401/403 interceptor — shows re-login prompt on session death
 * 4. Session expiry warning — alerts user before session dies
 *
 * This script should be loaded globally via layout.php / admin_layout.php.
 * Requires: API_BASE global (set by layout.php)
 */
(function () {
    'use strict';

    // ── Config ──────────────────────────────────────
    const HEARTBEAT_INTERVAL = 10 * 60 * 1000; // 10 minutes
    const PING_URL = (typeof API_BASE !== 'undefined' ? API_BASE : '/api/') + 'session/ping';
    const LOGIN_URL = (typeof ASSET_BASE !== 'undefined' ? ASSET_BASE : '/') + 'login';

    let lastActivity = Date.now();
    let lastHidden = null;
    let heartbeatTimer = null;
    let sessionExpired = false;
    let modalShown = false;

    // ── 1. Heartbeat ────────────────────────────────
    function startHeartbeat() {
        stopHeartbeat();
        heartbeatTimer = setInterval(ping, HEARTBEAT_INTERVAL);
    }

    function stopHeartbeat() {
        if (heartbeatTimer) {
            clearInterval(heartbeatTimer);
            heartbeatTimer = null;
        }
    }

    async function ping() {
        if (sessionExpired) return;
        try {
            const res = await fetch(PING_URL, {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (res.status === 401 || res.status === 403) {
                handleSessionDead();
                return;
            }

            if (res.ok) {
                const data = await res.json();
                if (data.success && data.csrf_token) {
                    updateCsrfToken(data.csrf_token);
                }
                lastActivity = Date.now();
            }
        } catch (e) {
            // Network error — don't show modal, let user retry naturally
            console.warn('[SessionGuard] Ping failed (network):', e.message);
        }
    }

    // ── 2. Tab-resume detection ─────────────────────
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            // User left the tab
            lastHidden = Date.now();
            stopHeartbeat();
        } else {
            // User came back
            const awayDuration = lastHidden ? Date.now() - lastHidden : 0;
            lastHidden = null;

            if (sessionExpired) {
                // Already showing modal, don't re-ping
                return;
            }

            // If away for more than 1 minute, verify session
            if (awayDuration > 60 * 1000) {
                ping();
            }

            // Restart heartbeat
            startHeartbeat();
        }
    });

    // ── 3. Global fetch interceptor ─────────────────
    const originalFetch = window.fetch;
    window.fetch = async function (url, options) {
        if (sessionExpired) {
            // Don't make requests if we know session is dead
            return Promise.reject(new Error('Session expired'));
        }

        const response = await originalFetch.call(this, url, options);

        // Check if server says auth is dead
        if (response.status === 401 || response.status === 403) {
            // Only intercept our own API calls, not external
            const urlStr = typeof url === 'string' ? url : url.toString();
            const isOurApi = urlStr.includes('/api/') || urlStr.startsWith(API_BASE || '');

            if (isOurApi) {
                // Try to read the response body to check for auth error codes
                const cloned = response.clone();
                try {
                    const data = await cloned.json();
                    if (data.code === 'AUTH_REQUIRED' || data.code === 'FORBIDDEN') {
                        handleSessionDead();
                    }
                } catch (e) {
                    // Non-JSON response (like a redirect page) — likely session dead
                    handleSessionDead();
                }
            }
        }

        return response;
    };

    // ── 4. CSRF token updater ───────────────────────
    function updateCsrfToken(newToken) {
        // Update meta tags
        const metaNames = ['X-CSRF-TOKEN', 'csrf_test_name', 'csrf-token'];
        metaNames.forEach(function (name) {
            const meta = document.querySelector('meta[name="' + name + '"]');
            if (meta) meta.setAttribute('content', newToken);
        });

        // Update data attributes on elements
        document.querySelectorAll('[data-csrf-token]').forEach(function (el) {
            el.setAttribute('data-csrf-token', newToken);
        });

        // Update hidden form inputs
        document.querySelectorAll('input[name="csrf_test_name"]').forEach(function (input) {
            input.value = newToken;
        });

        // Update global JS variables if they exist
        if (typeof window.CSRF_TOKEN !== 'undefined') {
            window.CSRF_TOKEN = newToken;
        }
        if (typeof window.BATTLE_CONFIG !== 'undefined' && window.BATTLE_CONFIG) {
            window.BATTLE_CONFIG.csrfToken = newToken;
        }

        // Update cookie
        document.cookie = 'csrf_cookie_name=' + newToken + '; path=/; SameSite=Lax';
    }

    // ── 5. Session expired UI ───────────────────────
    function handleSessionDead() {
        if (sessionExpired) return;
        sessionExpired = true;
        stopHeartbeat();
        showSessionExpiredModal();
    }

    function showSessionExpiredModal() {
        if (modalShown) return;
        modalShown = true;

        // Create the modal
        const overlay = document.createElement('div');
        overlay.id = 'session-expired-overlay';
        overlay.style.cssText = [
            'position: fixed',
            'top: 0', 'left: 0', 'right: 0', 'bottom: 0',
            'background: rgba(0, 0, 0, 0.85)',
            'z-index: 99999',
            'display: flex',
            'align-items: center',
            'justify-content: center',
            'padding: 20px',
            'animation: fadeIn 0.3s ease'
        ].join(';');

        overlay.innerHTML =
            '<div style="' +
            'background: linear-gradient(135deg, #1a1200, #2a1f00);' +
            'border: 2px solid #d4af37;' +
            'border-radius: 12px;' +
            'padding: 32px 28px;' +
            'max-width: 380px;' +
            'width: 100%;' +
            'text-align: center;' +
            'box-shadow: 0 0 30px rgba(212, 175, 55, 0.3);' +
            'animation: slideUp 0.3s ease;' +
            '">' +
            '<div style="font-size: 48px; margin-bottom: 16px;">⏳</div>' +
            '<h2 style="' +
            'color: #d4af37;' +
            'font-family: Cinzel, serif;' +
            'font-size: 1.4rem;' +
            'margin: 0 0 12px 0;' +
            '">Sesi Telah Berakhir</h2>' +
            '<p style="' +
            'color: #ccc;' +
            'font-size: 0.95rem;' +
            'line-height: 1.5;' +
            'margin: 0 0 24px 0;' +
            '">Koneksi ke server terputus. Silakan login kembali untuk melanjutkan petualangan.</p>' +
            '<a href="' + LOGIN_URL + '" style="' +
            'display: inline-block;' +
            'padding: 12px 32px;' +
            'background: linear-gradient(135deg, #8b6b23, #d4af37);' +
            'color: #000;' +
            'text-decoration: none;' +
            'font-weight: bold;' +
            'border-radius: 6px;' +
            'font-size: 1rem;' +
            'transition: all 0.3s;' +
            '">Login Kembali</a>' +
            '</div>';

        // Add animation keyframes
        if (!document.getElementById('session-guard-styles')) {
            const style = document.createElement('style');
            style.id = 'session-guard-styles';
            style.textContent =
                '@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }' +
                '@keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }';
            document.head.appendChild(style);
        }

        document.body.appendChild(overlay);
    }

    // ── Boot ────────────────────────────────────────
    // Only start heartbeat on authenticated pages (check if API_BASE is set)
    if (typeof API_BASE !== 'undefined') {
        startHeartbeat();
    }

})();
