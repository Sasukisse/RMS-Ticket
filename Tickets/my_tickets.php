<?php
session_start();
include '../Database/connection.php';

// Sécurité : utilisateur connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../Login/login.php');
    exit();
}

// Récupération des tickets de l'utilisateur
$sql = "SELECT id, title, status, type, category, created_at
        FROM tickets
        WHERE user_id = ?
        ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculer les messages non lus par ticket pour cet utilisateur
$unreadCounts = [];
if ($tickets) {
    // Préparer une requête qui compte les réponses non lues par ticket
    // On utilise ticket_responses (admin) si présent, sinon ticket_messages (legacy)
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
    $stmtCheck->execute(['ticket_responses']);
    $hasResponses = (int)$stmtCheck->fetchColumn() > 0;

    // Récupérer les IDs de tickets
    $ids = array_map(function($t){ return (int)$t['id']; }, $tickets);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    if ($hasResponses) {
        $sqlUnread = "SELECT tr.ticket_id, COUNT(*) AS cnt
                      FROM ticket_responses tr
                      LEFT JOIN (
                        SELECT ticket_id, MAX(last_read_at) AS last_read
                        FROM ticket_message_reads
                        WHERE user_id = ?
                        GROUP BY ticket_id
                      ) r ON r.ticket_id = tr.ticket_id
                      WHERE tr.ticket_id IN ($placeholders)
                        AND tr.created_at > COALESCE(r.last_read, '1970-01-01 00:00:00')
                        AND tr.user_id != ?
                      GROUP BY tr.ticket_id";
        $params = array_merge([$_SESSION['user_id']], $ids, [$_SESSION['user_id']]);
        $stmtU = $pdo->prepare($sqlUnread);
        $stmtU->execute($params);
        $rows = $stmtU->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) $unreadCounts[(int)$r['ticket_id']] = (int)$r['cnt'];
    } else {
        // fallback to ticket_messages
        $sqlUnread2 = "SELECT tm.ticket_id, COUNT(*) AS cnt
                       FROM ticket_messages tm
                       LEFT JOIN (
                         SELECT ticket_id, MAX(last_read_at) AS last_read
                         FROM ticket_message_reads
                         WHERE user_id = ?
                         GROUP BY ticket_id
                       ) r ON r.ticket_id = tm.ticket_id
                       WHERE tm.ticket_id IN ($placeholders)
                        AND tm.created_at > COALESCE(r.last_read, '1970-01-01 00:00:00')
                        AND tm.sender_id != ?
                       GROUP BY tm.ticket_id";
        $params2 = array_merge([$_SESSION['user_id']], $ids, [$_SESSION['user_id']]);
        $stmtU2 = $pdo->prepare($sqlUnread2);
        $stmtU2->execute($params2);
        $rows2 = $stmtU2->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows2 as $r) $unreadCounts[(int)$r['ticket_id']] = (int)$r['cnt'];
    }
}

