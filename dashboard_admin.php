<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit;
}

// Check session validity
$stmt = $pdo->prepare("SELECT * FROM user_sessions WHERE user_id = ? AND session_id = ?");
$stmt->execute([$_SESSION['user_id'], session_id()]);
if (!$stmt->fetch()) {
    session_destroy();
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body style="margin:0; background-color: rgb(0, 0, 0);">

    <div class="section bg-1">
        <!-- Sidebar + Toggle Button -->
        <div id="sidebar" class="sidebar">
            <div id="sidebarToggle" class="sidebar-toggle">&gt;</div>
            <ul>
                <li><a href="dashboard_admin.php">Dashboard</a></li>
                <li><a href="admin.php">Admin panel</a></li>
                <li><a href="logout.php">Log Out</a></li>
            </ul>
        </div>
        <div class="card" style="width:35%; margin: 4% auto 0 auto;">
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
            <p>You can manage users and sessions from the Admin Panel.</p>
        </div>
    </div>

</body>

</html>

<script>
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebarToggle');

    toggle.addEventListener('click', () => {
        sidebar.classList.toggle('show');
        toggle.innerHTML = sidebar.classList.contains('show') ? '&lt;' : '&gt;';
    });
</script>