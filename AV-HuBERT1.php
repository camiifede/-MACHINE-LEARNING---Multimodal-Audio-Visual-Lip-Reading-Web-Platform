<!DOCTYPE html>
<html lang="en">

<head>
    <title>AV-HuBERT Speech Transcription</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">

</head>

<body class="page-AV-HuBERT black-bg-page" style="color: white; padding: 2em;">
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

    <div class="text-container" style="width: 75%; margin-top: 5%; margin-left: 10%;">
        <div class="card">
            <h2>AV-HuBERT</h2>
            <form method="post" enctype="multipart/form-data">
                <label>Upload a Video</label><br>
                <form action="AV-HuBERT1.php" method="post" enctype="multipart/form-data">
                    <input id="video-upload" type="file" name="video" accept="video/mp4" required>
                    <input type="submit" value="Transcribe">
                </form>
                <video id="videoPreview" width="480" height="100%" controls style="display:none; margin-top: 10px;">
                    <source id="videoSource" src="" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
                <br>
            </form>
        </div>
        <div class="card" style="margin-top: 20px;">
            <label>Or Record a Video</label>
            <video id="recordPreview" width="480" controls autoplay muted style="display:none;"></video>
            <div style="margin-top: 10px;">
                <button type="button" id="startRecord">Start Recording</button>
                <button type="button" id="stopRecord" disabled>Stop Recording</button>
                <form method="post" enctype="multipart/form-data" style="margin-top: 10px;" id="recordForm">
                    <input type="hidden" name="recorded" value="1">
                    <input type="file" id="recordedFile" name="video" style="display: none;">
                    <button type="submit" id="submitRecorded" disabled>Transcribe Recording</button>
                </form>
            </div>
        </div>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['video'])) {
            $targetDir = realpath(__DIR__ . "/AV-HuBERT-S2S/cache/example") . DIRECTORY_SEPARATOR;
            $videoName = pathinfo($_FILES["video"]["name"], PATHINFO_FILENAME);
            $videoPath = $targetDir . $videoName . ".mp4";
            $outputPath = $targetDir . $videoName . "_transcript.txt";

            if (move_uploaded_file($_FILES["video"]["tmp_name"], $videoPath)) {

                $venvPython = __DIR__ . "/venv/Scripts/python.exe";
                $scriptPath = __DIR__ . "/run_model.py";


                $cmd = "\"$venvPython\" \"$scriptPath\" \"$videoPath\" \"$outputPath\" 2>&1";
                exec($cmd, $output, $status);


                if ($status === 0 && file_exists($outputPath)) {
                    $raw = file_get_contents($outputPath);
                    $transcript = trim($raw, "[]'\"\n\r ");

                    require_once 'db.php'; // Include the PDO connection file
                    session_start(); // Make sure the session is active to access user_id

                    $userId = $_SESSION['user_id'] ?? 0; // Replace with actual session logic
                    $filename = $videoName . ".mp4";
                    $modelUsed = "AV-HuBERT-MuAViC-en";
                    $review = 0; // Default value
                    $tags = null;
                    $wrongWords = null;
                    $correctedTranscript = null;

                    try {
                        $stmt = $pdo->prepare("
        INSERT INTO transcriptions (
            user_id, filename, model_used, transcript, created_at, tags, review, wrong_words, corrected_transcript
        ) VALUES (
            :user_id, :filename, :model_used, :transcript, NOW(), :tags, :review, :wrong_words, :corrected_transcript
        )
    ");
                        $stmt->execute([
                            ':user_id' => $userId,
                            ':filename' => $filename,
                            ':model_used' => $modelUsed,
                            ':transcript' => $transcript,
                            ':tags' => $tags,
                            ':review' => $review,
                            ':wrong_words' => $wrongWords,
                            ':corrected_transcript' => $correctedTranscript
                        ]);
                    } catch (PDOException $e) {
                        echo "<p>Error inserting into database: " . htmlspecialchars($e->getMessage()) . "</p>";
                    }



                    echo <<<HTML
            <div class="card">
                <h3>Transcript:</h3>
                <pre id="transcript-text">{$transcript}</pre>
                <button onclick="copyTranscript()">Copy</button>
            </div>
            <script>
            function copyTranscript() {
                const text = document.getElementById('transcript-text').innerText;
                navigator.clipboard.writeText(text).then(() => alert("Transcript copied!"));
            }
            </script>
            HTML;
                } else {
                    echo "<p>Something went wrong during transcription.</p>";
                    echo "<pre>Command status: $status</pre>";
                    echo "<pre>Path: $outputPath</pre>";
                    if (!file_exists($outputPath)) {
                        echo "<pre>Transcript file was NOT found.</pre>";
                    }
                }
            }
            $filesToKeep = [$outputPath];
            $pattern = $targetDir . $videoName . "*";
            foreach (glob($pattern) as $file) {
                if (!in_array($file, $filesToKeep)) {
                    unlink($file);
                }
            }
        }

        ?>

    </div>
    </div>
    <script>
        document.querySelector('input[type="file"]').addEventListener('change', function(event) {
            const file = event.target.files[0];
            const video = document.getElementById('videoPreview');
            const source = document.getElementById('videoSource');

            if (file && file.type === "video/mp4") {
                const url = URL.createObjectURL(file);
                source.src = url;
                video.load();
                video.style.display = "block";
            } else {
                video.style.display = "none";
                source.src = "";
            }
        });
    </script>
</body>

</html>
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

<script>
    let mediaRecorder;
    let recordedChunks = [];

    const startBtn = document.getElementById('startRecord');
    const stopBtn = document.getElementById('stopRecord');
    const preview = document.getElementById('recordPreview');
    const recordedFileInput = document.getElementById('recordedFile');
    const submitBtn = document.getElementById('submitRecorded');

    startBtn.addEventListener('click', async () => {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: true,
            audio: true
        });
        preview.srcObject = stream;
        preview.style.display = 'block';
        mediaRecorder = new MediaRecorder(stream);
        recordedChunks = [];

        mediaRecorder.ondataavailable = e => {
            if (e.data.size > 0) recordedChunks.push(e.data);
        };

        mediaRecorder.onstop = () => {
            const blob = new Blob(recordedChunks, {
                type: 'video/mp4'
            });
            const file = new File([blob], 'recorded.mp4', {
                type: 'video/mp4'
            });

            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            recordedFileInput.files = dataTransfer.files;

            submitBtn.disabled = false;

            preview.srcObject.getTracks().forEach(track => track.stop());
        };

        mediaRecorder.start();
        startBtn.disabled = true;
        stopBtn.disabled = false;
    });

    stopBtn.addEventListener('click', () => {
        mediaRecorder.stop();
        startBtn.disabled = false;
        stopBtn.disabled = true;
    });
</script>