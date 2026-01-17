<?php require_once __DIR__ . '/core/helpers.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classes - Mediterranean of Egypt</title>

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

    <!-- Class Page CSS -->
    <link rel="stylesheet" href="<?= asset('assets/css/class.css') ?>">
</head>

<body>

    <div class="background-container"></div>

    <div class="page-wrapper">

        <!-- NAVBAR -->
        <?php include 'navbar.html'; ?>

    </div>

    <!-- CLASS SECTION -->
    <section class="content-section class-section">

        <h2 class="section-title">ANCIENT KNOWLEDGE CLASSES</h2>
        <p class="section-subtitle">Unlock the mysteries of the Mediterranean.</p>

        <div class="class-grid">

            <!-- Oceanology -->
            <div class="class-card ocean">
                <div class="card-icon"><i class="fa-solid fa-water"></i></div>
                <h3>Oceanology</h3>
                <p>Study the secrets of the Nile and the mystic depths of the Mediterranean Sea.</p>
                <a href="#" class="class-btn">View Syllabus</a>
            </div>

            <!-- Herbology -->
            <div class="class-card herb">
                <div class="card-icon"><i class="fa-solid fa-leaf"></i></div>
                <h3>Herbology</h3>
                <p>Master the ancient art of healing, poisons, and magical flora of Egypt.</p>
                <a href="#" class="class-btn">View Syllabus</a>
            </div>

            <!-- Astronomy -->
            <div class="class-card astro">
                <div class="card-icon"><i class="fa-solid fa-star-and-crescent"></i></div>
                <h3>Astronomy</h3>
                <p>Read the stars, navigate the desert sands, and predict the empire's fate.</p>
                <a href="#" class="class-btn">View Syllabus</a>
            </div>

        </div>

    </section>

</body>

</html>