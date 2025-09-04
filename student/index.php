<?php
session_start();
require '../config/database.php';

// Check for remember me token
if (!isset($_SESSION['user_id']) && isset($_COOKIE['student_remember_token'])) {
    $remember_token = $_COOKIE['student_remember_token'];
    
    $stmt = $pdo->prepare("
        SELECT id, nama_lengkap 
        FROM siswa 
        WHERE remember_token = ? 
        AND remember_token_expires > NOW()
    ");
    $stmt->execute([$remember_token]);
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        header('Location: dashboard.php');
        exit();
    } else {
        // Invalid or expired token, clear cookie
        setcookie('student_remember_token', '', time() - 3600, '/');
    }
}

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Anastasya Vocal Arts - Students</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
   <meta name="description" content="Anastasya Vocal Arts Students">

  <!-- Favicon dasar -->
  <link rel="icon" type="image/png" sizes="32x32" href="assets/images/ava_icon.png">
  <link rel="icon" type="image/png" sizes="192x192" href="assets/images/ava_icon.png">
  <link rel="apple-touch-icon" href="assets/images/ava_icon.png">

  <!-- Open Graph (WA/FB/Telegram) -->
  <meta property="og:title" content="Anastasya Vocal Arts - Students">
  <meta property="og:description" content="Platform AVA Students.">
  <meta property="og:image" content="https://anastasya.co/student/assets/images/ava_icon.png">
  <meta property="og:url" content="https://anastasya.co/student/">
  <meta property="og:type" content="website">

  <!-- Twitter Card (opsional) -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="Anastasya Vocal Arts - Students">
  <meta name="twitter:description" content="Platform AVA Students.">
  <meta name="twitter:image" content="https://anastasya.co/student/assets/images/ava_icon.png">
</head>
<body>
    <div class="container">
      
        <img src="../avaaset/AVA-Logo-Master.png" alt="Logo" class="logo">

        <div class="login-card">
            <h2>Login </h2>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="login_process.php" method="POST">
                <div class="input-group">
                    <input type="email" id="email" name="email" placeholder=" " required>
                    <label for="email">Email</label>
                </div>
                <div class="input-group">
                    <input type="password" id="password" name="password" placeholder=" " required>
                    <label for="password">Password</label>
                </div>
                <button type="submit" class="continue-button">Continue</button>
            </form>
            <br>
            <div class="flex items-center justify-between mt-4">
                <div class="flex items-center">
                    <input id="remember" name="remember" type="checkbox" class="h-4 w-4 text-pink-accent focus:ring-pink-accent border-pink-300 rounded">
                    <label for="remember" class="ml-2 block text-sm text-pink-dark">
                        Remember me
                    </label>
                </div>
            </div>
            <br>
            <a href="forgot_password.php" class="forgot-password">Forgot Password?</a>
        </div>
    </div>
</body>
</html>
