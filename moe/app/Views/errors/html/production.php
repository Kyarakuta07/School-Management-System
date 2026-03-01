<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistem Sedang Menghadapi Badai Pasir</title>
    <style>
        body {
            background-color: #1a1a1a;
            color: #d4af37;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            text-align: center;
            padding: 50px;
            background-image: url('<?= base_url('assets/images/bg_home.jpg') ?>');
            background-size: cover;
            background-position: center;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1;
        }

        .container {
            position: relative;
            z-index: 2;
            max-width: 600px;
            margin: 10vh auto;
            background: rgba(20, 20, 20, 0.9);
            padding: 40px;
            border: 2px solid #8b6b23;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(212, 175, 55, 0.2);
        }

        h1 {
            font-size: 2.5em;
            border-bottom: 1px solid #8b6b23;
            padding-bottom: 20px;
        }

        p {
            font-size: 1.2em;
            color: #e0e0e0;
            line-height: 1.6;
        }

        .back-btn {
            display: inline-block;
            margin-top: 30px;
            padding: 10px 20px;
            background: linear-gradient(135deg, #8b6b23, #d4af37);
            color: #000;
            text-decoration: none;
            font-weight: bold;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .back-btn:hover {
            background: #fff;
            color: #8b6b23;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Terjadi Kesalahan (500)</h1>
        <p>Aduh! Sistem sedang tertiup badai pasir dan para Arsitek (Developer) telah diberitahu untuk mendesain ulang
            reruntuhan ini.</p>
        <p>Silakan coba beberapa saat lagi.</p>
        <a href="<?= base_url('beranda') ?>" class="back-btn">Kembali ke Tempat Aman</a>
    </div>
</body>

</html>