<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import osu! Beatmap - MOE Rhythm Game</title>
    <link href="https://fonts.googleapis.com/css2?family=Titillium+Web:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Titillium Web', sans-serif;
            background: linear-gradient(135deg, #0a0a1a 0%, #1a1a2e 50%, #16213e 100%);
            min-height: 100vh;
            color: #e2e8f0;
            padding: 40px 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #63b3ed;
            text-shadow: 0 0 20px rgba(99, 179, 237, 0.5);
        }

        .upload-box {
            background: rgba(26, 32, 44, 0.9);
            border: 2px dashed #4a5568;
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .upload-box:hover {
            border-color: #63b3ed;
            background: rgba(99, 179, 237, 0.1);
        }

        .upload-box i {
            font-size: 4rem;
            color: #9f7aea;
            margin-bottom: 20px;
        }

        .upload-box p {
            font-size: 1.1rem;
            color: #a0aec0;
        }

        .upload-box input {
            display: none;
        }

        .btn {
            display: inline-block;
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: transform 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .status {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            display: none;
        }

        .status.success {
            background: rgba(72, 187, 120, 0.2);
            border: 1px solid #48bb78;
            color: #68d391;
        }

        .status.error {
            background: rgba(245, 101, 101, 0.2);
            border: 1px solid #f56565;
            color: #fc8181;
        }

        .file-name {
            margin-top: 15px;
            color: #b794f6;
            font-weight: 600;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 30px;
            color: #63b3ed;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        /* ── Song Manager ── */
        .song-manager {
            margin-top: 40px;
        }

        .song-manager h2 {
            color: #DAA520;
            margin-bottom: 16px;
            font-size: 1.2rem;
        }

        .song-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .song-item {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            padding: 12px 14px;
            transition: all 0.2s;
        }

        .song-item:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.15);
        }

        .song-item-info {
            flex: 1;
            min-width: 0;
        }

        .song-item-title {
            font-weight: 700;
            font-size: 0.95rem;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .song-item-meta {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.4);
            margin-top: 2px;
        }

        .song-item-id {
            font-size: 0.65rem;
            color: rgba(255, 255, 255, 0.2);
            flex-shrink: 0;
        }

        .btn-delete {
            padding: 6px 14px;
            background: rgba(245, 101, 101, 0.15);
            border: 1px solid rgba(245, 101, 101, 0.3);
            border-radius: 8px;
            color: #fc8181;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            flex-shrink: 0;
            font-family: inherit;
        }

        .btn-delete:hover {
            background: rgba(245, 101, 101, 0.3);
            border-color: #f56565;
        }

        .btn-delete:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .song-list-empty {
            text-align: center;
            color: rgba(255, 255, 255, 0.3);
            padding: 24px;
        }

        .song-list-loading {
            text-align: center;
            color: rgba(255, 255, 255, 0.3);
            padding: 24px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1><i class="fas fa-file-import"></i> Import osu! Beatmap</h1>

        <div class="upload-box" onclick="document.getElementById('fileInput').click()">
            <i class="fas fa-cloud-upload-alt"></i>
            <p>Klik atau drag file <strong>.osz</strong> atau <strong>.osu</strong> ke sini</p>
            <input type="file" id="fileInput" accept=".osz,.osu">
            <div class="file-name" id="fileName"></div>
        </div>

        <div style="text-align: center;">
            <button class="btn" id="uploadBtn" disabled onclick="uploadFile()">
                <i class="fas fa-upload"></i> Upload & Import
            </button>
        </div>

        <div class="status" id="status"></div>

        <!-- Song Manager -->
        <div class="song-manager">
            <h2><i class="fas fa-music"></i> Manage Songs</h2>
            <div class="song-list" id="songList">
                <div class="song-list-loading"><i class="fas fa-spinner fa-spin"></i> Loading songs...</div>
            </div>
        </div>

        <a href="<?= base_url('rhythm-game') ?>" class="back-link">
            <i class="fas fa-arrow-left"></i> Kembali ke Game
        </a>
    </div>

    <script>
        const IMPORT_API_URL = '<?= base_url('api/rhythm/import') ?>';
        const SONGS_API_URL = '<?= base_url('api/rhythm/songs') ?>';
        const DELETE_API_URL = '<?= base_url('api/rhythm/delete-song') ?>';
        const csrfName = '<?= csrf_token() ?>';
        let csrfHash = '<?= csrf_hash() ?>';
    </script>
    <script src="<?= base_url('js/social/import_beatmap.js') ?>"></script>
    <script>
        // ── Song List Management ──
        async function loadSongList() {
            const container = document.getElementById('songList');
            try {
                const res = await fetch(SONGS_API_URL);
                const data = await res.json();

                if (!data.success || !data.songs || data.songs.length === 0) {
                    container.innerHTML = '<div class="song-list-empty">No songs uploaded yet.</div>';
                    return;
                }

                container.innerHTML = data.songs.map(song => `
                    <div class="song-item" id="song-${song.id}">
                        <div class="song-item-info">
                            <div class="song-item-title">${escHtml(song.title)}</div>
                            <div class="song-item-meta">${escHtml(song.artist)} · ${song.bpm} BPM · ${song.difficulty}</div>
                        </div>
                        <span class="song-item-id">#${song.id}</span>
                        <button class="btn-delete" onclick="deleteSong(${song.id}, '${escHtml(song.title)}')" title="Delete Song">
                            <i class="fas fa-trash-alt"></i> Delete
                        </button>
                    </div>
                `).join('');
            } catch (e) {
                container.innerHTML = '<div class="song-list-empty">Failed to load songs.</div>';
            }
        }

        async function deleteSong(songId, title) {
            if (!confirm(`Delete "${title}"?\n\nThis will also delete all scores and the audio file. This cannot be undone.`)) {
                return;
            }

            const btn = document.querySelector(`#song-${songId} .btn-delete`);
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            }

            try {
                const fetchFn = typeof fetchWithCsrf === 'function' ? fetchWithCsrf : fetch;
                const res = await fetchFn(DELETE_API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ song_id: songId })
                });
                const data = await res.json();

                if (data.success) {
                    const el = document.getElementById(`song-${songId}`);
                    if (el) {
                        el.style.transition = 'all 0.3s';
                        el.style.opacity = '0';
                        el.style.transform = 'translateX(20px)';
                        setTimeout(() => el.remove(), 300);
                    }
                    // Update CSRF if returned
                    if (data.csrf_token) csrfHash = data.csrf_token;
                } else {
                    alert('Delete failed: ' + (data.error || 'Unknown error'));
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-trash-alt"></i> Delete';
                    }
                }
            } catch (e) {
                alert('Network error. Please try again.');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-trash-alt"></i> Delete';
                }
            }
        }

        function escHtml(str) {
            const d = document.createElement('div');
            d.textContent = str;
            return d.innerHTML;
        }

        // Load song list on page load
        document.addEventListener('DOMContentLoaded', loadSongList);
    </script>
</body>

</html>