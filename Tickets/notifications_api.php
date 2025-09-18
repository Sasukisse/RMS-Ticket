<?php
session_start();
include __DIR__ . '/../Database/connection.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['unread' => 0]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Vérifier si la table ticket_responses existe
$stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
$stmtCheck->execute(['ticket_responses']);
$hasResponses = (int)$stmtCheck->fetchColumn() > 0;

if (!$hasResponses) {
    // fallback: aucun système de messages admin → 0
    echo json_encode(['unread' => 0]);
    exit;
}

// Calculer le nombre de réponses non lues pour l'utilisateur
// Nous utilisons une table ticket_message_reads (created by migration) qui stocke last_read_at par ticket et user
$stmt = $pdo->prepare("SELECT COUNT(*) AS cnt
                       FROM ticket_responses tr
                       LEFT JOIN (
                         SELECT ticket_id, MAX(last_read_at) AS last_read
                         FROM ticket_message_reads
                         WHERE user_id = ?
                         GROUP BY ticket_id
                       ) r ON r.ticket_id = tr.ticket_id
                       WHERE tr.ticket_id IN (SELECT id FROM tickets WHERE user_id = ?)
                         AND tr.created_at > COALESCE(r.last_read, '1970-01-01 00:00:00')
                       AND tr.user_id != ?");

$stmt->execute([$user_id, $user_id, $user_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$unread = (int)($row['cnt'] ?? 0);

echo json_encode(['unread' => $unread]);
exit;
