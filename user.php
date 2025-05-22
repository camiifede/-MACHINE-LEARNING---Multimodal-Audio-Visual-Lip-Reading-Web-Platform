<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['is_admin']) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$success = $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'], $_POST['current_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];

    // Verify current password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $hashed = $stmt->fetchColumn();

    if (password_verify($current, $hashed)) {
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$newHash, $user_id]);
        $success = "Password updated successfully.";
    } else {
        $error = "Incorrect current password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>User Profile</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body style="margin:0; background-color: rgb(0, 0, 0);">

    <div class="section bg-1">
    <!-- Sidebar + Toggle Button -->
    <div id="sidebar" class="sidebar">
        <div id="sidebarToggle" class="sidebar-toggle">&gt;</div>
        <ul>
            <li><a href="dashboard_user.php">Dashboard</a></li>
            <li><a href="AV-HuBERT1.php">AV-HuBERT</a></li>
            <li><a href="MyModel.php">New model</a></li>
            <li><a href="history.php">Transcription History</a></li>
            <li><a href="user.php">Profile details</a></li>
            <li><a href="logout.php">Log Out</a></li>
        </ul>
    </div>

    <div class="card" Style="color: white; width:35%; margin: 4% auto 0 auto;">
        <h1>User Profile</h1>
        <p><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>

        <h2>Change Password</h2>

        <?php if ($success): ?>
            <p style="color: lightgreen;"><?= $success ?></p>
        <?php elseif ($error): ?>
            <p style="color: red;"><?= $error ?></p>
        <?php endif; ?>

        <form method="POST">
            <label for="current_password">Current Password:</label><br>
            <input type="password" name="current_password" required>

            <label for="new_password">New Password:</label><br>
            <input type="password" name="new_password" required>

            <button type="submit">Update Password</button>
        </form>
    </div>
    </div>
    <script>
        const sidebar = document.getElementById('sidebar');
        const toggle = document.getElementById('sidebarToggle');

        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('show');
            toggle.innerHTML = sidebar.classList.contains('show') ? '&lt;' : '&gt;';
        });
    </script>
</body>
</html>
