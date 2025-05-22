<!DOCTYPE html>
<html lang="en">

<head>
    <title>Register</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
</head>

<body style="margin:0; background-color: rgb(0, 0, 0);">

    <div class="section bg-1">
        <!-- Navbar -->
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="login.php">Log In</a></li>
            </ul>
        </nav>

        <!-- Logo Top Right -->
        <div style="position: absolute; top: 10px; right: 10px;">
            <img src="css/avhubert_logo.png" alt="AV-HuBERT Logo" style="width: 100px; height: auto;">
        </div>

        <!-- Register Form -->
        <div class="overlay-section" style="color: white; width: 30%; margin: 4% auto 0 auto;">
            <h1 style="color: white; font-family: 'Times New Roman', Times, serif;">REGISTER</h1>
            <form action="register_process.php" method="POST" style="text-align: left;">
                <label for="username">Username:</label><br>
                <input type="text" id="username" name="username" required style="width: 90%; padding: 8px;"><br><br>

                <label for="email">Email:</label><br>
                <input type="email" id="email" name="email" required style="width: 90%; padding: 8px;"><br><br>

                <label for="password">Password:</label><br>
                <input type="password" id="password" name="password" required style="width: 90%; padding: 8px;"><br><br>

                <label for="confirm_password">Confirm Password:</label><br>
                <input type="password" id="confirm_password" name="confirm_password" required style="width: 90%; padding: 8px;"><br><br>

                <button type="submit" style="width: 100%; padding: 10px;">Register</button>

                <!-- Link to login -->
                <p style="text-align: center; margin-top: 15px;">
                    <a href="login.php" style="color: white;">Already have an account? Log in here.</a>
                </p>
            </form>
        </div>
    </div>
</body>

</html>