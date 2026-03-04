/**
 * Import Beatmap — file upload + API import
 * Requires globals: IMPORT_API_URL, csrfName, csrfHash (injected by view)
 */

const fileInput = document.getElementById('fileInput');
const uploadBtn = document.getElementById('uploadBtn');
const fileName = document.getElementById('fileName');
const statusEl = document.getElementById('status');

/**
 * Read CSRF token from cookie (more reliable than stale PHP-injected value)
 */
function getCsrfFromCookie() {
    const match = document.cookie.match(new RegExp('(?:^|;\\s*)' + csrfName + '=([^;]+)'));
    return match ? decodeURIComponent(match[1]) : csrfHash;
}

/**
 * Extract error message from various CI4 response formats
 */
function extractError(data, httpStatus) {
    if (data.error) return data.error;
    if (data.message) return data.message;
    if (data.messages) {
        const msgs = typeof data.messages === 'object'
            ? Object.values(data.messages).join(', ')
            : data.messages;
        return msgs;
    }
    if (httpStatus === 403) return 'Akses ditolak (403). Pastikan kamu login sebagai admin.';
    if (httpStatus === 401) return 'Sesi habis. Silakan login ulang.';
    return 'Terjadi kesalahan (HTTP ' + httpStatus + ')';
}

fileInput.addEventListener('change', (e) => {
    if (e.target.files.length > 0) {
        const file = e.target.files[0];
        fileName.textContent = file.name;
        uploadBtn.disabled = false;
    }
});

async function uploadFile() {
    const file = fileInput.files[0];
    if (!file) return;

    uploadBtn.disabled = true;
    uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Importing...';
    statusEl.style.display = 'none';

    const formData = new FormData();
    formData.append('osz_file', file);
    formData.append(csrfName, getCsrfFromCookie());

    try {
        const response = await fetch(IMPORT_API_URL, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        let data;
        const contentType = response.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
            data = await response.json();
        } else {
            // Server returned HTML (e.g. CSRF error page)
            throw new Error('Server mengembalikan HTML, bukan JSON. Status: ' + response.status);
        }

        // Update CSRF hash for next request (token regeneration)
        if (data[csrfName]) csrfHash = data[csrfName];

        statusEl.style.display = 'block';
        if (data.success) {
            statusEl.className = 'status success';
            statusEl.innerHTML = `
                <strong>✅ Import Berhasil!</strong><br>
                <strong>Judul:</strong> ${data.title}<br>
                <strong>Artis:</strong> ${data.artist}<br>
                <strong>Jumlah Note:</strong> ${data.note_count}<br>
                <strong>Difficulty:</strong> ${data.difficulty}
            `;
        } else {
            statusEl.className = 'status error';
            statusEl.innerHTML = `<strong>❌ Error:</strong> ${extractError(data, response.status)}`;
        }
    } catch (error) {
        statusEl.style.display = 'block';
        statusEl.className = 'status error';
        statusEl.innerHTML = `<strong>❌ Error:</strong> ${error.message}`;
    }

    uploadBtn.disabled = false;
    uploadBtn.innerHTML = '<i class="fas fa-upload"></i> Upload &amp; Import';
}
