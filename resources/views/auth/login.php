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
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
<<<<<<< HEAD
            background-image: url("../../views/image/login_bg.jpg");
            background-size: cover;
            background-position: center;
=======
            background-image: url("../image/login_bg.jpg"); /* make sure the path is correct, include extension .jpg/.png */
            background-size: cover;      /* makes image cover entire viewport */
            background-position: center; /* centers the image */
            background-repeat: no-repeat;/* prevents tiling */
            background-attachment: fixed;/* optional: keeps image fixed while scrolling */
>>>>>>> 759aa5d6ee9919d1eda12ddb3ef11280fe293b0a
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            background:  rgba(255, 255, 255, 0.3); /* Transparent Whitesmoke */
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
        }

        .login-container img {
        display: block;
        margin: 0 auto 20px;
        width: 80px;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: blue;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            border-radius: 20px;
            margin-bottom: 8px;
            color: #555;
            font-weight: bold;
        }
        input[type="username"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 20px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        input[type="username"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }
        button {
            width: 100px;
            padding: 12px;
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
     * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background-image: url("../image/login_bg.jpg");
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;

        }
        .login-container {
            background: transparent whitesmoke;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: bold;
        }
        input[type="username"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        input[type="username"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }
        button {
            width: 100px;
            padding: 12px;
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