// Badge de statut
function status_badge($s) {
    $map = ['open'=>'Ouvert','in_progress'=>'En cours','resolved'=>'Résolu','closed'=>'Fermé'];
    $label = $map[$s] ?? $s;
    return '<span class="badge badge-'.$s.'">'.$label.'</span>';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Mes tickets - RMS Ticket</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- On réutilise le CSS de CreateTickets (il contient déjà la navbar) -->
   <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="my_tickets.css">
  <style>
    .ticket-unread-badge { display:inline-block; min-width:20px;height:20px;padding:0 6px;border-radius:999px;background:#ef4444;color:#fff;font-weight:700;font-size:.75rem;line-height:20px;text-align:center;margin-left:8px; }
  </style>
</head>
<body>
  <div class="noise" aria-hidden="true"></div>

  <!-- NAVBAR (toujours visible) -->
  <nav class="navbar">
  <div class="nav-container">
    <a href="../HomePage/index.php" class="nav-brand" style="text-decoration:none;color:inherit;">
      <div class="brand-logo"><span>R</span></div>
      <span class="brand-text">RMS Ticket</span>
    </a>

  <div class="nav-menu">
  <a href="../HomePage/index.php" class="nav-link">Accueil</a>
  <a href="../CreateTickets/create_ticket.php" class="nav-link">Créer un ticket</a>
      <a href="../Tickets/my_tickets.php" class="nav-link active" id="nav-my-tickets" style="position:relative;display:inline-block;padding-right:18px;">
        Mes tickets
        <span id="tickets-badge" style="position:absolute;top:-6px;right:2px;display:none;min-width:20px;height:20px;padding:0 6px;border-radius:999px;background:#ef4444;color:#fff;font-weight:700;font-size:.75rem;line-height:20px;text-align:center;box-shadow:0 2px 6px rgba(0,0,0,.3);">0</span>
      </a>
      <?php if (!empty($_SESSION['droit']) && $_SESSION['droit']>=1): ?>
        <a href="../AdminPanel/adminpanel.php" class="nav-link">Administration</a>
      <?php endif; ?>
      <a href="../HomePage/logout.php" class="nav-link">Déconnexion</a>
    </div>
  </div> <!-- ✅ fermeture nav-container -->
</nav>


  <!-- CONTENU -->
  <main class="main-content">
    <div class="form-container">
      <div class="form-card">
        <div class="form-header">
          <h1>📂 Mes tickets</h1>
          <p class="form-subtitle">Liste de vos demandes et incidents</p>
        </div>

        <?php if (!$tickets): ?>
          <div class="alert" style="background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.12);">
            Vous n’avez pas encore de ticket.
          </div>
        <?php else: ?>
          <div class="table-wrapper">
            <table class="tickets-table">
              <thead>
  <tr>
    <th>#</th>
    <th>Intitulé</th>
    <th>Catégorie</th>
    <th>Type</th>
    <th>Statut</th>
    <th>Créé le</th>
    <th>Actions</th> <!-- ✅ -->
  </tr>
</thead>
<tbody>
<?php foreach ($tickets as $t): 
    $tid = (int)$t['id'];
    $nUnread = isset($unreadCounts[$tid]) ? (int)$unreadCounts[$tid] : 0;
?>
  <tr>
    <td><?= (int)$t['id'] ?></td>
    <td class="title"><?= htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8') ?></td>
    <td><?= htmlspecialchars(ucfirst($t['category']), ENT_QUOTES, 'UTF-8') ?></td>
    <td><?= htmlspecialchars(ucfirst($t['type']), ENT_QUOTES, 'UTF-8') ?></td>
    <td><?= status_badge($t['status']) ?></td>
    <td><?php $dt=new DateTime($t['created_at']); echo $dt->format('d/m/Y H:i'); ?></td>
    <td>
      <a class="btn small" href="ticket.php?id=<?= (int)$t['id'] ?>" style="text-decoration:none;display:inline-flex;align-items:center;gap:.4rem;">
        Voir
        <?php if ($nUnread > 0): ?>
          <span class="ticket-unread-badge"><?= $nUnread > 99 ? '99+' : $nUnread ?></span>
        <?php endif; ?>
      </a>
    </td>
  </tr>
<?php endforeach; ?>
</tbody>

            </table>
          </div>
        <?php endif; ?>

        <div style="margin-top:1rem; display:flex; gap:.5rem;">
          <a class="btn submit-btn" href="../CreateTickets/create_ticket.php" style="text-decoration:none;display:inline-flex;align-items:center;gap:.5rem;">
            Créer un ticket
          </a>
          <a class="btn submit-btn" href="../HomePage/index.php" style="text-decoration:none;display:inline-flex;align-items:center;gap:.5rem;background:rgba(255,255,255,0.08);">
            Retour accueil
          </a>
        </div>
      </div>
    </div>
  </main>
  <script>
    (function(){
      const badge = document.getElementById('tickets-badge');
      if (!badge) return;
      async function fetchUnread(){
        try{
          const res = await fetch('notifications_api.php');
          if (!res.ok) return;
          const json = await res.json();
          const n = parseInt(json.unread || 0, 10);
          if (n > 0) {
            badge.style.display = 'inline-block';
            badge.textContent = n;
          } else {
            badge.style.display = 'none';
          }
        }catch(e){console.error(e)}
      }

      fetchUnread();
      setInterval(fetchUnread, 10000);
    })();
  </script>
</body>
</html>
