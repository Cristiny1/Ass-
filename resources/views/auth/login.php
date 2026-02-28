<?php
session_start();

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit();
}

// Handle login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // $username = $_POST['username'] ?? '';
    // $password = $_POST['password'] ?? '';

    // Example: Replace with your actual DB check
    if ($username === 'rorn' && $password === 'admin123') {
        $_SESSION['user_id'] = 1; // store user ID or other identifier
        $_SESSION['role'] = 'admin';
        $_SESSION['success'] = "Login successful!";
        header('Location: ../admin/dashboard.php');
        exit();
    }elseif ($username === 'teacher' && $password === 'teacher123') {
        $_SESSION['user_id'] = 2; // store user ID or other identifier
        $_SESSION['role'] = 'teacher';
        $_SESSION['success'] = "Login successful!";
        header('Location: teacher/dashboard.php');
        exit();
    }elseif ($username === 'student' && $password === 'student123') {
        $_SESSION['user_id'] = 3; // store user ID or other identifier
        $_SESSION['role'] = 'student';
        $_SESSION['success'] = "Login successful!";
        header('Location: student/dashboard.php');
        exit();
    } else {
        $_SESSION['error'] = "Invalid username or password.";
        header('Location: login.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Online Quiz System</title>
    <link rel="stylesheet" href="../../assets/css/login.css">

</head>
<body>
    <div class="login-container">
        <img src="../image/1.png" alt="">
        <h1>Online Quiz System</h1>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="username" id="username" name="username" required placeholder="Enter your username">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter your password">
            </div>
            
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
