<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    exit('Unauthorized');
}

$id = (int)$_POST['id'];
$transcript = trim($_POST['transcript'] ?? '');

if (!$transcript) {
    http_response_code(400);
    exit('Transcript cannot be empty.');
}

$stmt = $pdo->prepare("UPDATE transcriptions SET transcript = ? WHERE id = ? AND user_id = ?");
$success = $stmt->execute([$transcript, $id, $_SESSION['user_id']]);

if ($success) {
    echo "Transcript updated successfully.";
} else {
    http_response_code(500);
    echo "Failed to update transcript.";
}
?>
