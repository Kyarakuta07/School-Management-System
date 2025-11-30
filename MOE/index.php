<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Mediterranean Of Egypt</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Lato:wght@400;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="bg-image"></div>
    <div class="bg-overlay"></div>

    <div class="login-container">
        
        <div class="login-logo">
            <img src="assets/landing/logo.png" alt="MOE Logo">
        </div>

        <h1>Mediterranean Of Egypt</h1>
        <p class="subtitle">Enter your credentials</p>

        <?php
        if (isset($_GET['pesan']) && $_GET['pesan'] == "gagal") {
            echo '<div class="alert-error">
                    <i class="fa-solid fa-triangle-exclamation"></i> 
                    Incorrect username or password.
                  </div>';
        }
        ?>

        <form action="proses_login.php" method="POST">
            
            <div class="input-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" placeholder="Enter username" required autocomplete="off">
                <i class="fa-solid fa-user input-icon"></i>
            </div>

            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                <i class="fa-solid fa-lock input-icon"></i>
            </div>

            <button type="submit" class="btn-login">LOGIN</button>

            <div class="footer-links">
                <a href="#">Forgot Password?</a>
                <a href="signup.php">Create Account</a>
            </div>
        </form>

        <a href="index.html" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Back to Home
        </a>
    </div>

</body>
</html>