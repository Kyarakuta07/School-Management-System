<?php require_once __DIR__ . '/core/helpers.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>World Map - Mediterranean of Egypt</title>

    <!-- SEO Meta Tags -->
    <meta name="description"
        content="Explore the mystical world of Mediterranean of Egypt. Navigate the 5 Sanctuaries: Horus, Osiris, Khonshu, Ammit, and Hathor.">
    <meta name="keywords"
        content="MOE, Mediterranean of Egypt, world map, sanctuaries, Horus, Osiris, Khonshu, Ammit, Hathor">

    <!-- Theme Color -->
    <meta name="theme-color" content="#0a0a0a">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/landing/logo.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Lato:wght@400;700&display=swap"
        rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <!-- Global CSS -->
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/landing-style.css">

    <style>
        /* World Map Page Styles */
        .world-container {
            min-height: 100vh;
            padding-top: 80px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .map-wrapper {
            position: relative;
            width: 90vw;
            max-width: 1400px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 0 60px rgba(218, 165, 32, 0.3), 0 0 100px rgba(0, 0, 0, 0.8);
            border: 2px solid rgba(218, 165, 32, 0.4);
        }

        /* Base map (cleaned, no buildings) */
        .map-base {
            width: 100%;
            height: auto;
            display: block;
        }

        /* === LIVING MAP: Building Overlays === */
        .sanctuary-building {
            position: absolute;
            cursor: pointer;
            transition: transform 0.3s ease, filter 0.3s ease;
            transform-origin: center bottom;
            /* Scale from bottom for "pop up" effect */
            z-index: 10;
        }

        .sanctuary-building:hover {
            transform: scale(1.15) translateY(-5px);
            filter: drop-shadow(0 0 20px rgba(218, 165, 32, 0.8)) drop-shadow(0 10px 30px rgba(0, 0, 0, 0.6));
            z-index: 20;
        }

        .sanctuary-building img {
            width: 100%;
            height: auto;
            display: block;
        }

        /* Building positions - ALIGNED TO ORIGINAL MAP */
        .building-horus {
            width: 22%;
            top: 26%;
            left: 11%;
        }

        .building-khonshu {
            width: 20%;
            top: 27%;
            right: 13%;
        }

        .building-osiris {
            width: 20%;
            top: 54%;
            left: 7%;
        }

        .building-hathor {
            width: 26%;
            bottom: 3%;
            left: 38%;
        }

        .building-ammit {
            width: 24%;
            top: 52%;
            right: 10%;
        }

        .building-pyramid {
            width: 24%;
            top: 26%;
            left: 40%;
            z-index: 5;
        }

        /* Floating label on hover */
        .sanctuary-building::after {
            content: attr(data-name);
            position: absolute;
            bottom: -25px;
            left: 50%;
            transform: translateX(-50%) translateY(10px);
            font-family: 'Cinzel', serif;
            font-size: 0.85rem;
            color: #DAA520;
            text-shadow: 0 0 10px rgba(218, 165, 32, 0.9), 0 2px 6px rgba(0, 0, 0, 0.9);
            white-space: nowrap;
            opacity: 0;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 2px;
            pointer-events: none;
        }

        .sanctuary-building:hover::after {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        /* Page Title */
        .world-title {
            text-align: center;
            margin-bottom: 2rem;
        }

        .world-title h1 {
            font-family: 'Cinzel', serif;
            font-size: 2.5rem;
            color: #DAA520;
            text-shadow: 0 0 20px rgba(218, 165, 32, 0.5);
            margin-bottom: 0.5rem;
        }

        .world-title p {
            font-family: 'Lato', sans-serif;
            color: rgba(255, 255, 255, 0.7);
            font-size: 1rem;
        }

        /* Info Panel (Shows on click) */
        .sanctuary-info-panel {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(10, 10, 10, 0.98) 0%, rgba(10, 10, 10, 0.9) 100%);
            border-top: 2px solid rgba(218, 165, 32, 0.5);
            padding: 2rem;
            transform: translateY(100%);
            transition: transform 0.4s ease;
            z-index: 100;
        }

        .sanctuary-info-panel.active {
            transform: translateY(0);
        }

        .info-panel-content {
            max-width: 800px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .info-panel-emblem {
            width: 100px;
            height: 100px;
            flex-shrink: 0;
        }

        .info-panel-text h2 {
            font-family: 'Cinzel', serif;
            color: #DAA520;
            margin-bottom: 0.5rem;
        }

        .info-panel-text p {
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.6;
        }

        .info-panel-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            color: #DAA520;
            font-size: 1.5rem;
            cursor: pointer;
        }

        .btn-enter-guild {
            display: inline-block;
            margin-top: 1rem;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #DAA520, #B8860B);
            color: #000;
            font-family: 'Cinzel', serif;
            font-weight: 700;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .btn-enter-guild:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(218, 165, 32, 0.5);
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .world-title h1 {
                font-size: 1.8rem;
            }

            .map-wrapper {
                width: 98vw;
            }

            .sanctuary-building::after {
                font-size: 0.65rem;
                bottom: -18px;
            }

            .info-panel-content {
                flex-direction: column;
                text-align: center;
            }

            .node-marker {
                width: 40px !important;
            }
        }
    </style>
</head>

<body>
    <div class="background-container"></div>

    <div class="page-wrapper">

        <!-- NAVBAR -->
        <?php include 'navbar.php'; ?>

        <!-- WORLD MAP SECTION -->
        <section class="world-container">

            <div class="world-title">
                <h1><i class="fas fa-globe-africa"></i> THE REALM OF MOE</h1>
                <p>Hover over a Sanctuary to explore</p>
            </div>

            <div class="map-wrapper">
                <!-- Base Map (Cleaned - no buildings) -->
                <img src="assets/map/map_moe_cleanup.jpeg" alt="World Map of Mediterranean of Egypt" class="map-base">

                <div class="sanctuary-building building-horus" data-sanctuary="horus" data-name="Sanctuary of Horus">
                    <img src="assets/map/sanctuary_horus (1).png" alt="Horus Sanctuary">
                </div>

                <div class="sanctuary-building building-khonshu" data-sanctuary="khonshu"
                    data-name="Sanctuary of Khonshu">
                    <img src="assets/map/sanctuary_khonshu (1).png" alt="Khonshu Sanctuary">
                </div>

                <div class="sanctuary-building building-osiris" data-sanctuary="osiris" data-name="Sanctuary of Osiris">
                    <img src="assets/map/sanctuary_osiris (1).png" alt="Osiris Sanctuary">
                </div>

                <div class="sanctuary-building building-hathor" data-sanctuary="hathor" data-name="Sanctuary of Hathor">
                    <img src="assets/map/sanctuary_hathor (1).png" alt="Hathor Sanctuary">
                </div>

                <div class="sanctuary-building building-ammit" data-sanctuary="ammit" data-name="Sanctuary of Ammit">
                    <img src="assets/map/sanctuary_ammit (1).png" alt="Ammit Sanctuary">
                </div>

                <div class="sanctuary-building building-pyramid" data-sanctuary="pyramid" data-name="Pyramid Khufu">
                    <img src="assets/map/Piramida_tengah (1).png" alt="Pyramid Khufu">
                </div>


            </div>

        </section>

    </div>

    <!-- Info Panel (Hidden by default) -->
    <div class="sanctuary-info-panel" id="info-panel">
        <button class="info-panel-close" id="close-panel"><i class="fas fa-times"></i></button>
        <div class="info-panel-content">
            <img src="" alt="Emblem" class="info-panel-emblem" id="panel-emblem">
            <div class="info-panel-text">
                <h2 id="panel-title">Sanctuary Name</h2>
                <p id="panel-desc">Description goes here...</p>
                <a href="#" class="btn-enter-guild" id="panel-btn">
                    <i class="fas fa-door-open"></i> Enter Guild Hall
                </a>
            </div>
        </div>
    </div>

    <script>
        // Sanctuary Data
        const sanctuaryData = {
            horus: {
                name: 'Sanctuary of Horus',
                emblem: 'assets/faction emblem/faction_horus.png',
                desc: 'The Sky Watchers. Disciples of the Falcon God, masters of aerial combat and divine vision. Horus members are known for their sharp strategy and unwavering justice.',
                url: 'user/guild_hall.php?faction=horus'
            },
            khonshu: {
                name: 'Sanctuary of Khonshu',
                emblem: 'assets/faction emblem/faction_khonshu.png',
                desc: 'The Moon Walkers. Followers of the God of the Moon, shrouded in mystery and wielding the power of the night. They navigate between light and shadow.',
                url: 'user/guild_hall.php?faction=khonshu'
            },
            osiris: {
                name: 'Sanctuary of Osiris',
                emblem: 'assets/faction emblem/faction_osiris.png',
                desc: 'The Eternal. Servants of the Lord of the Underworld, guardians of the cycle of life and death. Osiris members specialize in resurrection and endurance.',
                url: 'user/guild_hall.php?faction=osiris'
            },
            hathor: {
                name: 'Sanctuary of Hathor',
                emblem: 'assets/faction emblem/faction_hathor.png',
                desc: 'The Harmonizers. Devotees of the Goddess of Love and Joy. Masters of healing, support magic, and morale-boosting auras in battle.',
                url: 'user/guild_hall.php?faction=hathor'
            },
            ammit: {
                name: 'Sanctuary of Ammit',
                emblem: 'assets/faction emblem/faction_ammit.png',
                desc: 'The Soul Devourers. Followers of the fearsome beast who judges the dead. They specialize in raw power, intimidation, and finishing blows.',
                url: 'user/guild_hall.php?faction=ammit'
            },
            pyramid: {
                name: 'Pyramid Khufu & Khafre',
                emblem: 'assets/landing/logo.png',
                desc: 'The ancient tombs of the Pharaohs. A neutral zone where all Sanctuaries gather for grand events, trade, and the Festival of the Sun.',
                url: '#'
            }
        };

        // DOM Elements
        const buildings = document.querySelectorAll('.sanctuary-building');
        const infoPanel = document.getElementById('info-panel');
        const panelEmblem = document.getElementById('panel-emblem');
        const panelTitle = document.getElementById('panel-title');
        const panelDesc = document.getElementById('panel-desc');
        const panelBtn = document.getElementById('panel-btn');
        const closeBtn = document.getElementById('close-panel');

        // Building Click Handler
        buildings.forEach(building => {
            building.addEventListener('click', () => {
                const key = building.dataset.sanctuary;
                const data = sanctuaryData[key];

                if (data) {
                    panelEmblem.src = data.emblem;
                    panelTitle.textContent = data.name;
                    panelDesc.textContent = data.desc;
                    panelBtn.href = data.url;
                    // Hide button if no URL (Pyramid case)
                    panelBtn.style.display = data.url === '#' ? 'none' : 'inline-block';
                    infoPanel.classList.add('active');
                }
            });
        });



        // Close Panel
        closeBtn.addEventListener('click', () => {
            infoPanel.classList.remove('active');
        });

        // Close on outside click
        infoPanel.addEventListener('click', (e) => {
            if (e.target === infoPanel) {
                infoPanel.classList.remove('active');
            }
        });

        // Keyboard support (ESC to close)
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && infoPanel.classList.contains('active')) {
                infoPanel.classList.remove('active');
            }
        });
    </script>
</body>

</html>