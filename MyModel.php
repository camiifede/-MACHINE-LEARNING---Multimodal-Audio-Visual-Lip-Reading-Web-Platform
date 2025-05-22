<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['is_admin']) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>My Model</title>
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
                <li><a href="user.php">Profile</a></li>
                <li><a href="logout.php">Log Out</a></li>
            </ul>
        </div>

        <div class="card" Style="color:white; margin-top:17%; width: 30%; margin: 4% auto 0 auto;">
            <h2>Coming Soon</h2>
            <p>This feature is under development. Stay tuned for exciting updates in the next release!</p>
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