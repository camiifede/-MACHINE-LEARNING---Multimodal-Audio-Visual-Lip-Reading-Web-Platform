<?php
session_start();
require 'db.php';

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE session_id = ?");
    $stmt->execute([session_id()]);

    // Clear remember token in DB
    $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
}

// Expire the cookie
setcookie("remember_token", "", time() - 3600, "/");

session_destroy();
header("Location: login.php");
exit;
?>
