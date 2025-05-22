<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    die("Unauthorized access.");
}

$transcript_id = (int)$_GET['id'];
$format = $_GET['format'] ?? 'txt';

// Fetch the transcript belonging to the current user
$stmt = $pdo->prepare("SELECT filename, transcript FROM transcriptions WHERE id = ? AND user_id = ?");
$stmt->execute([$transcript_id, $_SESSION['user_id']]);
$data = $stmt->fetch();

if (!$data) {
    die("Transcript not found.");
}

$filename = pathinfo($data['filename'], PATHINFO_FILENAME);
$content = $data['transcript'];

if ($format === 'pdf') {
    // Optional: requires mpdf or TCPDF
    die("PDF export coming soon!");
} else {
    header('Content-Type: text/plain');
    header("Content-Disposition: attachment; filename=\"{$filename}_transcript.txt\"");
    echo $content;
}
exit;
?>
