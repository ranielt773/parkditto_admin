<?php
include 'includes/auth.php';

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (login($username, $password)) {
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ParkDitto - Admin Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="login-body">
    <div class="overlay">
        <!-- Background cars -->
        <img src="assets/red-car.png" alt="Red Car" class="car car-left">
        <img src="assets/black-car.png" alt="Black Car" class="car car-right">

        <!-- Login Box -->
        <div class="login-box shadow">
            <h5 class="login-title">
                <span class="fw-bold text-danger">Welcome</span>
                <img src="assets/parkditto.png" alt="ParkDitto Logo" class="logo-text">
                <span class="fw-bold text-danger">Admin</span>
            </h5>

            <?php if (isset($error)): ?>
                <div class="alert"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="input-container">
                    <input type="text" class="custom-input" name="username" id="username" required>
                    <label class="floating-label" for="username">Username</label>
                </div>
                <div class="input-container">
                    <input type="password" class="custom-input" name="password" id="password" required>
                    <label class="floating-label" for="password">Password</label>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <input type="checkbox" id="remember" class="form-check-input">
                        <label for="remember" class="form-check-label small">Remember me</label>
                    </div>
                    <a href="#" class="small text-decoration-none">Forgot password?</a>
                </div>

                <button type="submit" class="btn-login">Login</button>
            </form>
        </div>
    </div>

    
</body>

</html>