<?php
session_start();
require 'db.php';

$username = $_POST['username'];
$password = $_POST['password'];
$remember = isset($_POST['remember']);

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = $user['is_admin'];

        $session_id = session_id();

        // Store session in DB
        $stmt = $pdo->prepare("INSERT INTO user_sessions (user_id, session_id) VALUES (?, ?)");
        $stmt->execute([$user['id'], $session_id]);

        // Remember Me logic
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            setcookie("remember_token", $token, time() + (30 * 24 * 60 * 60), "/"); // 30 days

            // Store token in DB
            $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
            $stmt->execute([$token, $user['id']]);
        }

        // Redirect based on role
        if ($user['is_admin']) {
            header("Location: dashboard_admin.php");
        } else {
            header("Location: dashboard_user.php");
        }
        exit;


    } else {
        echo "Invalid username or password.";
    }
} catch (PDOException $e) {
    echo "Login failed: " . $e->getMessage();
}
?>
