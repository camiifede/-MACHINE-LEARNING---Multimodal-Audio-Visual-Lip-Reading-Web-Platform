<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to view your transcription history.");
}

$user_id = $_SESSION['user_id'];

// Handle review/correction submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_review'])) {
    $id = (int) $_POST['review_transcript_id'];
    $review = trim($_POST['review'] ?? '');
    $wrong = trim($_POST['wrong_words'] ?? '');
    $corrected = trim($_POST['corrected_transcript'] ?? '');

    $stmt = $pdo->prepare("
        UPDATE transcriptions
        SET review = :review,
            wrong_words = :wrong_words,
            corrected_transcript = :corrected
        WHERE id = :id AND user_id = :user_id
    ");
    $stmt->execute([
        ':review' => $review,
        ':wrong_words' => $wrong,
        ':corrected' => $corrected,
        ':id' => $id,
        ':user_id' => $user_id
    ]);
}


// Handle transcript deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_transcript'])) {
    $delete_id = (int) $_POST['delete_transcript_id'];
    $stmt = $pdo->prepare("DELETE FROM transcriptions WHERE id = :id AND user_id = :user_id");
    $stmt->execute([':id' => $delete_id, ':user_id' => $user_id]);
}

// Handle tag update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_tags_id'])) {
    $updateId = (int) $_POST['update_tags_id'];
    $tagString = trim($_POST['tags']);
    $stmt = $pdo->prepare("UPDATE transcriptions SET tags = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$tagString, $updateId, $user_id]);
}

// Handle tag filtering
$filterTag = $_GET['tag_filter'] ?? '';
if ($filterTag) {
    $stmt = $pdo->prepare("
        SELECT id, filename, model_used, transcript, created_at, tags 
        FROM transcriptions 
        WHERE user_id = :user_id AND tags LIKE :filter 
        ORDER BY created_at DESC
    ");
    $stmt->execute([
        ':user_id' => $user_id,
        ':filter' => "%$filterTag%"
    ]);
} else {
    $stmt = $pdo->prepare("
        SELECT id, filename, model_used, transcript, created_at, tags 
        FROM transcriptions 
        WHERE user_id = :user_id 
        ORDER BY created_at DESC
    ");
    $stmt->execute([':user_id' => $user_id]);
}

$transcripts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Transcription History</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body style="margin:0;">

    <div class="content-section bg-3">
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

        <div style="top: 5%; width: 75%; color:black; width: 80%; margin: 4% auto 0 auto;">
            <h2>Your Transcription History</h2>

            <!-- Tag Filter Form -->

            <form method="get" style="margin-bottom: 20px;">
                <input type="text" name="tag_filter" placeholder="Filter by label..." value="<?= htmlspecialchars($filterTag) ?>"
                    style="padding: 6px; border-radius: 6px; border: 2px solid black;">
                <button type="submit">Filter</button>
            </form>

            <?php if (empty($transcripts)): ?>
                <p>No transcripts found.</p>
            <?php else: ?>
                <?php foreach ($transcripts as $t): ?>
                    <div class="transcript-card">
                        <h4><?= htmlspecialchars($t['filename']) ?> <small>(<?= htmlspecialchars($t['model_used']) ?>)</small></h4>
                        <small><em><?= htmlspecialchars($t['created_at']) ?></em></small>

                        <div class="transcript-wrapper" data-id="<?= $t['id'] ?>">
                            <pre class="transcript-display"><?= htmlspecialchars($t['transcript']) ?></pre>
                            <textarea class="transcript-edit" style="display:none; width:100%; background-color: #2b2b2b; color: white; border: 1px solid #444; height:150px;"><?= htmlspecialchars($t['transcript']) ?></textarea>

                            <div style="margin-top: 10px;">
                                <button class="edit-btn">Edit</button>
                                <button class="save-btn" style="display:none;">Save</button>
                            </div>
                            <a href="download_transcript.php?id=<?= $t['id'] ?>&format=txt">
                                <button style="margin-top: 10px;">Download as .txt</button>
                            </a>
                            <form method="post" style="margin-top: 10px;">
                                <input type="hidden" name="review_transcript_id" value="<?= $t['id'] ?>">

                                <label style="color:white">Model Review 1 to 5:</label><br>
                                <input type="text" name="review" value="<?= htmlspecialchars($t['review'] ?? '') ?>" style="width: 5%; background-color: #2b2b2b; color: white; border: 1px solid #444; margin-top:2px"><br><br>

                                <label style="color:white">Wrong Words:</label><br>
                                <input type="text" name="wrong_words" value="<?= htmlspecialchars($t['wrong_words'] ?? '') ?>" style="width: 5%; background-color: #2b2b2b; color: white; border: 1px solid #444;  margin-top:2px"><br><br>

                                <label style="color:white">Corrected Transcript:</label><br>
                                <textarea name="corrected_transcript" style="width: 100%; height: 100px; background-color: #2b2b2b; color: white; border: 1px solid #444;"><?= htmlspecialchars($t['corrected_transcript'] ?? '') ?></textarea><br><br>

                                <button type="submit" name="save_review">Save Feedback</button>
                            </form>


                            <form method="post" class="tag-form" style="margin-top: 10px;">
                                <input type="hidden" name="update_tags_id" value="<?= $t['id'] ?>">
                                <input type="text" name="tags" value="<?= htmlspecialchars($t['tags'] ?? '') ?>" placeholder="Add labels (comma-separated)" style="max-width: 100%; padding: 6px;">
                                <button type="submit" style="margin-top: 5px;">Update Tags</button>
                            </form>
                        </div>

                        <form method="post" style="margin-top: 10px;">
                            <input type="hidden" name="delete_transcript_id" value="<?= $t['id'] ?>">
                            <button type="submit" name="delete_transcript" onclick="return confirm('Are you sure you want to delete this transcript?');">Delete</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <!-- Inline Edit + Save Script -->
    <script>
        document.querySelectorAll('.transcript-wrapper').forEach(wrapper => {
            const editBtn = wrapper.querySelector('.edit-btn');
            const saveBtn = wrapper.querySelector('.save-btn');
            const pre = wrapper.querySelector('.transcript-display');
            const textarea = wrapper.querySelector('.transcript-edit');
            const id = wrapper.dataset.id;

            editBtn.addEventListener('click', () => {
                pre.style.display = 'none';
                textarea.style.display = 'block';
                editBtn.style.display = 'none';
                saveBtn.style.display = 'inline-block';
            });

            saveBtn.addEventListener('click', () => {
                const newTranscript = textarea.value;
                fetch('save_transcript_update.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `id=${encodeURIComponent(id)}&transcript=${encodeURIComponent(newTranscript)}`
                    })
                    .then(res => res.text())
                    .then(msg => {
                        pre.textContent = newTranscript;
                        pre.style.display = 'block';
                        textarea.style.display = 'none';
                        editBtn.style.display = 'inline-block';
                        saveBtn.style.display = 'none';
                        alert(msg);
                    })
                    .catch(() => alert("Failed to save changes."));
            });
        });
    </script>

    <script>
        const sidebar = document.getElementById('sidebar');
        const toggle = document.getElementById('sidebarToggle');

        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('show');
            toggle.innerHTML = sidebar.classList.contains('show') ? '&lt;' : '&gt;';
        });
    </script>

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
        document.getElementById('historyBtn').addEventListener('click', () => {
            window.location.href = 'history.php';
        });
    </script>
</body>

</html>