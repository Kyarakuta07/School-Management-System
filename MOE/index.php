<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Mediterranean Of Egypt</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600&family=Lato:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <div class="scene-container">

        <div class="login-card">
            <img src="logo.png" alt="MOE Logo" class="login-logo">
            <h2>Mediterranean Of Egypt</h2>
            <h3>Enter your credentials</h3>
            <?php
            if (isset($_GET['pesan']) && $_GET['pesan'] == 'gagal') {
                echo "<p class='error-message'>Login failed! Incorrect name or password.</p>";
            }
            ?>
            <form class="login-form" action="proses_login.php" method="POST">
                <input type="text" name="username" placeholder="Your Name" required />
                <input type="password" name="password" placeholder="Password" required />
                <a href="#">Forgot your password?</a>
                <button type="submit">LOGIN</button>
            </form>
        </div>
        <img src="camels.svg" alt="Walking camels caravan" class="walking-camels">
    </div>
</body>
</html>
