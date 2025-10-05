<?php
session_start();

// Destroy the session to log out the user
session_destroy();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            color: #fff;
        }
        .logout-container {
            background: #fff;
            color: #333;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            text-align: center;
            animation: slideIn 1s ease-in-out;
        }
        .logout-container h2 {
            margin: 0 0 20px;
            font-size: 1.8em;
        }
        .logout-container p {
            margin: 0 0 20px;
            font-size: 1.2em;
        }
        .logout-container a {
            display: inline-block;
            padding: 10px 20px;
            background: #2a5298;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s, transform 0.3s;
        }
        .logout-container a:hover {
            background: #1e3c72;
            transform: translateY(-3px);
        }
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @media (max-width: 768px) {
            .logout-container {
                width: 90%;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <h2>Logged Out</h2>
        <p>You have been successfully logged out.</p>
        <a href="#" onclick="redirectToLogin()">Go to Login</a>
    </div>
    <script>
        function redirectToLogin() {
            window.location.href = 'login.php';
        }
        // Auto-redirect after 2 seconds
        setTimeout(redirectToLogin, 2000);
    </script>
</body>
</html>
