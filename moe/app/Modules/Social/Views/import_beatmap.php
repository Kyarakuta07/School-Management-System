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

        <a href="<?= base_url('rhythm-game') ?>" class="back-link">
            <i class="fas fa-arrow-left"></i> Kembali ke Game
        </a>
    </div>

    <script>
        const IMPORT_API_URL = '<?= base_url('api/rhythm/import') ?>';
        const csrfName = '<?= csrf_token() ?>';
        let csrfHash = '<?= csrf_hash() ?>';
    </script>
    <script src="<?= base_url('js/social/import_beatmap.js') ?>"></script>
</body>

</html>