<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['is_admin']) {
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
    <title>User Dashboard</title>
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

        <div style=" max-width: 80%; margin: 4% auto 0 auto;" >
            <h1>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</h1>
            <p>Select a model or view your transcription history using the links.</p>

            <?php
            // Total transcript count
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM transcriptions WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $totalTranscripts = $stmt->fetchColumn();

            // Total words transcribed
            $stmt = $pdo->prepare("SELECT transcript FROM transcriptions WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $allTranscripts = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $totalWords = array_sum(array_map(fn($t) => str_word_count($t), $allTranscripts));

            // Most active day
            $stmt = $pdo->prepare("
    SELECT DATE(created_at) as day, COUNT(*) as count 
    FROM transcriptions 
    WHERE user_id = ? 
    GROUP BY day 
    ORDER BY count DESC 
    LIMIT 1
");
            $stmt->execute([$_SESSION['user_id']]);
            $mostActive = $stmt->fetch();
            $mostActiveDay = $mostActive ? $mostActive['day'] : 'N/A';
            $mostActiveCount = $mostActive ? $mostActive['count'] : 0;
            // Fetch the 5 most recent uploads
            $stmt = $pdo->prepare("
SELECT filename, created_at 
FROM transcriptions 
WHERE user_id = ? 
ORDER BY created_at DESC 
LIMIT 5
");
            $stmt->execute([$_SESSION['user_id']]);
            $recentTranscripts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Helper: Time ago formatter
            function timeAgo($datetime)
            {
                $time = strtotime($datetime);
                $diff = time() - $time;

                if ($diff < 60) return "$diff seconds ago";
                if ($diff < 3600) return floor($diff / 60) . " minutes ago";
                if ($diff < 86400) return floor($diff / 3600) . " hours ago";
                return floor($diff / 86400) . " days ago";
            }

            ?>

            <div style="display: flex; flex-wrap: wrap; gap: 30px; margin-top: 40px;">
                <!-- Recent Activity Card -->
                <div class="card" style="flex: 1; min-width: 300px;">
                    <h2>🕒 Recent Activity</h2>
                    <?php if (empty($recentTranscripts)): ?>
                        <p>No recent uploads.</p>
                    <?php else: ?>
                        <ul style="padding-left: 20px;">
                            <?php foreach ($recentTranscripts as $rt): ?>
                                <li>
                                    You uploaded <strong><?= htmlspecialchars($rt['filename']) ?></strong><br>
                                    <small><?= timeAgo($rt['created_at']) ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <!-- Analytics Card -->
                <div class="card" style="flex: 1; min-width: 300px;">
                    <h2>📈 Analytics</h2>
                    <p><strong>Total transcripts:</strong> <?= $totalTranscripts ?></p>
                    <p><strong>Total words transcribed:</strong> <?= $totalWords ?></p>
                    <p><strong>Most active day:</strong><br><?= $mostActiveDay ?> (<?= $mostActiveCount ?> transcripts)</p>
                </div>

                <!-- AV-HuBERT Card -->
                <div class="card" style="flex: 1; min-width: 300px;">
                    <h2>AV-HuBERT Model</h2>
                    <p>Transcribe spoken language using advanced audio-visual processing. Upload your video and get the transcript instantly.</p>
                    <a href="AV-HuBERT1.php">
                        <button>Go to AV-HuBERT</button>
                    </a>
                </div>

                <!-- Transcription Summary Card -->
                <div class="card" style="flex: 1; min-width: 300px;">
                    <h2>📋 Transcript History</h2>
                    <?php
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM transcriptions WHERE user_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $count = $stmt->fetchColumn();
                    ?>
                    <p>You have <strong><?= $count ?></strong> transcriptions stored.</p>
                    <a href="history.php">
                        <button>View History</button>
                    </a>
                </div>

                <!-- Coming Soon Card -->
                <div class="card" style="flex: 1; min-width: 300px;">
                    <h2>⚠️Coming Soon</h2>
                    <p>New features are on the way! Stay tuned for powerful tools and models coming in the next release.</p>
                </div>



                <div id="toolbar" class="mini-toolbar">
                    <button id="micBtn" title="Click to speak a command"
                        style="width: 38px; height: 38px; border-radius: 50%; background: #333; border: none; cursor: pointer; padding: 0; overflow: hidden; margin-bottom: 9%;">
                        <img src="css/mic.png" alt="Mic" style="width: 100%; height: 100%; object-fit: contain;">
                    </button>
                    <button id="historyBtn" title="Transcript History"
                        style="width: 38px; height: 38px; border-radius: 50%; background: #333; border: none; cursor: pointer; padding: 0; overflow: hidden; margin-bottom: 9%;">
                        <img src="css/transcript.png" alt="Transcript History" style="width: 100%; height: 100%; object-fit: contain;" />
                    </button>


                    <!-- Future tool buttons can go here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Voice Command Script -->
    <script>
        if ('webkitSpeechRecognition' in window) {
            const recognition = new webkitSpeechRecognition();
            recognition.continuous = false;
            recognition.lang = 'en-US';
            recognition.interimResults = false;

            const micBtn = document.getElementById('micBtn');

            const commands = {
                "log out": () => window.location.href = "logout.php",
                "go to dashboard": () => window.location.href = "dashboard_user.php",
                "open av hubert": () => window.location.href = "AV-HuBERT1.php",
                "show last transcript": () => {
                    const last = document.querySelector('.transcript-card');
                    if (last) last.scrollIntoView({
                        behavior: 'smooth'
                    });
                },
                "go to history": () => window.location.href = "history.php",
                "open transcript history": () => window.location.href = "history.php",
                "history": () => window.location.href = "history.php"
            };

            micBtn.addEventListener('click', () => {
                recognition.start();
                micBtn.classList.add("pulsing");
            });

            recognition.onresult = event => {
                const spoken = event.results[0][0].transcript.toLowerCase().trim();
                console.log("Heard:", spoken);
                micBtn.classList.remove("pulsing");

                for (const key in commands) {
                    if (spoken.includes(key)) {
                        commands[key]();
                        return;
                    }
                }

                alert(`Command not recognized: "${spoken}"`);
            };

            recognition.onerror = e => {
                micBtn.classList.remove("pulsing");
                console.error("Voice error:", e.error);
            };

            recognition.onend = () => {
                micBtn.classList.remove("pulsing");
            };
        } else {
            document.getElementById('micBtn').style.display = 'none';
            console.warn("Web Speech API not supported.");
        }
    </script>

    <script>
        const sidebar = document.getElementById('sidebar');
        const toggle = document.getElementById('sidebarToggle');

        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('show');
            toggle.innerHTML = sidebar.classList.contains('show') ? '&lt;' : '&gt;';
        });
    </script>

    <script>
        document.getElementById('historyBtn').addEventListener('click', () => {
            window.location.href = 'history.php';
        });
    </script>
</body>

</html>