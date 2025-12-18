<?php
// Allow Sketchfab 3D embed - override CSP headers
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://static.sketchfab.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com https://unpkg.com; frame-src 'self' https://sketchfab.com; img-src 'self' data: blob: https: http:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com; connect-src 'self' https://sketchfab.com https://static.sketchfab.com; worker-src 'self' blob:;");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mediterranean of Egypt - Home</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/landing/logo.png">
    <link rel="shortcut icon" type="image/png" href="assets/landing/logo.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Lato:wght@400;700&display=swap"
        rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <!-- Sketchfab 3D Model (Horus Egyptian God) -->

    <!-- Global CSS -->
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/landing-style.css">

    <!-- Home Page CSS -->
    <link rel="stylesheet" href="assets/css/home.css">

    <!-- Spline 3D Styles -->
    <style>
        /* Spline 3D Container */
        .spline-container {
            position: absolute;
            top: 0;
            right: 0;
            width: 50%;
            height: 100vh;
            z-index: 2;
            pointer-events: none;
        }

        .spline-container spline-viewer {
            width: 100%;
            height: 100%;
            pointer-events: auto;
        }

        /* Hide character image when Spline is active */
        .hero-right.spline-active .character-container {
            display: none;
        }

        /* Fallback - show character if Spline fails */
        .hero-right .character-container {
            transition: opacity 0.5s ease;
        }

        /* Spline loading indicator */
        .spline-loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: var(--gold, #DAA520);
            font-size: 1rem;
            text-align: center;
        }

        .spline-loading i {
            font-size: 2rem;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        /* Mobile: stack layout, Spline on top */
        @media (max-width: 768px) {
            .spline-container {
                position: relative;
                width: 100%;
                height: 300px;
            }
        }

        /* Enhanced hero section for Spline integration */
        .hero-section.with-spline {
            position: relative;
        }

        .hero-section.with-spline .hero-left {
            z-index: 5;
        }

        /* Floating particles overlay */
        .particles-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
            overflow: hidden;
        }

        .particle-gold {
            position: absolute;
            width: 4px;
            height: 4px;
            background: radial-gradient(circle, #ffd700 0%, transparent 70%);
            border-radius: 50%;
            animation: floatParticle 10s linear infinite;
            opacity: 0.6;
        }

        @keyframes floatParticle {
            0% {
                transform: translateY(100vh) translateX(0);
                opacity: 0;
            }

            10% {
                opacity: 0.6;
            }

            90% {
                opacity: 0.6;
            }

            100% {
                transform: translateY(-100px) translateX(50px);
                opacity: 0;
            }
        }
    </style>
</head>

<body>

    <!-- Floating Gold Particles Overlay -->
    <div class="particles-overlay" id="particles-overlay"></div>

    <div class="background-container"></div>

    <div class="page-wrapper">

        <!-- NAVBAR -->
        <?php include 'navbar.html'; ?>

        <!-- HOME SECTION -->
        <section id="home-section" class="hero-section with-spline">
            <div class="hero-left">

                <div class="title-container">
                    <img src="assets/landing/7.png" alt="MOE" class="moe-text-img">
                    <h1>Mediterranean of Egypt</h1>
                </div>

                <div class="about-container">
                    <h2 class="about-title">ABOUT</h2>
                    <div class="about-box">
                        <p>
                            Welcome to the Mediterranean of Egypt, a premier virtual academy bridging the gap
                            between ancient sands and the digital realm. Inspired by the lore of Moon Knight and the
                            mystic
                            corners of the DC Universe, MOE offers a structured curriculum for those seeking knowledge
                            beyond
                            the veil. From Oceanology to the Dark Arts, our halls are open to all scholars ready to
                            embrace
                            their destiny and ascend the ranks of our order.
                        </p>
                    </div>

                    <!-- CTA Buttons -->
                    <div class="hero-cta" style="margin-top: 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                        <a href="index.php" class="btn-cta primary">
                            <i class="fas fa-door-open"></i> Enter Portal
                        </a>
                        <a href="staff.php" class="btn-cta secondary">
                            <i class="fas fa-users"></i> Meet Our Staff
                        </a>
                    </div>
                </div>

            </div>

            <div class="hero-right" id="hero-right">
                <!-- Sketchfab 3D Egyptian Model (Horus by Rumpelstiltskin) -->
                <div class="sketchfab-embed-wrapper"
                    style="width: 100%; height: 100%; position: absolute; top: 0; right: 0;">
                    <iframe title="Horus - Egyptian God" frameborder="0" allowfullscreen mozallowfullscreen="true"
                        webkitallowfullscreen="true" allow="autoplay; fullscreen; xr-spatial-tracking"
                        xr-spatial-tracking execution-while-out-of-viewport execution-while-not-rendered web-share
                        src="https://sketchfab.com/models/3f6e75ecb0b44fb6bd7df0c3b2c7ce32/embed?autostart=1&transparent=1&ui_theme=dark"
                        style="width: 100%; height: 100%;">
                    </iframe>
                </div>

                <!-- Fallback: Original character image (shown if Spline fails or on slow connections) -->
                <div class="character-container" id="fallback-character">
                    <img src="assets/landing/nyatuin.png" alt="Main Character" class="main-combined">
                </div>
            </div>
        </section>

    </div>

    <!-- FOOTER -->
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

    <!-- Spline Viewer Component -->


    <!-- Particle Generator Script -->
    <script>
        // Generate floating gold particles
        function createParticles() {
            const container = document.getElementById('particles-overlay');
            const particleCount = 30;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle-gold';

                // Random position and timing
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 10 + 's';
                particle.style.animationDuration = (8 + Math.random() * 6) + 's';

                // Random size
                const size = 2 + Math.random() * 4;
                particle.style.width = size + 'px';
                particle.style.height = size + 'px';

                container.appendChild(particle);
            }
        }

        // Handle Spline loading
        document.addEventListener('DOMContentLoaded', () => {
            createParticles();

            const splineViewer = document.getElementById('spline-viewer');
            const loadingEl = document.getElementById('spline-loading');
            const fallbackEl = document.getElementById('fallback-character');

            // Spline load event
            if (splineViewer) {
                splineViewer.addEventListener('load', () => {
                    console.log('✅ Spline 3D scene loaded');
                    loadingEl.style.display = 'none';
                    fallbackEl.style.display = 'none';
                });

                splineViewer.addEventListener('error', (e) => {
                    console.warn('⚠️ Spline failed to load, showing fallback');
                    loadingEl.style.display = 'none';
                    fallbackEl.style.display = 'block';
                });

                // Timeout fallback (if Spline takes too long)
                setTimeout(() => {
                    if (loadingEl.style.display !== 'none') {
                        console.log('⚠️ Spline timeout, showing fallback');
                        loadingEl.style.display = 'none';
                        fallbackEl.style.display = 'block';
                    }
                }, 10000); // 10 second timeout
            }
        });
    </script>

    <style>
        /* CTA Button Styles */
        .btn-cta {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-family: 'Cinzel', serif;
            font-weight: 700;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-cta.primary {
            background: linear-gradient(135deg, #DAA520, #B8860B);
            color: #000;
            border: none;
            box-shadow: 0 4px 20px rgba(218, 165, 32, 0.4);
        }

        .btn-cta.primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(218, 165, 32, 0.6);
        }

        .btn-cta.secondary {
            background: transparent;
            color: #DAA520;
            border: 2px solid rgba(218, 165, 32, 0.5);
        }

        .btn-cta.secondary:hover {
            background: rgba(218, 165, 32, 0.1);
            border-color: #DAA520;
            transform: translateY(-3px);
        }

        /* Button shimmer effect */
        .btn-cta::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-cta:hover::before {
            left: 100%;
        }
    </style>

</body>

</html>