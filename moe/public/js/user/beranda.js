/**
 * Beranda (Dashboard) page JavaScript
 * Extracted from inline <script> in beranda.php
 *
 * Requires globals: API_BASE, ASSET_BASE, CSRF_NAME, CSRF_TOKEN
 * Requires: toast.js (showToast)
 */

// --- FUN FACT MODAL ---
const funfactModal = document.getElementById('funfactModal');
const funfactInput = document.getElementById('funfactInput');
const charCount = document.getElementById('charCount');

function openFunfactModal() {
    funfactModal.classList.add('active');
    updateCharCount();
}

function closeFunfactModal() {
    funfactModal.classList.remove('active');
}

function updateCharCount() {
    charCount.textContent = funfactInput.value.length;
}

funfactInput.addEventListener('input', updateCharCount);

function saveFunfact() {
    const funfact = funfactInput.value.trim();
    const formData = new FormData();
    formData.append('action', 'update_funfact');
    formData.append('fun_fact', funfact);
    formData.append(CSRF_NAME, CSRF_TOKEN);

    fetch(API_BASE + 'profile/update', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('funfactDisplay').textContent = data.fun_fact || funfact || 'Belum ada funfact.';
                closeFunfactModal();
                showToast('Fun fact berhasil diupdate!', 'success');
            } else {
                showToast(data.message || 'Gagal menyimpan', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Terjadi kesalahan', 'error');
        });
}

// --- PHOTO UPLOAD ---
const photoInput = document.getElementById('photoUploadInput');
const avatarPreview = document.getElementById('avatarPreview');

photoInput.addEventListener('change', function () {
    if (this.files && this.files[0]) {
        const file = this.files[0];
        if (file.size > 2 * 1024 * 1024) {
            showToast('Ukuran file terlalu besar (max 2MB)', 'error');
            return;
        }
        avatarPreview.style.opacity = '0.5';
        const formData = new FormData();
        formData.append('action', 'upload_photo');
        formData.append('profile_photo', file);
        formData.append(CSRF_NAME, CSRF_TOKEN);

        fetch(API_BASE + 'profile/update', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                avatarPreview.style.opacity = '1';
                if (data.success) {
                    avatarPreview.src = ASSET_BASE + data.photo_url + '?t=' + Date.now();
                    showToast('Foto profil berhasil diupdate!', 'success');
                } else {
                    showToast(data.message || 'Gagal upload foto', 'error');
                }
            })
            .catch(err => {
                avatarPreview.style.opacity = '1';
                console.error(err);
                showToast('Terjadi kesalahan', 'error');
            });
    }
});

// --- MODAL CLOSE ON OVERLAY ---
funfactModal.addEventListener('click', function (e) {
    if (e.target === this) closeFunfactModal();
});
