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

$debug = !empty($_GET['debug']);

// check reads table
$stmtCheck2 = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
$stmtCheck2->execute(['ticket_message_reads']);
$hasReads = (int)$stmtCheck2->fetchColumn() > 0;

// If reads table missing, try to create it (safe operation)
if (!$hasReads) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS ticket_message_reads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ticket_id INT NOT NULL,
            user_id INT NOT NULL,
            last_read_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY ux_ticket_user (ticket_id, user_id),
            INDEX idx_user (user_id),
            INDEX idx_ticket (ticket_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $hasReads = true;
    } catch (Exception $e) {
        // ignore creation errors; continue
    }
}

// Helper to compute unread from a responses-like table
function count_unread_from_table($pdo, $tableName, $user_id) {
    // check table exists
    $stmtC = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
    $stmtC->execute([$tableName]);
    $exists = (int)$stmtC->fetchColumn() > 0;
    if (!$exists) return 0;

    if ($tableName === 'ticket_responses') {
        $sql = "SELECT COUNT(*) AS cnt
                FROM ticket_responses tr
                LEFT JOIN (
                  SELECT ticket_id, MAX(last_read_at) AS last_read
                  FROM ticket_message_reads
                  WHERE user_id = ?
                  GROUP BY ticket_id
                ) r ON r.ticket_id = tr.ticket_id
                WHERE tr.ticket_id IN (SELECT id FROM tickets WHERE user_id = ?)
                  AND tr.created_at > COALESCE(r.last_read, '1970-01-01 00:00:00')
                  AND tr.user_id != ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $user_id, $user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['cnt'] ?? 0);
    }

    // fallback for ticket_messages (older table)
    $sql2 = "SELECT COUNT(*) AS cnt
             FROM ticket_messages tm
             LEFT JOIN (
               SELECT ticket_id, MAX(last_read_at) AS last_read
               FROM ticket_message_reads
               WHERE user_id = ?
               GROUP BY ticket_id
             ) r ON r.ticket_id = tm.ticket_id
             WHERE tm.ticket_id IN (SELECT id FROM tickets WHERE user_id = ?)
               AND tm.created_at > COALESCE(r.last_read, '1970-01-01 00:00:00')
               AND tm.sender_id != ?";
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute([$user_id, $user_id, $user_id]);
    $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
    return (int)($row2['cnt'] ?? 0);
}

$unread = 0;
$unread += count_unread_from_table($pdo, 'ticket_responses', $user_id);
$unread += count_unread_from_table($pdo, 'ticket_messages', $user_id);

if ($debug) {
    $info = [
        'hasResponses' => $hasResponses,
        'hasReads' => $hasReads,
        'unread' => $unread
    ];

    // total counts (safe: only if table exists)
    if ($hasResponses) {
        $stmtTot = $pdo->prepare("SELECT COUNT(*) FROM ticket_responses tr WHERE tr.ticket_id IN (SELECT id FROM tickets WHERE user_id = ?)");
        $stmtTot->execute([$user_id]);
        $info['total_responses_for_user_tickets'] = (int)$stmtTot->fetchColumn();
    } else {
        $info['total_responses_for_user_tickets'] = 0;
    }

    if ($hasReads) {
        $stmt3 = $pdo->prepare("SELECT ticket_id, last_read_at FROM ticket_message_reads WHERE user_id = ? LIMIT 20");
        $stmt3->execute([$user_id]);
        $info['last_reads'] = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode($info);
    exit;
}

echo json_encode(['unread' => $unread]);
exit;
