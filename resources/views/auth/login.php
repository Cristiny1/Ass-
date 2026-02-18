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
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background-image: url("../image/login_bg"); /* make sure the path is correct, include extension .jpg/.png */
            background-size: cover;      /* makes image cover entire viewport */
            background-position: center; /* centers the image */
            background-repeat: no-repeat;/* prevents tiling */
            background-attachment: fixed;/* optional: keeps image fixed while scrolling */
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            background: rgba(245, 245, 245, 0.5);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            max-height: 500px;
        }
        h2 {
            
            text-align: center;
            margin-bottom: 10px;
            color: #000000;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #030303;
            font-weight: bold;
        }
        input[type="username"],
        input[type="password"] {
            width: 100%;
            background: rgb(233, 232, 232,0.7);
            padding: 10px;
            border: 1px solid #757575;
            border-radius: 25px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        input[type="username"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }
        button {
            width: 80px;
            padding: 9px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
            justify-content: center;
            display: flex;
            margin: 0 auto;
            margin-top: 40px;
           
        }
        button:hover {
            background: #23ee67;
        }
        .error {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 5px;
        }
        .success {
            color: #27ae60;
            font-size: 14px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="../image/1" alt="" style="display: block; margin: 0 auto 20px; width: 80px;">
        <h2>Online Quiz System</h2>
        
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