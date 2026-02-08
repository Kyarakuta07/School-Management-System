<?php require_once __DIR__ . '/core/helpers.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Staff & Council - Mediterranean of Egypt</title>

    <!-- SEO Meta Tags -->
    <meta name="description"
        content="Meet the Imperial Roster of Mediterranean of Egypt - Vasiki leaders, SEN council, and dedicated staff members guiding our virtual academy.">
    <meta name="keywords"
        content="MOE staff, Mediterranean of Egypt council, Vasiki, SEN, school staff, virtual academy team">
    <meta name="author" content="Mediterranean of Egypt School">
    <meta name="robots" content="index, follow">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Staff & Council - Mediterranean of Egypt">
    <meta property="og:description" content="Meet the Guardians of Mediterranean - our leadership and staff team.">
    <meta property="og:image" content="assets/landing/logo.png">

    <!-- Theme Color -->
    <meta name="theme-color" content="#0a0a0a">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/landing/logo.png">
    <link rel="apple-touch-icon" href="assets/landing/logo.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Lato:wght@400;700&display=swap"
        rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <!-- Global CSS (with cache busting) -->
    <link rel="stylesheet" href="<?= asset('assets/css/navbar.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/landing-style.css') ?>">

    <!-- Staff Page CSS -->
    <link rel="stylesheet" href="<?= asset('assets/css/staff.css') ?>">
</head>

<body>

    <div class="background-container"></div>

    <div class="page-wrapper">

        <!-- NAVBAR -->
        <?php include 'navbar.php'; ?>

        <!-- STAFF SECTION -->
        <section class="content-section staff-section">

            <div style="text-align: center; margin-bottom: 4rem;">
                <h2 class="section-title">IMPERIAL ROSTER</h2>
                <p class="section-subtitle">The Guardians of Mediterranean</p>
            </div>

            <!-- VASIKI GROUP -->
            <div class="group-wrapper theme-vasiki">
                <h3 class="group-title">VASIKI</h3>

                <div class="staff-grid">
                    <div class="staff-card">
                        <img src="assets/landing/jason.jpeg" alt="Jason" class="staff-photo">
                        <h3>Jason Alekhenako Momoa</h3>
                        <p class="staff-title">Vasiki</p>
                    </div>

                    <div class="staff-card">
                        <img src="assets/landing/cheva.jpeg" alt="Cheva" class="staff-photo">
                        <h3>Cheva Asenath Nefreti</h3>
                        <p class="staff-title">Vasiki</p>
                    </div>

                    <div class="staff-card">
                        <img src="assets/landing/matthew.jpeg" alt="Matthew" class="staff-photo">
                        <h3>Matthew Djoser Akenatem</h3>
                        <p class="staff-title">Vasiki</p>
                    </div>

                    <div class="staff-card">
                        <img src="assets/landing/calix.jpeg" alt="Calix" class="staff-photo">
                        <h3>Calix Djedefre Thannyros</h3>
                        <p class="staff-title">Vasiki</p>
                    </div>

                    <div class="staff-card">
                        <img src="assets/landing/hector.jpeg" alt="Hector" class="staff-photo">
                        <h3>Hector Nicholas Theodore</h3>
                        <p class="staff-title">Vasiki</p>
                    </div>

                    <div class="staff-card">
                        <img src="assets/landing/nio.jpeg" alt="Athynio" class="staff-photo">
                        <h3>Athynio Victoria Eleanor</h3>
                        <p class="staff-title">Vasiki</p>
                    </div>
                </div>
            </div>

            <!-- SEN GROUP -->
            <div class="group-wrapper theme-sen">
                <h3 class="group-title">SEN</h3>

                <div class="staff-grid">
                    <div class="staff-card">
                        <img src="assets/landing/staff-1.png" alt="Cho Chang" class="staff-photo">
                        <h3>Cho Chang</h3>
                        <p class="staff-title">SEN</p>
                    </div>

                    <div class="staff-card">
                        <img src="assets/landing/staff-2.png" alt="Sheren" class="staff-photo">
                        <h3>Sheren Altheda</h3>
                        <p class="staff-title">SEN</p>
                    </div>

                    <div class="staff-card">
                        <img src="assets/landing/staff-3.png" alt="Nathaniel" class="staff-photo">
                        <h3>Nathaniel Vorthalos</h3>
                        <p class="staff-title">SEN</p>
                    </div>
                </div>
            </div>

            <!-- OTHER STAFF -->
            <div class="group-wrapper theme-member">
                <h3 class="group-title">OTHER STAFF</h3>

                <div class="staff-grid">
                    <div class="staff-card">
                        <img src="assets/landing/Azura.jpeg" alt="Azura" class="staff-photo">
                        <h3>Azura Lexy Dexton</h3>
                        <p class="staff-title">Staff</p>
                    </div>

                    <div class="staff-card">
                        <img src="assets/landing/staff-2.png" alt="Carina" class="staff-photo">
                        <h3>Carina de Agler Obelia</h3>
                        <p class="staff-title">Staff</p>
                    </div>

                    <div class="staff-card">
                        <img src="assets/landing/staff-3.png" alt="Loressa" class="staff-photo">
                        <h3>Loressa Aglenar</h3>
                        <p class="staff-title">Staff</p>
                    </div>

                    <div class="staff-card">
                        <img src="assets/landing/nada.jpeg" alt="Nada" class="staff-photo">
                        <h3>Nada Kaleria</h3>
                        <p class="staff-title">Staff</p>
                    </div>

                    <div class="staff-card">
                        <img src="assets/landing/lune.jpeg" alt="Lunne" class="staff-photo">
                        <h3>Lunne Demore</h3>
                        <p class="staff-title">Staff</p>
                    </div>

                    <div class="staff-card">
                        <img src="assets/landing/tom.jpeg" alt="Thomas" class="staff-photo">
                        <h3>Thomas El</h3>
                        <p class="staff-title">Staff</p>
                    </div>
                </div>
            </div>

        </section>

    </div>

    <!-- FOOTER (Sama seperti home.php) -->
    <footer class="marquee-footer">
        <div class="marquee-track">
            <span class="marquee-item">MEDITERRANEAN OF EGYPT &nbsp; ✦</span>
            <span class="marquee-item">MEDITERRANEAN OF EGYPT &nbsp; ✦</span>
            <span class="marquee-item">MEDITERRANEAN OF EGYPT &nbsp; ✦</span>
            <span class="marquee-item">MEDITERRANEAN OF EGYPT &nbsp; ✦</span>
        </div>

        <div class="marquee-track">
            <span class="marquee-item">MEDITERRANEAN OF EGYPT &nbsp; ✦</span>
            <span class="marquee-item">MEDITERRANEAN OF EGYPT &nbsp; ✦</span>
            <span class="marquee-item">MEDITERRANEAN OF EGYPT &nbsp; ✦</span>
            <span class="marquee-item">MEDITERRANEAN OF EGYPT &nbsp; ✦</span>
        </div>
    </footer>

</body>

</html>