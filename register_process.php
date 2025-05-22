<?php
session_start();
require 'db.php';

// Collect and sanitize input
$username = trim($_POST['username']);
$email = trim($_POST['email']);
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];

// === Validation ===

// Check required fields
if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
    die("All fields are required.");
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Invalid email address.");
}

// Password length check
if (strlen($password) < 6) {
    die("Password must be at least 6 characters long.");
}

// Username format check
if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    die("Username can only contain letters, numbers, and underscores.");
}

// Confirm passwords match
if ($password !== $confirm_password) {
    die("Passwords do not match.");
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// === Insert into Database ===
try {
    // Check for duplicate username or email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        die("Username or email already exists.");
    }

    // Insert user
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$username, $email, $hashed_password]);

    // Set flash message and redirect to login
    $_SESSION['flash_message'] = "Registration successful. Please log in.";
    header("Location: login.php");
    exit;

} catch (PDOException $e) {
    die("Registration failed: " . $e->getMessage());
}
?>
