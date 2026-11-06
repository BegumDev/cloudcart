<?php
session_start();
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT COUNT(*) FROM Users WHERE username = :username OR email = :email");
    $stmt->execute(['username' => $username, 'email' => $email]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        $error = "Username or Email already exists.";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $insertStmt = $conn->prepare("INSERT INTO Users (username, email, password_hash) VALUES (:username, :email, :password_hash)");
        
        try {
            $insertStmt->execute([
                'username' => $username,
                'email' => $email,
                'password_hash' => $password_hash
            ]);
            $success = "Account created successfully! You can now log in.";
        } catch(PDOException $e) {
            $error = "Registration failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - CloudCart</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav>
        <a href="index.php" class="brand">Cloud<span>Cart</span></a>
        <div class="links">
            <a href="index.php">Shop</a>
            <a href="login.php">Log In</a>
        </div>
    </nav>

    <div class="auth-container">
        <div class="auth-card">
            <h2>Create Account</h2>
            <?php if ($error): ?>
                <div style="background: #ffe6e6; color: #dc3545; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem; font-size: 0.9rem;">
                    <?= $error ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div style="background: #e6ffe6; color: #28a745; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem; font-size: 0.9rem;">
                    <?= $success ?> <a href='login.php' style="color: #155724; text-decoration: underline;">Log in here.</a>
                </div>
            <?php endif; ?>
            
            <form action="register.php" method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required minlength="8">
                </div>
                <button type="submit" class="btn">Sign Up</button>
            </form>
            <div class="auth-links">
                <p>Already have an account? <a href="login.php">Log in</a></p>
            </div>
        </div>
    </div>
</body>
</html>