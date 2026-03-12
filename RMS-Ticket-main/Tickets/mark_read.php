<?php
session_start();
include __DIR__ . '/../Database/connection.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok'=>false]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$ticket_id = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : (isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : 0);
if ($ticket_id <= 0) { echo json_encode(['ok'=>false]); exit; }

// Upsert last_read_at
$sql = "INSERT INTO ticket_message_reads (ticket_id, user_id, last_read_at) VALUES (?, ?, NOW())
        ON DUPLICATE KEY UPDATE last_read_at = NOW()";
$stmt = $pdo->prepare($sql);
$ok = $stmt->execute([$ticket_id, $user_id]);

echo json_encode(['ok' => (bool)$ok]);
exit;
