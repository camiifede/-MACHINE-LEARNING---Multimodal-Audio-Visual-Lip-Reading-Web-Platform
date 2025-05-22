<?php
require_once __DIR__ . '/db.php';

// Fetch accuracy-related data from the database
$stmt = $pdo->query("SELECT created_at, transcript, corrected_transcript, wrong_words FROM transcriptions");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
$totalWrongWords = 0;
$accuracyByDay = [];

foreach ($data as $row) {
    $date = date('Y-m-d', strtotime($row['created_at']));
    $original = str_word_count($row['transcript']);

    // Count wrong words (assumed comma-separated)
    $wrongWords = 0;
    if (!empty($row['wrong_words'])) {
        $wrongWords = count(array_filter(array_map('trim', explode(',', $row['wrong_words']))));
    }

    $total++;
    $totalWrongWords += $wrongWords;

    $accuracy = $original > 0 ? (1 - ($wrongWords / $original)) * 100 : 100;
    $accuracyByDay[$date][] = $accuracy;
}

$averageAccuracy = $total > 0 ? round(100 - ($totalWrongWords / array_sum(array_map(fn($r) => str_word_count($r['transcript']), $data))) * 100, 2) : 'N/A';

$chartData = [];
foreach ($accuracyByDay as $day => $accs) {
    $avg = round(array_sum($accs) / count($accs), 2);
    $chartData[] = ['date' => $day, 'accuracy' => $avg];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>AV-HuBERT Speech Transcription</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body style="margin:0; background-color: rgb(0, 0, 0);">

    <div class="section bg-1" style="width: 95%;">
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="login.php">Log In</a></li>
            </ul>
        </nav>

        <div class="structure">
            <img src="css/avhubert_logo.png" alt="AV-HuBERT Logo" style="width: 70px; height: auto;">
        </div>

        <div class="overlay-section">
            <h2>What is the AV-HuBERT</h2>
            <p>AV-HuBERT is a type of advanced AI model designed to understand human speech. What makes it special is
                that it learns directly from audio — like recordings of people talking — without needing written transcripts or subtitles.
                This means it can figure out the patterns of speech just by listening.
                The "AV" in AV-HuBERT stands for Audio-Visual, which means it doesn't just listen — it also watches. In its audio-visual version,
                AV-HuBERT uses both the sound of someone’s voice and their lip movements to understand what’s being said. This helps the model recognize
                speech more accurately, especially in noisy places or when the audio isn’t very clear — just like how people sometimes rely on lip-reading in
                real life.</p>

            <h2>About the website</h2>
            <p>This website is built for anyone who wants to upload a video and generate a transcript of what’s being said.
                If your video has background noise, you can choose a model that's designed to handle noisy audio for better results.
                Each model is trained on different types of data and has its own strengths, so you can pick the one that best fits your needs.</p>

            <h2>How to choose the best model</h2>
            <p><strong>→ AV-HuBERT:</strong> This model is ideal for both frontal and lateral videos.</p>
            <p><strong>→ MyModel:</strong> This model is ideal for one word transcription videos.</p>
        </div>
    </div>
    <div class="section bg-2">
        <div class="overlay-section">
            <div class="card">
                <h2>🔢 Model Accuracy Insights</h2>
                <p><strong>Average Accuracy:</strong> <?= $averageAccuracy ?>%</p>
                <p><strong>Total Reviewed Transcripts:</strong> <?= $total ?></p>
                <p><strong>Total Wrong Words Reported:</strong> <?= $totalWrongWords ?></p>
            </div>
            <canvas id="accuracyChart" width="600" height="250"></canvas>
        </div>
    </div>
    <script>
        const ctx = document.getElementById('accuracyChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($chartData, 'date')) ?>,
                datasets: [{
                    label: 'Accuracy Over Time (%)',
                    data: <?= json_encode(array_column($chartData, 'accuracy')) ?>,
                    fill: false,
                    borderColor: 'red',
                    tension: 0.2
                }]
            },
            options: {
                plugins: {
                    legend: {
                        labels: {
                            color: 'white' 
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            color: 'white' 
                        }
                    },
                    x: {
                        ticks: {
                            color: 'white' 
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>