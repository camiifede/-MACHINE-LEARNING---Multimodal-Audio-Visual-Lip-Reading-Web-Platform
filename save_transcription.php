<?php
session_start();
require 'db.php'; // Assumes this sets up $conn

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("❌ User not logged in.");
}

$user_id = $_SESSION['user_id'];
$transcript_file = $_POST['transcript_file'] ?? '';

if (!file_exists($transcript_file)) {
    die("❌ Transcript file not found: $transcript_file");
}

$transcript = file_get_contents($transcript_file);
if (trim($transcript) === '') {
    die("❌ Transcript file is empty.");
}

// Insert into database
$sql = "INSERT INTO transcripts (user_id, content, created_at) VALUES (?, ?, NOW())";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("❌ Prepare failed: " . $conn->error);
}

$stmt->bind_param("is", $user_id, $transcript);

if ($stmt->execute()) {
    echo "✅ Transcript saved to database.";
} else {
    echo "❌ Execute failed: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
