<?php
session_start();
require 'db.php';

// Restrict access to admin only
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if current user is admin
$stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$is_admin = $stmt->fetchColumn();

if (!$is_admin) {
    die("Access denied.");
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_user'])) {
        $userId = (int)$_POST['delete_user'];
        $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $targetIsAdmin = $stmt->fetchColumn();

        if (!$targetIsAdmin) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
        }
    }

    if (isset($_POST['end_session'])) {
        $sessionId = $_POST['end_session'];
        $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE session_id = ?");
        $stmt->execute([$sessionId]);
    }

    if (isset($_POST['delete_transcript_id'])) {
        $transcriptId = (int)$_POST['delete_transcript_id'];
        $stmt = $pdo->prepare("DELETE FROM transcriptions WHERE id = ?");
        $stmt->execute([$transcriptId]);
    }
}

// Handle search queries
$userSearch = $_GET['user_search'] ?? '';
$sessionSearch = $_GET['session_search'] ?? '';

// Fetch filtered users
$userQuery = "SELECT id, username, email, is_admin, created_at FROM users";
$userParams = [];

if ($userSearch) {
    $userQuery .= " WHERE username LIKE ? OR email LIKE ?";
    $userParams[] = "%$userSearch%";
    $userParams[] = "%$userSearch%";
}
$userQuery .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($userQuery);
$stmt->execute($userParams);
$users = $stmt->fetchAll();

// Fetch filtered sessions
$sessionQuery = "
    SELECT s.id, u.username, s.session_id, s.created_at 
    FROM user_sessions s 
    JOIN users u ON s.user_id = u.id";
$sessionParams = [];

if ($sessionSearch) {
    $sessionQuery .= " WHERE u.username LIKE ?";
    $sessionParams[] = "%$sessionSearch%";
}
$sessionQuery .= " ORDER BY s.created_at DESC";

$stmt = $pdo->prepare($sessionQuery);
$stmt->execute($sessionParams);
$sessions = $stmt->fetchAll();

function highlight($text, $search) {
    if (!$search) return htmlspecialchars($text);
    return preg_replace_callback(
        '/' . preg_quote($search, '/') . '/i',
        fn($match) => '<span style="background-color: yellow; color: black;">' . htmlspecialchars($match[0]) . '</span>',
        htmlspecialchars($text)
    );
}

// Fetch all transcripts for moderation
$stmt = $pdo->prepare("
    SELECT t.id, t.filename, t.transcript, t.created_at, u.username 
    FROM transcriptions t 
    JOIN users u ON t.user_id = u.id 
    ORDER BY t.created_at DESC 
    LIMIT 50
");
$stmt->execute();
$allTranscripts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Panel</title>
    <meta charset="UTF-8">
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

    <div style="margin-left:10%; margin-right:10%;">
        <h1 style="color: white;">Admin Panel</h1>

        <!-- Users -->
        <div class="card">
            <h2>Users</h2>
            <form method="GET" style="margin-bottom: 1em;">
                <input type="text" name="user_search" placeholder="Search users..." value="<?= htmlspecialchars($userSearch) ?>">
                <button type="submit">Search</button>
            </form>

            <table cellpadding="10" style="background-color: white; color: black; width: 100%;">
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Admin</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= highlight($user['username'], $userSearch) ?></td>
                        <td><?= highlight($user['email'], $userSearch) ?></td>
                        <td><?= $user['is_admin'] ? 'Yes' : 'No' ?></td>
                        <td><?= $user['created_at'] ?></td>
                        <td>
                            <?php if (!$user['is_admin']): ?>
                                <form method="POST" onsubmit="return confirm('Delete this user?');">
                                    <input type="hidden" name="delete_user" value="<?= $user['id'] ?>">
                                    <button type="submit">Delete</button>
                                </form>
                            <?php else: ?> --- <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- Sessions -->
        <div class="card">
            <h2>Active Sessions</h2>
            <form method="GET" style="margin-bottom: 1em;">
                <input type="text" name="session_search" placeholder="Search sessions by username..." value="<?= htmlspecialchars($sessionSearch) ?>">
                <button type="submit">Search</button>
            </form>

            <table cellpadding="10" style="background-color: white; color: black; width: 100%;">
                <tr>
                    <th>Session ID</th>
                    <th>Username</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($sessions as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['session_id']) ?></td>
                        <td><?= highlight($s['username'], $sessionSearch) ?></td>
                        <td><?= $s['created_at'] ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('End this session?');">
                                <input type="hidden" name="end_session" value="<?= $s['session_id'] ?>">
                                <button type="submit">End</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- Transcript Moderation -->
        <div class="card">
            <h2>Transcript Moderation</h2>
            <?php if (empty($allTranscripts)): ?>
                <p>No transcripts found.</p>
            <?php else: ?>
                <ul style="max-height: 400px; overflow-y: auto; padding-left: 20px;">
                    <?php foreach ($allTranscripts as $t): ?>
                        <li style="margin-bottom: 20px;">
                            <strong><?= htmlspecialchars($t['filename']) ?></strong> by <em><?= htmlspecialchars($t['username']) ?></em><br>
                            <small><?= $t['created_at'] ?></small>
                            <pre style="background:#2a2a2a; color:#eee; padding:10px; border-radius:6px; white-space:pre-wrap;"><?= htmlspecialchars($t['transcript']) ?></pre>
                            <form method="POST" onsubmit="return confirm('Delete this transcript?');">
                                <input type="hidden" name="delete_transcript_id" value="<?= $t['id'] ?>">
                                <button type="submit">Delete</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
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
