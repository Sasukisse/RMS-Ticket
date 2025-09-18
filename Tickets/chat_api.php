<?php
session_start();
include __DIR__ . '/../Database/connection.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$droit = !empty($_SESSION['droit']) ? (int)$_SESSION['droit'] : 0;

$ticket_id = isset($_REQUEST['ticket_id']) ? (int)$_REQUEST['ticket_id'] : 0;
if ($ticket_id <= 0) {
    echo json_encode([]);
    exit;
}

// Vérifier existence et droits sur le ticket
$check = $pdo->prepare("SELECT user_id FROM tickets WHERE id = ?");
$check->execute([$ticket_id]);
$owner = $check->fetchColumn();
if (!$owner) {
    http_response_code(404);
    echo json_encode(['error' => 'Ticket not found']);
    exit;
}
if ($owner != $user_id && $droit < 1) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Si la table ticket_responses existe (admin panel l'utilise), l'utiliser pour lister les réponses
    $useResponses = false;
    try {
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
        $stmtCheck->execute(['ticket_responses']);
        $count = (int) $stmtCheck->fetchColumn();
        if ($count > 0) $useResponses = true;
    } catch (Exception $e) { /* ignore */ }

    if ($useResponses) {
        $stmt = $pdo->prepare("SELECT tr.id, tr.ticket_id, tr.user_id AS sender_id, tr.response_text AS message, tr.created_at, tr.is_admin_response, u.username
                               FROM ticket_responses tr
                               LEFT JOIN users u ON u.id = tr.user_id
                               WHERE tr.ticket_id = ?
                               ORDER BY tr.created_at ASC");
        $stmt->execute([$ticket_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($messages);
        exit;
    }

    // fallback: ancienne table ticket_messages
    $stmt = $pdo->prepare("SELECT tm.id, tm.ticket_id, tm.sender_id, tm.message, tm.created_at, u.username
                           FROM ticket_messages tm
                           LEFT JOIN users u ON u.id = tm.sender_id
                           WHERE tm.ticket_id = ?
                           ORDER BY tm.created_at ASC");
    $stmt->execute([$ticket_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($messages);
    exit;
}

if ($method === 'POST') {
    $msg = '';
    if (isset($_POST['message'])) {
        $msg = trim($_POST['message']);
    } else {
        $body = json_decode(file_get_contents('php://input'), true);
        $msg = isset($body['message']) ? trim($body['message']) : '';
    }

    if ($msg === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Message vide']);
        exit;
    }

    // Si la table ticket_responses existe, insérer dedans (admin panel lit cette table)
    $useResponses = false;
    try {
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
        $stmtCheck->execute(['ticket_responses']);
        $count = (int) $stmtCheck->fetchColumn();
        if ($count > 0) $useResponses = true;
    } catch (Exception $e) { /* ignore */ }

    if ($useResponses) {
        $is_admin = $droit >= 1 ? 1 : 0;
        $ins = $pdo->prepare("INSERT INTO ticket_responses (ticket_id, user_id, response_text, is_admin_response) VALUES (?, ?, ?, ?)");
        $ins->execute([$ticket_id, $user_id, $msg, $is_admin]);

        // Si admin répond, mettre à jour le statut du ticket en in_progress (comme dans adminpanel)
        if ($is_admin) {
            $up = $pdo->prepare("UPDATE tickets SET status = 'in_progress', updated_at = NOW() WHERE id = ?");
            $up->execute([$ticket_id]);
        }

        $lastId = $pdo->lastInsertId();
        $stmt = $pdo->prepare("SELECT tr.id, tr.ticket_id, tr.user_id AS sender_id, tr.response_text AS message, tr.created_at, tr.is_admin_response, u.username
                               FROM ticket_responses tr
                               LEFT JOIN users u ON u.id = tr.user_id
                               WHERE tr.id = ?");
        $stmt->execute([$lastId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode($row ?: []);
        exit;
    }

    // fallback: ancienne table ticket_messages
    $ins = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message) VALUES (?, ?, ?)");
    $ins->execute([$ticket_id, $user_id, $msg]);

    $lastId = $pdo->lastInsertId();
    $stmt = $pdo->prepare("SELECT tm.id, tm.ticket_id, tm.sender_id, tm.message, tm.created_at, u.username
                           FROM ticket_messages tm
                           LEFT JOIN users u ON u.id = tm.sender_id
                           WHERE tm.id = ?");
    $stmt->execute([$lastId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode($row ?: []);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

