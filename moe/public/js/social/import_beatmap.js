/**
 * Import Beatmap — file upload + API import
 * Requires global: IMPORT_API_URL (injected by view)
 */

const fileInput = document.getElementById('fileInput');
const uploadBtn = document.getElementById('uploadBtn');
const fileName = document.getElementById('fileName');
const status = document.getElementById('status');

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
    status.style.display = 'none';

    const formData = new FormData();
    formData.append('osz_file', file);

    try {
        const response = await fetch(IMPORT_API_URL, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        status.style.display = 'block';
        if (data.success) {
            status.className = 'status success';
            status.innerHTML = `
                <strong>✅ Import Berhasil!</strong><br>
                <strong>Judul:</strong> ${data.title}<br>
                <strong>Artis:</strong> ${data.artist}<br>
                <strong>Jumlah Note:</strong> ${data.note_count}<br>
                <strong>Difficulty:</strong> ${data.difficulty}
            `;
        } else {
            status.className = 'status error';
            status.innerHTML = `<strong>❌ Error:</strong> ${data.error}`;
        }
    } catch (error) {
        status.style.display = 'block';
        status.className = 'status error';
        status.innerHTML = `<strong>❌ Error:</strong> ${error.message}`;
    }

    uploadBtn.disabled = false;
    uploadBtn.innerHTML = '<i class="fas fa-upload"></i> Upload & Import';
}
