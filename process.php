<?php
session_start(); // Required for $_SESSION['user_id']
$targetDir = "upload/";
$videoName = basename($_FILES["video"]["name"]);
$targetFile = __DIR__ . "/$targetDir" . $videoName;

// Upload the file
if (move_uploaded_file($_FILES["video"]["tmp_name"], $targetFile)) {
    echo "<p>File uploaded: <strong>$videoName</strong></p>";

    // Paths
    $pythonPath = __DIR__ . "\\venv\\Scripts\\python.exe";
    $scriptPath = __DIR__ . "\\run_model.py";
    $videoBaseName = pathinfo($videoName, PATHINFO_FILENAME);
    $outputPath = __DIR__ . "\\AV-HuBERT-S2S\\cache\\example\\{$videoBaseName}_transcript.txt";

    // Command to run the model
    $cmd = "\"$pythonPath\" \"$scriptPath\" \"$targetFile\" \"$outputPath\"";

    // Run Python script
    echo "<p>Running command:</p><pre>$cmd</pre>";

    exec($cmd, $output, $status);

    echo "<p>Status code: $status</p>";
    echo "<h4>Output:</h4><pre>" . implode("\n", $output) . "</pre>";

    if ($status === 0 && file_exists($outputPath)) {
        $transcript = file_get_contents($outputPath);
        echo "<h3>Transcription:</h3><pre>$transcript</pre>";

        // Save transcript to database
        $_POST['transcript_file'] = $outputPath;
        include 'save_transcription.php';
    } else {
        echo "<p>Model failed to process the video.</p>";
        echo "<pre>Debug Output:\n" . implode("\n", $output) . "</pre>";
    }
} else {
    echo "<p>Upload failed.</p>";
}
?>
